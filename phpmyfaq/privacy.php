<?php

/**
 * Redirect to the privacy page stored in the configuration
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-01-22
 */

use Symfony\Component\HttpFoundation\RedirectResponse;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = $container->get('phpmyfaq.configuration');

$privacyUrl = $faqConfig->get('main.privacyURL');
$redirectUrl = strlen((string) $privacyUrl) > 0 ? $privacyUrl : $faqConfig->get('main.referenceURL');

$response = new RedirectResponse($redirectUrl);
$response->send();
exit();
