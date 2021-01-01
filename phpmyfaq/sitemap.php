<?php

/**
 * Sitemap frontend.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thomas Zeithaml <seo@annatom.de>
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2005-08-21
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Sitemap;
use phpMyFAQ\Strings;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

try {
    $faqSession->userTracking('sitemap', 0);
} catch (Exception $e) {
    // @todo handle the exception
}

$letter = Filter::filterInput(INPUT_GET, 'letter', FILTER_SANITIZE_STRIPPED);
if (!is_null($letter) && (1 == Strings::strlen($letter))) {
    $currentLetter = strtoupper(Strings::substr($letter, 0, 1));
} else {
    $currentLetter = '';
}

$siteMap = new Sitemap($faqConfig);
$siteMap->setUser($currentUser);
$siteMap->setGroups($currentGroups);

$template->parse(
    'mainPageContent',
    [
        'pageHeader' => empty($currentLetter) ? $PMF_LANG['msgSitemap'] : $currentLetter,
        'renderLetters' => $siteMap->getAllFirstLetters(),
        'renderSiteMap' => $siteMap->getRecordsFromLetter($currentLetter),
        'writeCurrentLetter' => empty($currentLetter) ? $PMF_LANG['msgSitemap'] : $currentLetter,
    ]
);
