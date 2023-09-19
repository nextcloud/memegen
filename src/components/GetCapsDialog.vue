<template>
	<div>
		<NcModal container=".gif-picker-content"
			:show.sync="showDialog"
			@close="closeCapsDialog"
			size="small"
			name="Caps dialog"
			:outTransition="true"
			:hasNext="true"
			:hasPrevious="true">
			<div v-if="meme !== null" class="meme-caps-dialog">
				<div class="dialog-wrapper">
					<div v-for="n in parseInt(meme.lines)"
						class="input-wrapper"
						:key="n">
						<NcTextField
							:key="n"
							:ref="'meme-caption-' + String(n)"
							:label="'Caption ' + String(n)"
							:value.sync="captions[n-1]"
							@update:value="onCapUpdate()">
						</NcTextField>
					</div>
				</div>
				<div class="meme-renderer">
					<div v-if="!isLoaded" class="loading-icon">
						<NcLoadingIcon
							:size="44"
							:title="t('integration_giphy', 'Loading GIF')" />
					</div>
					<img v-show="isLoaded"
						class="meme-image"
						:src="imgUrl"
						@load="isLoaded = true">
				</div>
				<div class="input-wrapper">
					<NcButton
						@click="onSubmit"
						type="primary">
						Submit
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

export default {
	name: 'GetCapsDialog',
	components: {
		NcModal,
		NcTextField,
		NcLoadingIcon,
		NcButton,
	},
	data() {
		return {
			showDialog: false,
			meme: null,
			captions: [],
			isLoaded: false,
			imgUrl: '',
		}
	},
	methods: {
		showCapsDialog(meme) {
			this.meme = meme
			this.captions = Array(meme.lines).fill('')
			this.imgUrl = generateUrl('/apps/memegen/memes/{memeId}', { memeId: meme.memeId })
			this.showDialog = true
		},
		closeCapsDialog() {
			this.showDialog = false
		},
		onCapUpdate() {
			let getParams = ''
			for (let i = 0; i < this.captions.length; i++) {
				let caption = this.captions[i]
				if (caption === '') {
					caption = '_'
				}

				caption = encodeURIComponent('captions[' + String(i) + ']') + '=' + encodeURIComponent(this.encodeCaption(caption))

				if (i < this.captions.length - 1) {
					caption += '&'
				}
				getParams += caption
			}

			let newUrl = generateUrl('/apps/memegen/memes/{memeId}', { memeId: this.meme.memeId },)
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
			// this.showDialog = false
		},
		encodeCaption(caption) {
			// Replace space () with underscore (_)
			caption = caption.replace(/ /g, '_')

			// Replace underscore (_) with 2 underscores (__)
			caption = caption.replace(/_/g, '__')

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

			return caption

		},
	}
}
</script>
<style scoped lang="scss">
.meme-caps-dialog {
	width: 100%;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 12px 16px 0 16px;
	overflow-y: auto;
	max-height: 800px;

	.dialog-wrapper {
		width: 90%;
		display: grid;
		grid-gap: 8px;
		grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
		object-fit: cover;
		flex-direction: column;
		padding: 20px 0px 20px 0px;
		input {
			flex-grow: 1;
		}
		.input-loading {
			padding: 0 4px;
		}
	}
}
</style>
