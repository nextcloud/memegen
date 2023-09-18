<?php
namespace OCA\Memegen\Reference;

use OCP\Collaboration\Reference\ADiscoverableReferenceProvider;
use Psr\Log\LoggerInterface;
use OCP\Collaboration\Reference\ISearchableReferenceProvider;
use OCP\Collaboration\Reference\Reference;
use OC\Collaboration\Reference\ReferenceManager;
use OCA\Memegen\AppInfo\Application;
use OCA\Memegen\Service\MemegenService;
use OCP\Collaboration\Reference\IReference;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use Exception;

class MemegenReferenceProvider extends ADiscoverableReferenceProvider implements ISearchableReferenceProvider {

	private const RICH_OBJECT_TYPE = Application::APP_ID . '_meme';

	private ?string $userId;
	private IConfig $config;
	private ReferenceManager $referenceManager;
	private IL10N $l10n;
	private IURLGenerator $urlGenerator;
	private MemegenService $memegenService;

	private LoggerInterface $logger;
	public function __construct(IConfig $config,
								IL10N $l10n,
								LoggerInterface $logger,
								IURLGenerator $urlGenerator,
								MemegenService $memegenService,
								ReferenceManager $referenceManager,
								?string $userId) {
		$this->userId = $userId;
		$this->config = $config;
		$this->referenceManager = $referenceManager;
		$this->l10n = $l10n;
		$this->urlGenerator = $urlGenerator;
		$this->memegenService = $memegenService;
		$this->logger = $logger;
	}

	public function getId(): string	{
		return 'memegen_meme';
	}

	public function getTitle(): string {
		return $this->l10n->t('Memegen memes');
	}

	public function getOrder(): int	{
		return 10;
	}

	public function getIconUrl(): string {
		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->imagePath(Application::APP_ID, 'app.svg')
		);
	}

	public function getSupportedSearchProviderIds(): array {
		return ['memegen-search-memes'];

	}


	public function matchReference(string $referenceText): bool {
		
		$adminLinkPreviewEnabled = $this->config->getAppValue(Application::APP_ID, 'link_preview_enabled', '1') === '1';
		if (!$adminLinkPreviewEnabled) {
			return false;
		}
		return $this->parseMemeUrl($referenceText) !== null;
	}


	public function resolveReference(string $referenceText): ?IReference {
		if ($this->matchReference($referenceText)) {
			
			$memeUrlInfo = $this->parseMemeUrl($referenceText);
			if ($memeUrlInfo !== null) {
				$memeInfo = $this->memegenService->getMemeInfo($memeUrlInfo['meme_id']);				
				$reference = new Reference($referenceText);
				$reference->setTitle($memeInfo['alt'] ?? $this->l10n->t('Memegen meme'));
				$reference->setDescription('');
				
				$imageUrl = $this->urlGenerator->linkToRouteAbsolute(Application::APP_ID . '.memegen.getMemeContent', ['memeId' => $memeUrlInfo['meme_id'], 'captions' => $memeUrlInfo['captions']]);
								
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

	private function parseMemeUrl(string $url): ?array {
		preg_match('/^(?:https?:\/\/)?(?:www\.)?api\.memegen\.link\/images\/([^\?]+\/)+([^\/\?\.]+)+\.(gif|jpg|png)/i', $url, $matches);
		if (count($matches) > 3) {
			return ['captions' => explode('/',$matches[1]), 'meme_id' => $matches[2]];
		}

		preg_match('/^(?:https?:\/\/)?(?:www\.)?api\.memegen\.link\/images\/([^\/\?]+)\.(gif|jpg|png)$/i', $url, $matches);
		if (count($matches) > 2) {
			return ['captions' => null, 'meme_id' => $matches[1]];
		}
		return null;
	}

	public function getCachePrefix(string $referenceId): string {
		return $this->userId ?? '';
	}

	public function getCacheKey(string $referenceId): ?string {
		return $referenceId;
	}

	public function invalidateUserCache(string $userId): void {
		$this->referenceManager->invalidateCache($userId);
	}
}
