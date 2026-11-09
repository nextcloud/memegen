/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import {
	configureNextcloud,
	runOcc,
	startNextcloud,
	stopNextcloud,
	waitOnNextcloud,
} from '@nextcloud/e2e-test-server/docker'

let stopping

/**
 * Exit once, with the exit code of whoever asks first.
 *
 * @param {number} exitCode the exit code of the script
 */
function stop(exitCode = 0) {
	stopping ??= (async () => {
		process.stderr.write('Stopping Nextcloud server…\n')
		// locally the container is kept, so the next run starts without a cold boot.
		// In CI, stopNextcloud() removes the container, and first saves the server log if NEXTCLOUD_E2E_LOG_FILE is set.
		if (process.env.CI) {
			await stopNextcloud()
		}
		process.exit(exitCode)
	})()
	return stopping
}

process.on('SIGTERM', () => stop())
process.on('SIGINT', () => stop())

try {
	// BRANCH is the server branch to test against, e.g. master or stable33.
	// Locally, a container that is still running is reused, whatever its branch, unless a newer server image was pulled.
	const ip = await startNextcloud(process.env.BRANCH ?? 'master', true, { exposePort: 8081 })
	await waitOnNextcloud(ip)
	await configureNextcloud(['memegen'])
	await runOcc(['config:system:set', 'no_unsupported_browser_warning', '--value', 'true', '--type', 'boolean'])
} catch (error) {
	// a setup that fails because a signal stopped the server is not worth a message
	if (!stopping) {
		console.error('Could not set up Nextcloud:', error)
	}
	// exit 1 instead of reporting ready, unless a signal already asked to stop
	await stop(1)
}

process.stdout.write('Nextcloud ready at http://localhost:8081\n')

// stay alive, so Playwright can stop the server through the SIGTERM handler
while (true) {
	await new Promise((resolve) => setTimeout(resolve, 5000))
}
