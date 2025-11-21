/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCSPNonce } from '@nextcloud/auth'
import { linkTo } from '@nextcloud/router'
import {
	NcCustomPickerRenderResult,
	registerCustomPickerElement,
	registerWidget,
} from '@nextcloud/vue/components/NcRichText'

__webpack_nonce__ = getCSPNonce() // eslint-disable-line
__webpack_public_path__ = linkTo('memegen', 'js/') // eslint-disable-line

registerWidget('memegen_meme', async (el, { richObjectType, richObject, accessible }) => {
	const { createApp } = await import(/* webpackChunkName: "vue-lazy" */'vue')
	const { default: MemeReferenceWidget } = await import(/* webpackChunkName: "reference-lazy" */'./views/MemeReferenceWidget.vue')
	const app = createApp(MemeReferenceWidget, { richObjectType, richObject, accessible })
	app.mixin({ methods: { t, n } })
	app.mount(el)
}, () => {}, { hasInteractiveView: false })

registerCustomPickerElement('memegen_meme', async (el, { providerId, accessible }) => {
	const { createApp } = await import(/* webpackChunkName: "vue-lazy" */'vue')
	const { default: MemeCustomPickerElement } = await import(/* webpackChunkName: "reference-picker-lazy" */'./views/MemeCustomPickerElement.vue')

	const app = createApp(MemeCustomPickerElement, { providerId, accessible })
	app.mixin({ methods: { t, n } })
	app.mount(el)

	return new NcCustomPickerRenderResult(el, app)
}, (_, renderResult) => {
	renderResult.object.unmount()
})
