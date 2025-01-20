<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="memegen-meme-reference">
		<div class="meme-wrapper">
			<strong v-if="richObject.alt !== null">
				{{ richObject.alt }}
			</strong>
			<div v-if="!isLoaded" class="loading-icon">
				<NcLoadingIcon :size="44"
					:title="t('memes', 'Loading meme template')" />
			</div>
			<img v-show="isLoaded"
				class="image"
				:src="richObject.proxied_url"
				@load="isLoaded = true">
			<a v-show="isLoaded"
				class="attribution"
				target="_blank"
				:title="poweredByTitle"
				href="https://memegen.link">
				<div class="content" />
			</a>
		</div>
	</div>
</template>

<script>
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

import { imagePath } from '@nextcloud/router'

export default {
	name: 'MemeReferenceWidget',

	components: {
		NcLoadingIcon,
	},

	props: {
		richObjectType: {
			type: String,
			default: '',
		},
		richObject: {
			type: Object,
			default: null,
		},
		accessible: {
			type: Boolean,
			default: true,
		},
	},

	data() {
		return {
			isLoaded: false,
			poweredByImgSrc: imagePath('memegen', 'memegen.logo.png'),
			poweredByTitle: t('memegen', 'Powered by Memegen.link'),
		}
	},

	computed: {
	},

	methods: {
	},
}
</script>

<style scoped lang="scss">
.memegen-meme-reference {
	width: 100%;
	padding: 12px;
	white-space: normal;

	.meme-wrapper {
		width: 100%;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		position: relative;

		.image {
			max-height: 400px;
			max-width: 100%;
			border-radius: var(--border-radius);
		}

		.attribution {
			position: absolute;
			left: 0;
			bottom: 0;
			height: 33px;
			width: 33px;
			padding: 0;
			border-radius: var(--border-radius);
			background-color: var(--color-main-background);
			.content {
				height: 33px;
				width: 33px;
				background-image: url('../../img/memegen.logo.png');
				background-size: 33px 33px;
			}
		}
	}
}
</style>
