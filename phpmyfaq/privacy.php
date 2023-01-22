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
 * @copyright 2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-01-22
 */

use phpMyFAQ\Helper\HttpHelper;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$httpHelper = new HttpHelper();

$privacyUrl = $faqConfig->get('main.privacyURL');

if (strlen($privacyUrl) > 0) {
    $redirectUrl = $privacyUrl;
} else {
    $redirectUrl = $faqConfig->get('main.referenceURL');
}

$httpHelper->redirect($redirectUrl);
exit();
