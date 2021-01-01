<?php

/**
 * FAQ overview page.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2015-09-27
 */

use phpMyFAQ\Helper\FaqHelper;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

try {
    $faqSession->userTracking('overview', 0);
} catch (Exception $e) {
    // @todo handle the exception
}

$faqHelper = new FaqHelper($faqConfig);

$faq->setUser($currentUser);
$faq->setGroups($currentGroups);

$template->parse(
    'mainPageContent',
    [
        'pageHeader' => $PMF_LANG['faqOverview'],
        'overview' => $faqHelper->createOverview($category, $faq, $faqLangCode),
    ]
);
