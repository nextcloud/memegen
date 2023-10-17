<!-- SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com> -->
<!-- SPDX-License-Identifier: AGPL-3.0-or-later -->
<template>
	<div class="meme-caps-modal">
		<NcModal container=".meme-picker-content"
			size="small"
			name="Caps dialog"
			:out-transition="true"
			@close="onClose">
			<div v-if="meme !== null" class="meme-caps-dialog">
				<h2>
					{{ t('memegen', 'Caption your meme') }}
				</h2>
				<div class="dialog-wrapper">
					<div v-for="n in parseInt(meme.lines)"
						:key="n"
						class="input-wrapper">
						<NcTextField
							:key="n"
							:ref="'meme-caption-' + String(n)"
							:label="'Caption ' + String(n)"
							:value.sync="captions[n-1]"
							@update:value="onCapUpdate()" />
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
						@load="isLoaded = true">
				</div>
				<div class="button-wrapper">
					<NcButton
						type="primary"
						@click="onSubmit">
						Use this meme
					</NcButton>
				</div>
			</div>
		</NcModal>
	</div>
</template>
<script>
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import { generateUrl } from '@nextcloud/router'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
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
			type: Array,
			default: null,
		},
	},
	data() {
		return {
			captions: [],
			isLoaded: false,
			imgUrl: '',
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
			}, 500)()
		},
		updateCaps() {
			this.isLoaded = false
			let getParams = ''
			for (let i = 0; i < this.captions.length; i++) {
				let caption = this.captions[i]
				if (caption === '') {
					caption = '_'
				} else {
					this.encodeCaption(caption)
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
				externalUrl += '/' + this.encodeCaption(this.captions[i])
			}
			externalUrl += '.jpg'

			this.$emit('submit', externalUrl)
		},
		onClose() {
			this.$emit('close')
		},
		encodeCaption(caption) {
			// Replace underscore (_) with 2 underscores (__)
			caption = caption.replace(/_/g, '__')

			// Replace space () with underscore (_)
			caption = caption.replace(/ /g, '_')

			// Replace dash (-) with 2 dashes (--)
			caption = caption.replace(/-/g, '--')

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
	align-items: center;
	justify-content: center;
	padding: 12px 0px 0px 12px;
}

.meme-caps-dialog {
	width: 100%;
	height: 100%;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 12px 12px 0px 12px;
	overflow-x: hidden;

	h2 {
		display: flex;
		align-items: center;
	}

	.dialog-wrapper {
		width: 90%;
		display: grid;
		grid-gap: 8px;
		grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
		justify-content: center;
		align-items: center;
		.input-wrapper {
			display: flex;
			align-items: center;
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
		width: 100%;
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
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			display: flex;
			justify-content: center;
			align-items: center;
			z-index: 1;
		}
	}

	.button-wrapper {
		display: flex;
		align-items: center;
		width: 100%;
		flex-direction: column;
		padding: 12px 12px 12px 12px;
	}
}
</style>
