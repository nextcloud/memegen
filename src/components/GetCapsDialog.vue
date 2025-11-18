<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcModal container=".meme-picker-content"
		size="large"
		name="Caps dialog"
		class="meme-caps-modal"
		:v-show="meme !== null"
		:out-transition="true"
		@close="onClose">
		<div class="modal-content">
			<h2>
				{{ t('memegen', 'Caption your meme') }}
			</h2>
			<div v-if="captions.length > 0" class="dialog-wrapper">
				<div v-for="n in parseInt(meme.lines)"
					:key="n"
					class="input-wrapper">
					<NcTextField v-if="captions.length > 0"
						:key="n"
						:ref="'meme-caption-' + String(n)"
						v-model="captions[n-1]"
						:label="'Caption ' + String(n)"
						@update:model-value="onCapUpdate()" />
				</div>
			</div>
			<div class="meme-renderer">
				<div v-if="!isLoaded" class="loading-icon">
					<NcLoadingIcon
						:size="44"
						:title="t('memegen', 'Loading meme')" />
				</div>
				<img v-show="imgUrl !== ''"
					class="meme-image"
					:src="imgUrl"
					:alt="meme.alt"
					@load="isLoaded = true">
			</div>
			<div class="button-wrapper">
				<NcButton
					variant="primary"
					@click="onSubmit">
					{{ t('memegen','Use this meme') }}
				</NcButton>
			</div>
		</div>
	</NcModal>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcModal from '@nextcloud/vue/components/NcModal'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { delay } from '../utils.js'

export default {
	name: 'GetCapsDialog',
	components: {
		NcModal,
		NcTextField,
		NcLoadingIcon,
		NcButton,
	},
	props: {
		meme: {
			type: Object,
			default: null,
		},
	},
	data() {
		return {
			captions: [],
			isLoaded: false,
			imgUrl: '',
			timeout: null,
		}
	},
	mounted() {
		if (this.meme !== null) {
			this.initialize()
		}
	},
	methods: {
		initialize() {
			this.captions = Array(this.meme.lines).fill('')
			this.imgUrl = generateUrl('/apps/memegen/memes/{memeId}', { memeId: this.meme.memeId })
		},
		onCapUpdate() {
			delay(() => {
				this.updateCaps()
			}, 1000)()
		},
		updateCaps() {
			this.isLoaded = false
			let getParams = ''
			for (let i = 0; i < this.captions.length; i++) {
				let caption = this.captions[i]
				if (caption === '') {
					caption = '_'
				} else {
					caption = this.encodeCaption(caption)
				}
				caption = encodeURIComponent('captions[' + String(i) + ']') + '=' + caption

				if (i < this.captions.length - 1) {
					caption += '&'
				}
				getParams += caption
			}

			let newUrl = generateUrl('/apps/memegen/memes/{memeId}', { memeId: this.meme.memeId })
			newUrl += '?' + getParams

			this.imgUrl = newUrl

		},
		onSubmit() {
			let externalUrl = 'https://api.memegen.link/images/' + this.meme.memeId

			for (let i = 0; i < this.captions.length; i++) {
				if (this.captions[i] !== '') {
					externalUrl += '/' + this.encodeCaption(this.captions[i])
				} else {
					externalUrl += '/_'
				}
			}

			while (externalUrl.endsWith('/_')) {
				externalUrl = externalUrl.slice(0, -2)
			}

			externalUrl += '.jpg'

			this.$emit('submit', externalUrl)
		},
		onClose() {
			this.$emit('close')
		},
		encodeCaption(caption) {
			// Replace multiple spaces with underscore (_) with 2 underscores (__)
			caption = caption.replace(/_/g, '__')

			// Replace dash (-) with 2 dashes (--)
			caption = caption.replace(/-/g, '--')

			// Replace any number of spaces with a single underscore (_)
			caption = caption.replace(/ +/g, '_')

			// Replace newline character with tilde + N (~n)
			caption = caption.replace(/\n/g, '~n')

			// Replace reserved URL characters in using following escape patterns
			caption = caption.replace(/\?/g, '~q')
			caption = caption.replace(/&/g, '~a')
			caption = caption.replace(/%/g, '~p')
			caption = caption.replace(/#/g, '~h')
			caption = caption.replace(/\//g, '~s')
			caption = caption.replace(/\\/g, '~b')
			caption = caption.replace(/</g, '~l')
			caption = caption.replace(/>/g, '~g')

			// Replace double quote (") with 2 single quotes ('')
			caption = caption.replace(/"/g, "''")

			caption = encodeURIComponent(caption)

			// We need to escape the single quotes and stars (*) as well since the generated links won't be resolved by nextcloud otherwise
			caption = caption.replace(/'/g, '%27')
			caption = caption.replace(/\*/g, '%2A')

			return caption
		},
	},
}
</script>
<style scoped lang="scss">

.meme-caps-modal {
	width: 100%;
	height: 100%;
	display: flex;
	flex-direction: column;
	padding: 12px 12px 0px 12px;
	overflow-x: hidden;

	.modal-content {
		width: 100%;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-items: center;
		align-self: center;
		margin-top: 24px;
		h2 {
			text-align: center;
		}
		.dialog-wrapper {
			width: 90%;
			display: grid;
			grid-gap: 8px;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			justify-content: center;
			align-content: center;

			.input-wrapper {
				display: flex;
				align-items: center;
				align-content: center;
				justify-content: center;
				width: 100%;
				flex-direction: column;
				input {
					flex-grow: 1;
				}
				.input-loading {
					padding: 0 4px;
				}
			}
		}

		.meme-renderer {
			display: flex;
			align-items: center;
			justify-content: center;
			width: 90%;
			max-height: 640px;
			min-height: 640px;
			object-fit: cover;
			flex-direction: column;
			padding: 20px 0px 20px 0px;
			img {
				max-height: 640px;
				max-width: 100%;
				object-fit: cover;
			}

			.loading-icon {
				position: absolute;
				top: 0.5;
				inset-inline-start: 0.5;
				display: flex;
				justify-content: center;
				align-items: center;
				z-index: 1;
			}
		}

		.button-wrapper {
			display: flex;
			align-items: center;
			width: 90%;
			flex-direction: column;
			padding: 12px 12px 12px 12px;
		}
	}
}

</style>
