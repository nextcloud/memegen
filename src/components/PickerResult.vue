<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div
		class="result"
		:aria-label="meme.alt"
		role="button"
		@keydown.enter="$emit('click')"
		@click="$emit('click')">
		<div v-if="!isLoaded" class="loading-icon">
			<NcLoadingIcon
				:size="44"
				:title="t('memegen', 'Loading memes')" />
		</div>
		<img
			class="meme-image"
			:src="imgUrl"
			:alt="meme.alt"
			@load="isLoaded = true">
	</div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'

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
}
</script>

<style scoped lang="scss">
.result {
	display: flex;
	flex-direction: column;
	align-items: center;
	position: relative;

	> * {
		cursor: pointer;
	}

	.loading-icon {
		display: flex;
		align-items: center;
		width: 100%;
		height: 100%;
		position: absolute;
		top: 0;
		inset-inline-start: 0;
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
