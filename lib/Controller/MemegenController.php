<?php

// SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Memegen\Controller;

use OCA\Memegen\Service\MemegenService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class MemegenController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private MemegenService $memegenService,
		private ?string $userId,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $memeId
	 * @param array|null $captions
	 * @return DataDisplayResponse
	 */
	public function getMemeContent(string $memeId, ?array $captions): DataDisplayResponse {


		$memeResponse = $this->memegenService->getMemeContent($memeId, $captions);
		if ($memeResponse !== null && isset($memeResponse['body'], $memeResponse['headers'])) {
			$response = new DataDisplayResponse(
				$memeResponse['body'],
				Http::STATUS_OK,
				['Content-Type' => $memeResponse['headers']['Content-Type'][0] ?? 'image/jpeg']
			);
			$response->cacheFor(60 * 60 * 24, false, true);
			return $response;
		}
		return new DataDisplayResponse('', Http::STATUS_BAD_REQUEST);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string|null $term Search term
	 * @param int|null $offset Offset for cosecutive searches
	 * @param int|null $limit The index of the last search result to return
	 * @return DataResponse
	 */
	public function search(?string $term = '', ?int $offset = null, ?int $limit = 20): DataResponse {

		$memeResults = $this->memegenService->searchMemes($term, $offset ?? 0, $limit ?? 20);
		$results = ['offset' => $offset + count($memeResults), 'entries' => $memeResults];

		return new DataResponse($results);
	}
}
