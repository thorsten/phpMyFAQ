<?php

/**
 * XML, XHTML and PDF export - streamer page.
 *
 *
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-11-02
 */

use phpMyFAQ\Category;
use phpMyFAQ\Export;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\HttpStreamer;
use phpMyFAQ\Tags;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($user->perm->checkRight($user->getUserId(), 'export')) {
    $categoryId = Filter::filterInput(INPUT_POST, 'catid', FILTER_VALIDATE_INT);
    $downwards = Filter::filterInput(INPUT_POST, 'downwards', FILTER_VALIDATE_BOOLEAN, false);
    $inlineDisposition = Filter::filterInput(INPUT_POST, 'dispos', FILTER_SANITIZE_STRING);
    $type = Filter::filterInput(INPUT_POST, 'export-type', FILTER_SANITIZE_STRING, 'none');

    $faq = new Faq($faqConfig);
    $tags = new Tags($faqConfig);
    $category = new Category($faqConfig);
    $category->buildTree($categoryId);

    $export = Export::create($faq, $category, $faqConfig, $type);
    $content = $export->generate($categoryId, $downwards, $faqConfig->getLanguage()->getLanguage());

    // Stream the file content
    $oHttpStreamer = new HttpStreamer($type, $content);
    if ('inline' === $inlineDisposition) {
        $oHttpStreamer->send(HttpStreamer::EXPORT_DISPOSITION_INLINE);
    } else {
        $oHttpStreamer->send(HttpStreamer::EXPORT_DISPOSITION_ATTACHMENT);
    }
} else {
    echo $PMF_LANG['err_noArticles'];
}
