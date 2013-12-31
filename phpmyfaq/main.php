<?php
/**
 * The main start page with the Top10 and the latest messages
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Frontend
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2002-08-23
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON'){
        $protocol = 'https';
    }
    header('Location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$news = new PMF_News($faqConfig);

$archived        = PMF_Filter::filterInput(INPUT_GET, 'newsid', FILTER_VALIDATE_INT);
$writeNewsHeader = $faqConfig->get('main.titleFAQ');

if (!is_null($archived)) {
    $writeNewsHeader .= $PMF_LANG['newsArchive'];
    $writeNewsRSS     = '';
    $showAllNews      = sprintf('<a href="?%s">%s</a>', $sids, $PMF_LANG['newsShowCurrent']);
    $archived         = true;
} else {
    $writeNewsHeader .= ' ' . $PMF_LANG['msgNews'];
    $writeNewsRSS     = '&nbsp;<a href="feed/news/rss.php" target="_blank">' . 
                        '<img id="newsRSS" src="assets/img/feed.png" width="16" height="16" alt="RSS" /></a>';
    $showAllNews      = sprintf('<a href="?%snewsid=0">%s</a>', $sids, $PMF_LANG['newsShowArchive']);
}

$tpl->parse('writeContent', array(
    'writeNewsHeader'       => $writeNewsHeader,
    'writeNewsRSS'          => $writeNewsRSS,
    'writeNews'             => $news->getNews($archived),
    'showAllNews'           => $showAllNews,
    'writeNumberOfArticles' => $plr->getMsg('plmsgHomeArticlesOnline', $faq->getNumberOfRecords($LANGCODE))));

$tpl->merge('writeContent', 'index');
