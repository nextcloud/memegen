<?php

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Memegen\Reference;

use OC\Collaboration\Reference\ReferenceManager;
use OCA\Memegen\AppInfo\Application;
use OCA\Memegen\Service\MemegenService;
use OCP\Collaboration\Reference\ADiscoverableReferenceProvider;
use OCP\Collaboration\Reference\IReference;
use OCP\Collaboration\Reference\ISearchableReferenceProvider;
use OCP\Collaboration\Reference\Reference;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

class MemegenReferenceProvider extends ADiscoverableReferenceProvider implements ISearchableReferenceProvider {
	private const RICH_OBJECT_TYPE = Application::APP_ID . '_meme';

	public function __construct(
		private IConfig $config,
		private IL10N $l10n,
		private LoggerInterface $logger,
		private IURLGenerator $urlGenerator,
		private MemegenService $memegenService,
		private ReferenceManager $referenceManager,
		private ?string $userId,
	) {

	}

	#[\Override]
	public function getId(): string {
		return 'memegen_meme';
	}

	#[\Override]
	public function getTitle(): string {
		return $this->l10n->t('Memegen memes');
	}

	#[\Override]
	public function getOrder(): int {
		return 10;
	}

	#[\Override]
	public function getIconUrl(): string {
		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->imagePath(Application::APP_ID, 'app-dark.svg')
		);
	}

	#[\Override]
	public function getSupportedSearchProviderIds(): array {
		return ['memegen-search-memes'];

	}


	/**
	 * Given a referenceText, determine if this provider can resolve it.
	 *
	 * @param string $referenceText
	 * @return bool
	 */
	#[\Override]
	public function matchReference(string $referenceText): bool {

		$adminLinkPreviewEnabled = $this->config->getAppValue(Application::APP_ID, 'link_preview_enabled', '1') === '1';
		if (!$adminLinkPreviewEnabled) {
			return false;
		}
		return $this->parseMemeUrl($referenceText) !== null;
	}


	/**
	 * Given a referenceText, return a reference object.
	 *
	 * @param string $referenceText
	 * @return IReference|null
	 */
	#[\Override]
	public function resolveReference(string $referenceText): ?IReference {
		if ($this->matchReference($referenceText)) {

			$memeUrlInfo = $this->parseMemeUrl($referenceText);
			if ($memeUrlInfo !== null) {
				$memeInfo = $this->memegenService->getMemeInfo($memeUrlInfo['meme_id']);
				$reference = new Reference($referenceText);
				$reference->setTitle($memeInfo['alt'] ?? $this->l10n->t('Memegen meme'));
				$reference->setDescription('');

				$imageUrl = $this->urlGenerator->linkToRouteAbsolute(
					Application::APP_ID . '.memegen.getMemeContent',
					[
						'memeId' => $memeUrlInfo['meme_id'],
						'captions' => $memeUrlInfo['captions']
					]
				);

				$reference->setImageUrl($imageUrl);
				$memeInfo['proxied_url'] = $imageUrl;
				$reference->setRichObject(
					self::RICH_OBJECT_TYPE,
					array_merge($memeInfo, $memeUrlInfo)
				);
				return $reference;
			}
		}

		return null;
	}

	/**
	 * Given a url, parse it and return the meme id and captions. If the link doesn't match the proper format, return null.
	 *
	 * @param string $url
	 * @return array|null
	 */
	private function parseMemeUrl(string $url): ?array {
		preg_match('/^(?:https?:\/\/)?(?:www\.)?api\.memegen\.link\/images\/([^\/\?]+)\/([^\?]+)\.(gif|jpg|png)/i', $url, $matches);
		if (count($matches) > 3) {
			return ['captions' => explode('/', $matches[2]), 'meme_id' => $matches[1]];
		}

		preg_match('/^(?:https?:\/\/)?(?:www\.)?api\.memegen\.link\/images\/([^\/\?]+)\.(gif|jpg|png)$/i', $url, $matches);
		if (count($matches) > 2) {
			return ['captions' => null, 'meme_id' => $matches[1]];
		}
		return null;
	}

	#[\Override]
	public function getCachePrefix(string $referenceId): string {
		return $this->userId ?? '';
	}

	#[\Override]
	public function getCacheKey(string $referenceId): ?string {
		return $referenceId;
	}

	public function invalidateUserCache(string $userId): void {
		$this->referenceManager->invalidateCache($userId);
	}
}
