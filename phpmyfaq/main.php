<?php

/**
 * The main start page with the Top10 and the latest messages.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-08-23
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$news = new PMF_News($faqConfig);

$archived = PMF_Filter::filterInput(INPUT_GET, 'newsid', FILTER_VALIDATE_INT);
$writeNewsHeader = $faqConfig->get('main.titleFAQ');
$writeNewsRSS = '';

if (!is_null($archived)) {
    $writeNewsHeader .= $PMF_LANG['newsArchive'];
    $showAllNews = sprintf('<a href="?%s">%s</a>', $sids, $PMF_LANG['newsShowCurrent']);
    $archived = true;
} else {
    $writeNewsHeader .= ' '.$PMF_LANG['msgNews'];
    if ($faqConfig->get('main.enableRssFeeds')) {
        $writeNewsRSS = '&nbsp;<a href="feed/news/rss.php" target="_blank">'.
            '<i aria-hidden="true" class="fa fa-rss"></i></a>';
    }
    $showAllNews = sprintf('<a href="?%snewsid=0">%s</a>', $sids, $PMF_LANG['newsShowArchive']);
}

$tpl->parseBlock(
    'writeContent',
    'tagListSection',
    [
        'msgTags' => $PMF_LANG['msgPopularTags'],
        'tagList' => $oTag->renderPopularTags(12)
    ]
);

$tpl->parse(
    'writeContent',
    [
        'writeNewsHeader' => $writeNewsHeader,
        'writeNewsRSS' => $writeNewsRSS,
        'writeNews' => $news->getNews($archived),
        'showAllNews' => $showAllNews,
        'writeNumberOfArticles' => $plr->getMsg('plmsgHomeArticlesOnline', $faq->getNumberOfRecords($LANGCODE)),
        'writeSendAdress' => '?'.$sids.'action=search',
        'searchBox' => $PMF_LANG['msgSearch'],
        'categoryId' => ($cat === 0) ? '%' : (int) $cat,
        'msgSearch' => sprintf(
            '<a class="help" href="%sindex.php?action=search">%s</a>',
            $faqSystem->getSystemUri($faqConfig),
            $PMF_LANG['msgAdvancedSearch']
        )
    ]
);

$tpl->parseBlock(
    'index',
    'breadcrumb',
    [
        'breadcrumbHeadline' => PMF_String::htmlspecialchars($faqConfig->get('main.titleFAQ'))
    ]
);