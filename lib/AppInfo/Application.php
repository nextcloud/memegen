<?php

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Memegen\AppInfo;

use OCA\Memegen\Listener\MemegenReferenceListener;
use OCA\Memegen\Reference\MemegenReferenceProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Collaboration\Reference\RenderReferenceEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'memegen';
	public const MEME_SERVICE_URL = 'https://api.memegen.link';

	public function __construct(/*array $urlParams = []*/) {
		parent::__construct(self::APP_ID /*,$urlParams*/);
	}

	#[\Override]
	public function register(IRegistrationContext $context): void {
		$context->registerReferenceProvider(MemegenReferenceProvider::class);
		$context->registerEventListener(RenderReferenceEvent::class, MemegenReferenceListener::class);
	}

	#[\Override]
	public function boot(IBootContext $context): void {
	}
}
