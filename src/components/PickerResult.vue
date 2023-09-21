<!-- SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com> -->
<!-- SPDX-License-Identifier: AGPL-3.0-or-later -->
<template>
	<div v-tooltip.top="{ content: meme.name }"
		class="result"
		@keydown.enter="$emit('click')"
		@click="$emit('click')">
		<div v-if="!isLoaded" class="loading-icon">
			<NcLoadingIcon
				:size="44"
				:title="t('memegen', 'Loading memes')" />
		</div>
		<img v-show="isLoaded"
			class="meme-image"
			:src="imgUrl"
			@load="isLoaded = true">
	</div>
</template>

<script>
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'PickerResult',

	components: {
		NcLoadingIcon,
	},

	props: {
		meme: {
			type: Object,
			required: true,
		},
	},

	data() {
		return {
			isLoaded: false,
			imgUrl: generateUrl('/apps/memegen/memes/{memeId}', { memeId: this.meme.memeId }),
		}
	},

	watch: {
	},

	mounted() {
	},

	methods: {
	},
}
</script>

<style scoped lang="scss">
.result {
	display: flex;
	flex-direction: column;
	align-items: center;

	> * {
		cursor: pointer;
	}

	.loading-icon {
		display: flex;
		align-items: center;
		width: 100%;
		height: 100%;
	}

	.meme-image {
		height: 100%;
		width: 100%;
		object-fit: cover;
		border-radius: var(--border-radius);

		&:hover {
			object-fit: contain;
		}
	}
}
</style>
