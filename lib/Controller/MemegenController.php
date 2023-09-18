<?php

namespace OCA\Memegen\Controller;

use OCA\Memegen\Service\MemegenService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class MemegenController extends Controller {

	private MemegenService $memegenService;
	private ?string $userId;

	public function __construct(string        $appName,
								IRequest      $request,
								MemegenService $memegenService,
								?string       $userId)
	{
		parent::__construct($appName, $request);
		$this->memegenService = $memegenService;
		$this->userId = $userId;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
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
	 */
	public function search(?string $term = '', ?int $offset = null, ?int $limit = 20): DataResponse {

		$memeResults = $this->memegenService->searchMemes($term, $offset ?? 0, $limit ?? 20);
		$results =  ['offset' => $offset + count($memeResults), 'entries' => $memeResults];

		return new DataResponse($results);
	}
}