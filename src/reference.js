/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { NcCustomPickerRenderResult, registerCustomPickerElement, registerWidget } from '@nextcloud/vue/dist/Components/NcRichText.js'

import { getCSPNonce } from '@nextcloud/auth'
import { linkTo } from '@nextcloud/router'

__webpack_nonce__ = getCSPNonce() // eslint-disable-line
__webpack_public_path__ = linkTo('memegen', 'js/') // eslint-disable-line

registerWidget('memegen_meme', async (el, { richObjectType, richObject, accessible }) => {
	const { default: Vue } = await import(/* webpackChunkName: "vue-lazy" */'vue')
	Vue.mixin({ methods: { t, n } })
	const { default: MemeReferenceWidget } = await import(/* webpackChunkName: "reference-lazy" */'./views/MemeReferenceWidget.vue')
	const Widget = Vue.extend(MemeReferenceWidget)
	new Widget({
		propsData: {
			richObjectType,
			richObject,
			accessible,
		},
	}).$mount(el)
})

registerCustomPickerElement('memegen_meme', async (el, { providerId, accessible }) => {
	const { default: Vue } = await import(/* webpackChunkName: "vue-lazy" */'vue')
	Vue.mixin({ methods: { t, n } })
	const { default: MemeCustomPickerElement } = await import(/* webpackChunkName: "reference-picker-lazy" */'./views/MemeCustomPickerElement.vue')
	const Element = Vue.extend(MemeCustomPickerElement)
	const vueElement = new Element({
		propsData: {
			providerId,
			accessible,
		},
	}).$mount(el)
	return new NcCustomPickerRenderResult(vueElement.$el, vueElement)
}, (el, renderResult) => {
	renderResult.object.$destroy()
})
