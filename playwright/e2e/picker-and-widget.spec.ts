/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Page } from '@playwright/test'

import { login } from '@nextcloud/e2e-test-server/playwright'
import { test as base, expect } from '@playwright/test'

// the test container always has this admin user
const admin = { userId: 'admin', password: 'admin' }
const ocs = { 'OCS-APIRequest': 'true', Accept: 'application/json' }

// served for every meme image the app proxies, the test container must not depend on memegen.link
const pixel = Buffer.from('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7', 'base64')

// search results in the shape MemegenService::searchMemes() returns them
const memes = [
	{ memeId: 'doge', alt: 'Doge', blank_url: 'https://api.memegen.link/images/doge.jpg', lines: 2 },
	{ memeId: 'fry', alt: 'Futurama Fry', blank_url: 'https://api.memegen.link/images/fry.jpg', lines: 2 },
	{ memeId: 'buzz', alt: 'X, X Everywhere', blank_url: 'https://api.memegen.link/images/buzz.jpg', lines: 2 },
]

type PickerElements = Record<string, {
	callback: (element: HTMLElement, data: object) => Promise<unknown>
	onDestroy: (element: HTMLElement, renderResult: unknown) => void
}>

type Widgets = Record<string, { callback: (element: HTMLElement, data: object) => Promise<void> }>

// Every test also fails on an uncaught exception, on an error the app's scripts log, such as one Vue caught in a
// component, or on an error response from one of the app's own routes.
// Logged errors and error responses of other apps on the instance are ignored on purpose.
const test = base.extend<{ appErrors: void }>({
	appErrors: [async ({ page }, use) => {
		const errors: string[] = []
		page.on('pageerror', (error) => errors.push(`uncaught: ${error.message}`))
		page.on('console', (message) => {
			if (message.type() === 'error' && message.location().url.includes('/memegen/js/')) {
				errors.push(`logged: ${message.text()}`)
			}
		})
		page.on('response', (response) => {
			if (response.status() >= 400 && response.url().includes('/apps/memegen/')) {
				errors.push(`${response.status()} ${response.request().method()} ${new URL(response.url()).pathname}`)
			}
		})
		await use()
		expect(errors).toEqual([])
	}, { auto: true }],
})

/**
 * Answer every search of the picker with the given memes.
 *
 * @param page the page the picker runs in
 * @param entries the memes to answer with
 * @param held answer only once this promise resolves
 */
async function answerSearches(page: Page, entries: typeof memes, held?: Promise<void>) {
	await page.route('**/apps/memegen/search**', async (route) => {
		await held
		// the picker may have cancelled the request in the meantime
		await route.fulfill({ json: { offset: entries.length, entries } }).catch(() => {})
	})
}

/**
 * Serve every meme image the app proxies as a pixel.
 *
 * @param page the page the images load in
 * @param held serve the images only once this promise resolves
 */
async function serveImages(page: Page, held?: Promise<void>) {
	await page.route('**/apps/memegen/memes/**', async (route) => {
		await held
		await route.fulfill({ contentType: 'image/gif', body: pixel }).catch(() => {})
	})
}

/**
 * A promise and the function that resolves it, to hold an answer back until a test has looked at the state before it.
 */
function holdBack() {
	let release!: () => void
	const held = new Promise<void>((resolve) => {
		release = resolve
	})
	return { held, release }
}

/**
 * Load the app's reference script, which registers the picker and the widget.
 *
 * Nextcloud loads it on pages that render references, and the test container has no app that does,
 * so the script is added the way Nextcloud adds it, after the app's translations for the language the test asks for.
 *
 * @param page a page of a logged in user
 * @param language which of the app's translation files to load, none for English
 */
