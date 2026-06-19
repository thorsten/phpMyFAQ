<?php

/**
 * Bootstraps a phpMyFAQ instance
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-03-07
 */

declare(strict_types=1);

use phpMyFAQ\Bootstrapper;
use phpMyFAQ\System;
use Symfony\Component\HttpFoundation\RedirectResponse;

require __DIR__ . '/constants.php';
require __DIR__ . '/autoload.php';

$bootstrapper = new Bootstrapper();
$bootstrapper->run();

$faqConfig = $bootstrapper->getFaqConfig();
$db = $bootstrapper->getDb();
$request = $bootstrapper->getRequest();

//
// If the installed database is older than the current code base, the
// installation must be updated first. Redirect any front-facing request to the
// updater instead of running into fatal errors caused by an outdated schema
// (e.g. tables or columns added by a later release are not yet present).
// The updater itself, the installer, all REST endpoints and the admin login
// and upgrade pages are excluded to avoid redirect loops and to keep the
// recovery and update process reachable (see System::isUpdateExemptRequest()).
//
if ($faqConfig !== null) {
    $isUpdateExemptRequest = System::isUpdateExemptRequest(
        (string) $request->getScriptName(),
        (string) $request->getPathInfo(),
    );

    if (!$isUpdateExemptRequest && System::isUpdateNecessary((string) $faqConfig->get('main.currentVersion'))) {
        $response = new RedirectResponse((new System())->getSystemUri($faqConfig) . 'update/');
        $response->send();
        exit();
    }
}
