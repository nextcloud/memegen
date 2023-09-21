<?php

declare(strict_types=1);

namespace OCA\Memegen\Search;

use OCA\Memegen\Service\MemegenService;
use OCA\Memegen\AppInfo\Application;
use OCP\App\IAppManager;
use OCP\IL10N;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;

class MemegenSearchMemesProvider implements IProvider
{

	public function __construct(
		private IAppManager $appManager,
		private IL10N $l10n,
		private IConfig $config,
		private IURLGenerator $urlGenerator,
		private MemegenService $memegenService
	) {

	}

	public function getId(): string
	{
		return 'memegen-search-memes';
	}


	public function getName(): string
	{
		return $this->l10n->t('Memegen memes');
	}


	public function getOrder(string $route, array $routeParameters): int
	{
		if (strpos($route, Application::APP_ID . '.') === 0) {
			// Active app, prefer Memegen results
			return -1;
		}

		return 20;
	}

	/**
	 * Get memes sorted by a search based on the provided query term
	 * @param IUser $user
	 * @param ISearchQuery $query
	 * @return SearchResult
	 */
	public function search(IUser $user, ISearchQuery $query): SearchResult
	{
		/*if (!$this->appManager->isEnabledForUser(Application::APP_ID, $user)) {
				  return SearchResult::complete($this->getName(), []);
			  }*/

		$limit = $query->getLimit();
		$term = $query->getTerm();
		$offset = $query->getCursor();
		$offset = $offset ? intval($offset) : 0;

		$memes = $this->memegenService->searchMemes($term, $offset, $limit);

		$formattedResults = array_map(function (array $entry): SearchResultEntry {
			return new SearchResultEntry(
				$this->getThumbnailUrl($entry),
				$this->getMainText($entry),
				$this->getSubline($entry),
				$this->getUrl($entry),
				'',
				false
			);
		}, $memes);

		return SearchResult::paginated(
			$this->getName(),
			$formattedResults,
			$offset + $limit
		);
	}

	/**
	 * @param array $entry
	 * @return string
	 */
	protected function getMainText(array $entry): string
	{
		return $entry['alt'];
	}

	/**
	 * @param array $entry
	 * @return string
	 */
	protected function getSubline(array $entry): string
	{
		return '';
	}

	/**
	 * @param array $entry
	 * @return string
	 */
	protected function getUrl(array $entry): string
	{
		return $entry['blank_url'] ?? '';
	}

	/**
	 * @param array $entry
	 * @return string
	 */
	protected function getThumbnailUrl(array $entry): string
	{
		$memeId = $entry['memeId'] ?? '';
		return $this->urlGenerator->linkToRouteAbsolute(Application::APP_ID . '.memegen.getMemeContent', ['memeId' => $memeId]);
	}
}