async function loadReferenceScript(page: Page, language = 'en') {
	await page.evaluate(async (language) => {
		const globals = window as unknown as { OC: { appswebroots: Record<string, string> } }
		const nonce = document.querySelector<HTMLScriptElement>('script[nonce]')?.nonce ?? ''
		const files = [...(language === 'en' ? [] : [`l10n/${language}.js`]), 'js/memegen-reference.js']
		for (const file of files) {
			await new Promise((resolve, reject) => {
				const script = document.createElement('script')
				script.src = `${globals.OC.appswebroots.memegen}/${file}`
				script.nonce = nonce
				script.onload = resolve
				script.onerror = () => reject(new Error(`could not load ${file}`))
				document.head.appendChild(script)
			})
		}
	}, language)
	await page.waitForFunction(() => {
		const globals = window as unknown as { _vue_richtext_custom_picker_elements?: PickerElements, _vue_richtext_widgets?: Widgets }
		return !!globals._vue_richtext_custom_picker_elements?.memegen_meme && !!globals._vue_richtext_widgets?.memegen_meme
	})
}

/**
 * Mount the meme picker the way the smart picker does, and record the links it hands back.
 *
 * @param page a page of a logged in user
 */
async function mountPicker(page: Page) {
	await loadReferenceScript(page)
	await page.evaluate(async () => {
		const globals = window as unknown as { _vue_richtext_custom_picker_elements: PickerElements, memegenPicker: unknown, memegenSubmitted: string[] }
		const element = document.createElement('div')
		element.id = 'custom-picker'
		document.body.appendChild(element)
		globals.memegenSubmitted = []
		element.addEventListener('submit', (event) => globals.memegenSubmitted.push((event as CustomEvent<string>).detail))
		globals.memegenPicker = await globals._vue_richtext_custom_picker_elements.memegen_meme.callback(element, {
			providerId: 'memegen_meme',
			accessible: false,
		})
	})
	return page.locator('#custom-picker')
}

/**
 * Unmount the meme picker the way the smart picker does when it closes.
 *
 * @param page the page the picker runs in
 */
async function unmountPicker(page: Page) {
	await page.evaluate(() => {
		const globals = window as unknown as { _vue_richtext_custom_picker_elements: PickerElements, memegenPicker: unknown }
		globals._vue_richtext_custom_picker_elements.memegen_meme.onDestroy(document.getElementById('custom-picker')!, globals.memegenPicker)
	})
}

test.beforeEach(async ({ page }) => {
	await login(page.request, admin)
	// a test that holds the images back routes them again, and the newer route wins
	await serveImages(page)
	await page.goto('apps/files/')
})

test.describe('Link previews', () => {
	test('offer the provider to the smart picker', async ({ page }) => {
		const response = await page.request.get('../ocs/v2.php/references/providers', { headers: ocs })
		expect(response.ok()).toBe(true)
		const providers = (await response.json()).ocs.data as Array<{ id: string, title: string, icon_url: string }>
		const provider = providers.find((candidate) => candidate.id === 'memegen_meme')
		expect(provider).toBeDefined()
		expect(provider?.title).toBe('Memegen memes')
		// the provider icon is served by the app
		expect((await page.request.get(provider!.icon_url)).ok()).toBe(true)
	})
})

