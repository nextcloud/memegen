<template>
	<div v-tooltip.top="{ content: gif.title }"
		class="result"
		@keydown.enter="$emit('click')"
		@click="$emit('click')">
		<div v-if="!isLoaded" class="loading-icon">
			<NcLoadingIcon
				:size="44"
				:title="t('integration_giphy', 'Loading GIF')" />
		</div>
		<img v-show="isLoaded"
			class="gif-image"
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
		gif: {
			type: Object,
			required: true,
		},
	},

	data() {
		return {
			isLoaded: false,
			imgUrl: generateUrl('/apps/memegen/memes/{memeId}', { memeId: this.gif.memeId }),
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

	.gif-image {
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
