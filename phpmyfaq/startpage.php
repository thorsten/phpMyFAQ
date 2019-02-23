<?php

/**
 * The main start page with the Top10 and the latest messages.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2002-08-23
 */

use phpMyFAQ\Filter;
use phpMyFAQ\News;
use phpMyFAQ\Strings;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$news = new News($faqConfig);

$archived = Filter::filterInput(INPUT_GET, 'newsid', FILTER_VALIDATE_INT);
$writeNewsRSS = '';

if (!is_null($archived)) {
    $writeNewsHeader = $PMF_LANG['newsArchive'];
    $showAllNews = sprintf('<a href="?%s">%s</a>', $sids, $PMF_LANG['newsShowCurrent']);
    $archived = true;
} else {
    $writeNewsHeader = ' '.$PMF_LANG['msgNews'];
    if ($faqConfig->get('main.enableRssFeeds')) {
        $writeNewsRSS = '&nbsp;<a href="feed/news/rss.php" target="_blank">'.
            '<i class="fas fa-rss-square"></i></a>';
    }
    $showAllNews = sprintf('<a href="?%snewsid=0">%s</a>', $sids, $PMF_LANG['newsShowArchive']);
}

$startPageCategories = $category->getHomeCategories();
if (count($startPageCategories) > 0) {
    $template->parseBlock(
        'writeContent',
        'startPageCategories',
        [
            'categoryUrl' => $startPageCategories['url'],
            'categoryName' => $startPageCategories['name'],
            'categoryDescription' => $startPageCategories['description'],
            'categoryImage' => $startPageCategories['image']
        ]
    );
}

$stickyRecordsParams = $faq->getStickyRecords();
if (!isset($stickyRecordsParams['error'])) {
    $template->parseBlock(
        'writeContent',
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
        'writeContent',
        'toptenList',
        array(
            'toptenUrl' => $toptenParams['url'],
            'toptenTitle' => $toptenParams['title'],
            'toptenPreview' => $toptenParams['preview'],
            'toptenVisits' => $toptenParams[$param],
        )
    );
} else {
    $template->parseBlock(
        'writeContent',
        'toptenListError',
        array(
            'errorMsgTopTen' => $toptenParams['error'],
        )
    );
}

$latestEntriesParams = $faq->getLatest();
if (!isset($latestEntriesParams['error'])) {
    $template->parseBlock(
        'writeContent',
        'latestEntriesList',
        array(
            'latestEntriesUrl' => $latestEntriesParams['url'],
            'latestEntriesTitle' => $latestEntriesParams['title'],
            'latestEntriesPreview' => $latestEntriesParams['preview'],
            'latestEntriesDate' => $latestEntriesParams['date'],
        )
    );
} else {
    $template->parseBlock(
        'writeContent',
        'latestEntriesListError',
        [
            'errorMsgLatest' => $latestEntriesParams['error']
        ]
    );
}

$template->parseBlock(
    'writeContent',
    'tagListSection',
    [
        'msgTags' => $PMF_LANG['msgPopularTags'],
        'tagList' => $oTag->renderPopularTags(12)
    ]
);

$template->parse(
    'writeContent',
    [
        'baseHref' => $faqSystem->getSystemUri($faqConfig),
        'stickyRecordsHeader' => $PMF_LANG['stickyRecordsHeader'],
        'writeTopTenHeader' => $PMF_LANG['msgTopTen'],
        'rssFeedTopTen' => $rssFeedTopTen,
        'writeNewestHeader' => $PMF_LANG['msgLatestArticles'],
        'rssFeedLatest' => $rssFeedLatest,
        'writeNewsHeader' => $writeNewsHeader,
        'writeNewsRSS' => $writeNewsRSS,
        'writeNews' => $news->getNews($archived),
        'showAllNews' => $showAllNews,
        'writeNumberOfArticles' => $plr->getMsg('plmsgHomeArticlesOnline', $faq->getNumberOfRecords($LANGCODE)),
        'writeSendAdress' => '?'.$sids.'action=search',
        'searchBox' => $PMF_LANG['msgSearch'],
        'categoryId' => ($cat === 0) ? '%' : (int)$cat,
        'msgSearch' => sprintf(
            '<a class="help" href="%sindex.php?action=search">%s</a>',
            $faqSystem->getSystemUri($faqConfig),
            $PMF_LANG['msgAdvancedSearch']
        )
    ]
);

$template->parseBlock(
    'index',
    'breadcrumb',
    [
        'breadcrumbHeadline' => Strings::htmlspecialchars($faqConfig->get('main.titleFAQ'))
    ]
);
