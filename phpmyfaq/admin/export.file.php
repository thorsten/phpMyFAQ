<?php

/**
 * JSON, HTML5 and PDF export - streamer page.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-11-02
 */

use phpMyFAQ\Category;
use phpMyFAQ\Export;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\HttpStreamer;
use phpMyFAQ\Tags;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

if ($user->perm->hasPermission($user->getUserId(), 'export')) {
    $categoryId = Filter::filterInput(INPUT_POST, 'catid', FILTER_VALIDATE_INT);
    $downwards = Filter::filterInput(INPUT_POST, 'downwards', FILTER_VALIDATE_BOOLEAN, false);
    $inlineDisposition = Filter::filterInput(INPUT_POST, 'disposition', FILTER_SANITIZE_SPECIAL_CHARS);
    $type = Filter::filterInput(INPUT_POST, 'export-type', FILTER_SANITIZE_SPECIAL_CHARS, 'none');

    $faq = new Faq($faqConfig);
    $tags = new Tags($faqConfig);
    $category = new Category($faqConfig, [], false);
    $category->buildCategoryTree($categoryId);

    try {
        $export = Export::create($faq, $category, $faqConfig, $type);
        $content = $export->generate($categoryId, $downwards, $faqConfig->getLanguage()->getLanguage());

        // Stream the file content
        $httpStreamer = new HttpStreamer($type, $content);
        if ('inline' === $inlineDisposition) {
            $httpStreamer->send(HttpStreamer::EXPORT_DISPOSITION_INLINE);
        } else {
            $httpStreamer->send(HttpStreamer::EXPORT_DISPOSITION_ATTACHMENT);
        }
    } catch (Exception $e) {
        echo $e->getMessage();
    }

} else {
    echo Translation::get('err_noArticles');
}
