<?php

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Memegen\Service;

use Exception;
use OCA\Memegen\AppInfo\Application;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use Throwable;

class MemegenService {
	private array $memeTemplates;
	private ?ICache $serverCache;
	private IClient $client;

	public function __construct(
		private LoggerInterface $logger,
		IClientService $clientService,
		private IConfig $config,
		private IL10N $l10n,
		ICacheFactory $cacheFactory,
	) {
		$this->client = $clientService->newClient();
		$this->serverCache = $cacheFactory->isAvailable() ? $cacheFactory->createDistributed('memegen') : null;
	}

	/**
	 * Search the memeTemplates for matching memes based on the long version of the meme name.
	 * Return an array sorted based on the levenshtein distance (to allow for partial matches).
	 *
	 * @param string $query What to search for
	 * @param int $offset
	 * @param int $limit
	 * @return array request result
	 */
	public function searchMemes(string $query, int $offset = 0, int $limit = 5): array {
		$memeTemplates = $this->getMemeTemplates();
		#TODO: optimize search?
		# For now just brute force through the template list:
		$distanceArr = [];
		foreach ($memeTemplates as $memeShortName => $memeInfo) {

			$distanceArr[$memeShortName] = levenshtein(strtoupper($query), strtoupper($memeInfo['name']), 0, 1, 1);


		}

		asort($distanceArr);

		$distanceArr = array_slice($distanceArr, $offset, $limit, true);

		$result = [];
		foreach (array_keys($distanceArr) as $key) {
			$result[] = [
				'memeId' => $key,
				'alt' => $memeTemplates[$key]['name'],
				'blank_url' => $memeTemplates[$key]['blank_url'],
				'lines' => $memeTemplates[$key]['lines']
			];
		}

		return $result;

	}

	/**
	 * Get an up-to-date list of all meme templates from the memegen service.
	 *
	 * @return array|null
	 */
	private function getMemeTemplates(): ?array {
		$cachedTemplates = null;

		if ($this->serverCache !== null) {
			$cachedTemplates = $this->serverCache->get('memeTemplates');
		}

		if ($cachedTemplates !== null) {
			return $cachedTemplates;
		}

		try {
			$templateResponse = $this->client->get(Application::MEME_SERVICE_URL . '/templates/');
			$templateResponse = json_decode($templateResponse->getBody(), true);
			// Parse the response into a more usable format
			$templateResponse = array_map(function ($template) {
				return [
					'memeId' => $template['id'],
					'name' => $template['name'],
					'blank_url' => $template['blank'],
					'lines' => $template['lines'],
				];
			}, $templateResponse);

			//Use the memeId as the key for the array
			$templateResponse = array_combine(array_column($templateResponse, 'memeId'), $templateResponse);

			// Cache the templates for 24 hours
			if ($this->serverCache !== null) {
				$this->serverCache->set('memeTemplates', $templateResponse, 86400);
			}

			return $templateResponse;
		} catch (Exception|Throwable $e) {
			$this->logger->warning(
				'Memegen meme template request error: ' . $e->getMessage(),
				['app' => Application::APP_ID]
			);
			return null;
		}
	}


	/**
	 * Return all meme info contained within the memeTemplates array based on the meme name.
	 *
	 * @param string $memeName
	 * @return array|null
	 */
	public function getMemeInfo(string $memeName): ?array {
		$memeTemplates = $this->getMemeTemplates();

		if (!isset($memeTemplates[$memeName])) {
			return null;
		}

		return $memeTemplates[$memeName];
	}

	/**
	 * Use the memegen service to generate a meme image given a memeId and captions.
	 * Captions must be encoded using the escape characters specified at https://memegen.link/
	 *
	 * @param string $memeId
	 * @param array|null $captions
	 * @return array|null
	 */
	public function getMemeContent(string $memeId, ?array $captions): ?array {
		#TODO: Resize images as necessary

		$memeTemplates = $this->getMemeTemplates();

		if (isset($memeTemplates[$memeId])) {
			try {

				if ($captions === null) {
					$fullUrl = Application::MEME_SERVICE_URL . '/images/' . $memeId . '.jpg';
				} else {
					$fullUrl = Application::MEME_SERVICE_URL . '/images/' . $memeId;
					foreach ($captions as $caption) {
						if ($caption === '') {
							$fullUrl .= '/_';
						} else {
							$fullUrl .= '/' . $caption;
						}

					}
					$fullUrl .= '.jpg';
				}


				$memeResponse = $this->client->get($fullUrl);
				return [
					'body' => $memeResponse->getBody(),
					'headers' => $memeResponse->getHeaders(),
				];
			} catch (Exception|Throwable $e) {
				$this->logger->warning(
					'Memegen meme content request error: ' . $e->getMessage(),
					['app' => Application::APP_ID]
				);
				return null;
			}
		}
		return null;
	}
}