test.describe('Meme picker', () => {
	test('say that it is searching, then list the memes', async ({ page }) => {
		const search = holdBack()
		await answerSearches(page, memes, search.held)
		const picker = await mountPicker(page)

		await expect(picker.getByRole('heading', { name: 'Memegen meme picker' })).toBeVisible()
		await expect(picker.getByLabel('Search memes', { exact: true })).toBeVisible()
		await expect(picker.getByText('Searching…')).toBeVisible()

		search.release()
		const results = picker.locator('.result')
		await expect(results).toHaveCount(memes.length)
		await expect(picker.getByLabel('Search memes', { exact: true })).toBeFocused()
		await expect(results.first()).toHaveAttribute('aria-label', 'Doge')
	})

	test('say when no meme matches', async ({ page }) => {
		await answerSearches(page, [])
		const picker = await mountPicker(page)

		await expect(picker.getByText('No results')).toBeVisible()
	})

	test('show a spinner while a meme loads', async ({ page }) => {
		const images = holdBack()
		await answerSearches(page, memes.slice(0, 1))
		await serveImages(page, images.held)
		const picker = await mountPicker(page)

		const result = picker.getByRole('button', { name: 'Doge' })
		await expect(result.getByRole('img', { name: 'Loading memes', exact: true })).toBeVisible()
		images.release()
		await expect(result.getByRole('img', { name: 'Loading memes', exact: true })).toBeHidden()
	})

	test('search for what the user types, and clear the search', async ({ page }) => {
		await answerSearches(page, memes)
		const picker = await mountPicker(page)
		await expect(picker.locator('.result')).toHaveCount(memes.length)

		// a search for doge finds one meme
		await page.route((url) => url.pathname.endsWith('/apps/memegen/search') && url.searchParams.get('term') === 'doge', (route) =>
			route.fulfill({ json: { offset: 1, entries: memes.slice(0, 1) } }))
		const input = picker.getByLabel('Search memes', { exact: true })
		await input.fill('doge')
		await expect(picker.locator('.result')).toHaveCount(1)
		await expect(picker.locator('.result')).toHaveAttribute('aria-label', 'Doge')

		const cleared = page.waitForRequest((request) => request.url().includes('/apps/memegen/search') && new URL(request.url()).searchParams.get('term') === '')
		await picker.getByRole('button', { name: 'Clear text' }).click()
		await cleared
		await expect(input).toHaveValue('')
		await expect(picker.locator('.result')).toHaveCount(memes.length)
	})

	test('caption a meme and hand its link to the smart picker', async ({ page }) => {
		await answerSearches(page, memes)
		const images = holdBack()
		await serveImages(page, images.held)
		const picker = await mountPicker(page)

		await picker.getByRole('button', { name: 'Doge' }).click()
		const dialog = page.getByRole('dialog')
		await expect(dialog.getByRole('heading', { name: 'Caption your meme' })).toBeVisible()
		// the meme image is still loading
		await expect(dialog.getByRole('img', { name: 'Loading meme', exact: true })).toBeVisible()
		images.release()
		await expect(dialog.getByRole('img', { name: 'Loading meme', exact: true })).toBeHidden()

		await expect(dialog.getByLabel(/^Caption \d$/)).toHaveCount(2)
		// memegen.link writes a space as _, a _ as __ and a ? as ~q
		await dialog.getByLabel('Caption 1').fill('such test?')
		const preview = page.waitForRequest((request) => request.url().includes('/apps/memegen/memes/doge?') && request.url().includes('very'))
		await dialog.getByLabel('Caption 2').fill('very_wow')
		const query = new URL((await preview).url()).searchParams
		expect([query.get('captions[0]'), query.get('captions[1]')]).toEqual(['such_test~q', 'very__wow'])

		await dialog.getByRole('button', { name: 'Use this meme' }).click()
		await expect.poll(() => page.evaluate(() => (window as unknown as { memegenSubmitted: string[] }).memegenSubmitted))
			.toEqual(['https://api.memegen.link/images/doge/such_test~q/very__wow.jpg'])
	})

	test('close the caption dialog', async ({ page }) => {
		await answerSearches(page, memes)
		const picker = await mountPicker(page)

		await picker.getByRole('button', { name: 'Doge' }).click()
		const dialog = page.getByRole('dialog')
		await expect(dialog).toBeVisible()
		await dialog.getByRole('button', { name: 'Close' }).click()
		// the modal reports the close only after its fade out, and the picker then removes the dialog
		await expect(page.getByRole('dialog', { includeHidden: true })).toHaveCount(0)
		await expect(picker.locator('.result')).toHaveCount(memes.length)

		// the next meme gets a dialog of its own
		await picker.getByRole('button', { name: 'Futurama Fry' }).click()
		await expect(dialog.getByRole('heading', { name: 'Caption your meme' })).toBeVisible()
		await expect(dialog.getByRole('img', { name: 'Futurama Fry', exact: true })).toHaveAttribute('src', /\/apps\/memegen\/memes\/fry$/)
	})

	test('cancel a running search when the smart picker closes', async ({ page }) => {
		const search = holdBack()
		await answerSearches(page, memes, search.held)
		const failed: string[] = []
		page.on('requestfailed', (request) => {
			if (request.url().includes('/apps/memegen/search')) {
				failed.push(request.failure()?.errorText ?? '')
			}
		})
		const searched = page.waitForRequest((request) => request.url().includes('/apps/memegen/search'))
		await mountPicker(page)
		await searched

		await unmountPicker(page)
		await expect.poll(() => failed).toEqual(['net::ERR_ABORTED'])
		search.release()
	})

	test('close right after it opened', async ({ page }) => {
		await answerSearches(page, memes)
		await loadReferenceScript(page)
		// open and close in one go, so that no slow round trip lets the picker stay open for 300 ms
		await page.evaluate(async () => {
			const globals = window as unknown as { _vue_richtext_custom_picker_elements: PickerElements }
			const element = document.createElement('div')
			document.body.appendChild(element)
			const picker = globals._vue_richtext_custom_picker_elements.memegen_meme
			picker.onDestroy(element, await picker.callback(element, { providerId: 'memegen_meme', accessible: false }))
		})
		// the picker focuses its search field 300 ms after it opened; an uncaught error fails the test
		await page.waitForTimeout(600)
	})
})

