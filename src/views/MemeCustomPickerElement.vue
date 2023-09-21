<template>
	<div class="meme-picker-content">
		<h2>
			{{ t('memegen', 'Memegen meme picker') }}
		</h2>
		<div v-if="selectedMeme !== null"
			class="input-wrapper">
			<GetCapsDialog
				ref="caps-dialog"
				:meme="selectedMeme"
				@submit="onSubmit"
				@close="onCapsDialogClose" />
		</div>
		<div class="input-wrapper">
			<NcTextField
				ref="memegen-search-input"
				:value.sync="searchQuery"
				:show-trailing-button="searchQuery !== ''"
				:label="inputPlaceholder"
				@trailing-button-click="onClear"
				@update:value="onInput">
				<template #trailing-button-icon>
					<CloseIcon :size="16" />
				</template>
				<MagnifyIcon :size="16" />
			</NcTextField>
		</div>
		<div v-if="memes.length === 0"
			class="empty-content-wrapper">
			<NcEmptyContent v-if="searching"
				:title="t('memegen', 'Searching...')">
				<template #icon>
					<NcLoadingIcon />
				</template>
			</NcEmptyContent>
			<NcEmptyContent v-else
				:title="t('memegen', 'No results')">
				<template #icon>
					<img class="empty-content-img"
						:src="sadGifUrl">
				</template>
			</NcEmptyContent>
		</div>
		<div v-else
			ref="results"
			class="results">
			<PickerResult v-for="(meme, i) in memes"
				:key="i + '-' + meme.blankUrl"
				:meme="meme"
				:tabindex="0"
				@click="onSelect(meme)" />
			<InfiniteLoading v-if="memes.length >= LIMIT"
				@infinite="infiniteHandler">
				<template #no-results>
					<div class="infinite-end">
						<img :src="sadGifUrl">
						{{ t('memegen', 'No results') }}
					</div>
				</template>
				<template #no-more>
					<div class="infinite-end">
						<img :src="sadGifUrl">
						{{ t('memegen', 'No more memes') }}
					</div>
				</template>
			</InfiniteLoading>
		</div>
		<a class="attribution"
			target="_blank"
			:title="poweredByTitle"
			href="https://memegen.link">
			<img :src="poweredByImgSrc"
				:alt="poweredByTitle">
		</a>
	</div>
</template>

<script>
import MagnifyIcon from 'vue-material-design-icons/Magnify.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'

import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

import PickerResult from '../components/PickerResult.vue'
import GetCapsDialog from '../components/GetCapsDialog.vue'

import axios from '@nextcloud/axios'
import { generateUrl, imagePath } from '@nextcloud/router'
import { delay } from '../utils.js'

import InfiniteLoading from 'vue-infinite-loading'
import Tooltip from '@nextcloud/vue/dist/Directives/Tooltip.js'
import Vue from 'vue'
Vue.directive('tooltip', Tooltip)

const LIMIT = 20

export default {
	name: 'MemeCustomPickerElement',

	components: {
		PickerResult,
		GetCapsDialog,
		NcLoadingIcon,
		InfiniteLoading,
		NcEmptyContent,
		NcTextField,
		MagnifyIcon,
		CloseIcon,
	},

	props: {
		providerId: {
			type: String,
			required: true,
		},
		accessible: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			searchQuery: '',
			searching: false,
			memes: [],
			selectedMeme: null,
			inputPlaceholder: t('memegen', 'Search memes'),
			offset: null,
			abortController: null,
			poweredByImgSrc: imagePath('memegen', 'memegen.logo.png'),
			poweredByTitle: t('memegen', 'Powered by Memegen.link'),
			LIMIT,
			sadGifUrl: imagePath('memegen', 'sad.gif'),
		}
	},

	computed: {
	},

	watch: {
	},

	mounted() {
		this.search()
		this.focusOnInput()
	},

	beforeDestroy() {
		this.cancelSearchRequests()
	},

	methods: {
		focusOnInput() {
			setTimeout(() => {
				// this.$refs['memegen-search-input']?.focus()
				this.$refs['memegen-search-input'].$el.getElementsByTagName('input')[0]?.focus()
			}, 300)
		},
		onSelect(meme) {
			this.cancelSearchRequests()
			this.selectedMeme = meme
		},
		onSubmit(extUrl) {
			this.$emit('submit', extUrl)
		},
		onInput() {
			delay(() => {
				this.updateSearch()
			}, 500)()
		},
		onClear() {
			this.searchQuery = ''
			this.updateSearch()
		},
		onCapsDialogClose() {
			this.selectedMeme = null
		},
		updateSearch() {
			if (this.$refs.results?.scrollTop) {
				this.$refs.results.scrollTop = 0
			}
			this.cancelSearchRequests()
			this.memes = []
			this.offset = 0
			this.search()
		},
		cancelSearchRequests() {
			if (this.abortController) {
				this.abortController.abort()
			}
		},
		infiniteHandler($state) {
			this.search($state)
		},
		search(state = null, limit = LIMIT) {
			this.abortController = new AbortController()
			this.searching = true
			const url = this.offset === null
				? generateUrl(
					'apps/memegen/search?term={term}&limit={limit}',
					{ term: this.searchQuery, limit }
				)
				: generateUrl(
					'apps/memegen/search?term={term}&offset={offset}&limit={limit}',
					{ term: this.searchQuery, offset: this.offset, limit }
				)
			return axios.get(url, {
				signal: this.abortController.signal,
			})
				.then((response) => {
					this.offset = response.data.offset
					this.memes.push(...response.data.entries)
					if (state !== null) {
						if (response.data.entries.length > 0) {
							state.loaded()
						}
						if (response.data.entries.length < limit) {
							state.complete()
						}
					}
				})
				.catch((error) => {
					console.debug('memegen search request error', error)
					if (state !== null) {
						state.complete()
					}
				})
				.then(() => {
					this.searching = false
				})
		},
	},
}
</script>

<style scoped lang="scss">
.meme-picker-content {
	width: 100%;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 12px 16px 0 16px;
	overflow-y: auto;
	max-height: 800px;

	h2 {
		display: flex;
		align-items: center;
	}

	.attribution {
		position: absolute;
		bottom: 4px;
		left: 16px;
		height: 30px;
		border-radius: var(--border-radius);
		border: 2px solid var(--color-background-darker);
		img {
			height: 100%;
		}
	}

	.input-wrapper {
		display: flex;
		align-items: center;
		width: 100%;
		input {
			flex-grow: 1;
		}
		.input-loading {
			padding: 0 4px;
		}
	}

	.empty-content-wrapper {
		display: flex;
		align-items: center;
		height: 5000px;

		.empty-content-img {
			width: 100px;
		}
	}

	.results {
		width: 98%;
		// ugly but...makes it take all available space
		height: 5000px;
		//flex-grow: 1;
		display: grid;
		grid-auto-rows: 160px;
		grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
		grid-gap: 8px;
		overflow-y: scroll;
		scrollbar-width: auto;
		scrollbar-color: var(--color-primary);
		margin-top: 12px;
		padding-right: 16px;

		.result {
			&:hover {
				border: 2px solid var(--color-primary);
				border-radius: var(--border-radius);
			}
		}

		:deep(.infinite-status-prompt) {
			height: 100%;
			.infinite-end {
				height: 100%;
				display: flex;
				flex-direction: column;
				align-items: center;
				justify-content: center;

				img {
					width: 50px;
				}
			}
		}
	}
}
</style>
