<?php

/**
 * The main start page with the start page categories, the Top 10 and the latest messages.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-08-23
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\News;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$news = new News($faqConfig);
$categoryHelper = new CategoryHelper();
$plr = new Plurals();

$request = Request::createFromGlobals();
$archived = Filter::filterVar($request->query->get('newsid'), FILTER_VALIDATE_INT);

if (!is_null($archived)) {
    $writeNewsHeader = Translation::get('newsArchive');
    $showAllNews = sprintf('<a href="?%s">%s</a>', $sids, Translation::get('newsShowCurrent'));
    $archived = true;
} else {
    $writeNewsHeader = ' ' . Translation::get('msgNews');
    $showAllNews = sprintf('<a href="?%snewsid=0">%s</a>', $sids, Translation::get('newsShowArchive'));
    $archived = false;
}

$startPageCategories = $category->getHomeCategories();
if ((is_countable($startPageCategories) ? count($startPageCategories) : 0) > 0) {
    $template->parseBlock(
        'mainPageContent',
        'startPageCategories',
        [
            'startPageCategoryDecks' => $categoryHelper->renderStartPageCategories($startPageCategories)
        ]
    );
}

$stickyRecordsParams = $faq->getStickyRecords();
if (!isset($stickyRecordsParams['error'])) {
    $template->parseBlock(
        'mainPageContent',
        'stickyRecordsList',
        [
            'stickyTitle' => $stickyRecordsParams['title'],
            'stickyUrl' => $stickyRecordsParams['url'],
            'stickyPreview' => $stickyRecordsParams['preview']
        ]
    );
}

// generate top ten list
if ($faqConfig->get('records.orderingPopularFaqs') == 'visits') {
    $param = 'visits';
} else {
    $param = 'voted';
}

$toptenParams = $faq->getTopTen($param);
if (!isset($toptenParams['error'])) {
    $template->parseBlock(
        'mainPageContent',
        'toptenList',
        [
            'toptenUrl' => $toptenParams['url'],
            'toptenTitle' => $toptenParams['title'],
            'toptenPreview' => $toptenParams['preview'],
            'toptenVisits' => $toptenParams[$param],
        ]
    );
} else {
    $template->parseBlock(
        'mainPageContent',
        'toptenListError',
        [
            'errorMsgTopTen' => $toptenParams['error'],
        ]
    );
}

$latestEntriesParams = $faq->getLatest();
if (!isset($latestEntriesParams['error'])) {
    $template->parseBlock(
        'mainPageContent',
        'latestEntriesList',
        [
            'latestEntriesUrl' => $latestEntriesParams['url'],
            'latestEntriesTitle' => $latestEntriesParams['title'],
            'latestEntriesPreview' => $latestEntriesParams['preview'],
            'latestEntriesDate' => $latestEntriesParams['date'],
        ]
    );
} else {
    $template->parseBlock(
        'mainPageContent',
        'latestEntriesListError',
        [
            'errorMsgLatest' => $latestEntriesParams['error']
        ]
    );
}

$template->parseBlock(
    'mainPageContent',
    'tagListSection',
    [
        'msgTags' => Translation::get('msgPopularTags'),
        'tagList' => $oTag->renderPopularTags(12)
    ]
);

$template->parse(
    'mainPageContent',
    [
        'pageHeader' => Strings::htmlspecialchars($faqConfig->getTitle()),
        'baseHref' => Strings::htmlentities($faqSystem->getSystemUri($faqConfig)),
        'stickyRecordsHeader' => Translation::get('stickyRecordsHeader'),
        'writeTopTenHeader' => Translation::get('msgTopTen'),
        'writeNewestHeader' => Translation::get('msgLatestArticles'),
        'writeNewsHeader' => $writeNewsHeader,
        'writeNews' => $news->getNews($archived),
        'showAllNews' => $showAllNews,
        'writeNumberOfArticles' => $plr->getMsg('plmsgHomeArticlesOnline', $faq->getNumberOfRecords($faqLangCode)),
        'formActionUrl' => '?' . $sids . 'action=search',
        'searchBox' => Translation::get('msgSearch'),
        'categoryId' => ($cat === 0) ? '%' : (int)$cat,
        'msgSearch' => sprintf(
            '<a class="help" href="%sindex.php?action=search">%s</a>',
            Strings::htmlentities($faqSystem->getSystemUri($faqConfig)),
            Translation::get('msgAdvancedSearch')
        )
    ]
);
