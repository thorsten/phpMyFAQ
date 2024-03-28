<?php

/**
 * FAQ overview page.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-09-27
 */

use phpMyFAQ\Helper\FaqHelper;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqSession->userTracking('overview', 0);

$faqHelper = new FaqHelper($faqConfig);

$faq->setUser($currentUser);
$faq->setGroups($currentGroups);

$template->parse(
    'mainPageContent',
    [
        'pageHeader' => Translation::get('faqOverview'),
        'overview' => $faqHelper->createOverview($category, $faq, $faqLangCode),
    ]
);