test.describe('Reference widget', () => {
	// a meme as MemegenReferenceProvider puts it into the rich object of a link preview,
	// where proxied_url is an absolute URL of the server
	const memePath = '/index.php/apps/memegen/memes/doge?captions%5B0%5D=such&captions%5B1%5D=test'
	const richObject = {
		memeId: 'doge',
		name: 'Doge',
		blank_url: 'https://api.memegen.link/images/doge.jpg',
		lines: 2,
		captions: ['such', 'test'],
		meme_id: 'doge',
	}

	/**
	 * Render the rich object in the app's reference widget.
	 *
	 * @param page a page of a logged in user
	 * @param language which of the app's translation files to load
	 */
	async function renderWidget(page: Page, language = 'en') {
		await loadReferenceScript(page, language)
		await page.evaluate(async (data) => {
			const globals = window as unknown as { _vue_richtext_widgets: Widgets }
			const element = document.createElement('div')
			element.id = 'reference-widget'
			document.body.appendChild(element)
			await globals._vue_richtext_widgets.memegen_meme.callback(element, {
				richObjectType: 'memegen_meme',
				richObject: data,
				accessible: true,
			})
		}, { ...richObject, proxied_url: new URL(memePath, page.url()).href })
		return page.locator('#reference-widget')
	}

	test('show the meme with its name', async ({ page }) => {
		const images = holdBack()
		await serveImages(page, images.held)
		const widget = await renderWidget(page)

		await expect(widget.getByText('Doge', { exact: true })).toBeVisible()
		// a spinner stands in for the meme while it loads
		await expect(widget.getByRole('img', { name: 'Loading meme template', exact: true })).toBeVisible()
		// an image that has not loaded yet has no size, so its display tells whether the widget hides it
		await expect(widget.locator('img.image')).toHaveCSS('display', 'none')
		images.release()
		await expect(widget.locator('img.image')).toBeVisible()
		await expect(widget.getByRole('img', { name: 'Loading meme template', exact: true })).toBeHidden()
		await expect(widget.locator('img.image')).toHaveAttribute('src', new URL(memePath, page.url()).href)
		await expect(widget.getByRole('link', { name: 'Powered by Memegen.link' })).toHaveAttribute('href', 'https://memegen.link')
	})

	test('translate the label of its spinner', async ({ page }) => {
		const images = holdBack()
		await serveImages(page, images.held)
		// the test container forces English, so the app's German translations are loaded the way Nextcloud loads them
		const widget = await renderWidget(page, 'de')

		await expect(widget.getByRole('img', { name: 'Lade Meme Vorlage', exact: true })).toBeVisible()
		images.release()
		await expect(widget.locator('img.image')).toBeVisible()
	})
})
