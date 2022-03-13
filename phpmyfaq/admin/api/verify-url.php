<?php

/**
 * AJAX: verifyurl.
 *
 * Usage:
 *   index.php?uin=<uin>&action=ajax&ajax=verifyURL&id=<id>&artlang=<lang>
 *
 * Performs link verification when entries are shown in record.show.php
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * The Initial Developer of the Original Code is released for external use
 * with permission from NetJapan, Inc. IT Administration Group.
 *
 * @package phpMyFAQ
 * @author Minoru TODA <todam@netjapan.co.jp>
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2022 NetJapan, Inc. and phpMyFAQ Team
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2005-09-30
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\LinkVerifier;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$http = new HttpHelper();
$http->setContentType('text/html');
$http->addHeader();

$linkVerifier = new LinkVerifier($faqConfig, $user->getLogin());
if ($linkVerifier->isReady() === false) {
    if (count(ob_list_handlers()) > 0) {
        ob_clean();
    }
    $http->sendWithHeaders('disabled');
    exit();
}

$id = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$lang = Filter::filterInput(INPUT_GET, 'lang', FILTER_UNSAFE_RAW);

if (!(isset($id) && isset($lang))) {
    $http->setStatus(401);
    exit();
}

$faq->faqRecord = null;
$faq->getRecord($id);

if (!isset($faq->faqRecord['content'])) {
    $http->setStatus(401);
    exit();
}

if (count(ob_list_handlers()) > 0) {
    ob_clean();
}

$linkVerifier->parseString($faq->faqRecord['content']);
$linkVerifier->verifyURLs($faqConfig->getDefaultUrl());
$linkVerifier->markEntry($id, $lang);

$http->sendWithHeaders($linkVerifier->getLinkStateString());
