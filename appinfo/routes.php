<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com>
// SPDX-License-Identifier: AGPL-3.0-or-later


return [
	'routes' => [
		// we use this route to get the search result thumbnail and in the reference widget to get the image itself
		// this is a way to avoid allowing the page to access Memegen directly. We let the server get the image.
		['name' => 'memegen#getMemeContent', 'url' => '/memes/{memeId}', 'verb' => 'GET'],
		['name' => 'memegen#search', 'url' => '/search', 'verb' => 'GET'],
		// this route is used by the admin settings page to save the option values
		//['name' => 'config#setAdminConfig', 'url' => '/admin-config', 'verb' => 'PUT'],
	],
];
