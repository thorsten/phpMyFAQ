<?php
/**
 * The main start page with the Top10 and the latest messages
 *
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * @category  phpMyFAQ
 * @package   Frontend
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2002-08-23
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

require_once('inc/News.php');

$news = new PMF_News();

$archived        = PMF_Filter::filterInput(INPUT_GET, 'newsid', FILTER_VALIDATE_INT);
$writeNewsHeader = $faqconfig->get('main.titleFAQ');

if (!is_null($archived)) {
    $writeNewsHeader .= $PMF_LANG['newsArchive'];
    $writeNewsRSS     = '';
    $showAllNews      = sprintf('<a href="?%s">%s</a>', $sids, $PMF_LANG['newsShowCurrent']);
    $archived         = true;
} else {
    $writeNewsHeader .= ' ' . $PMF_LANG['msgNews'];
    $writeNewsRSS     = '&nbsp;<a href="feed/news/rss.php" target="_blank">' . 
                        '<img id="newsRSS" src="images/feed.png" width="16" height="16" alt="RSS" /></a>';
    $showAllNews      = sprintf('<a href="?%snewsid=0">%s</a>', $sids, $PMF_LANG['newsShowArchive']);
}

$tpl->parse('writeContent', array(
    'writeNewsHeader'       => $writeNewsHeader,
    'writeNewsRSS'          => $writeNewsRSS,
    'writeNews'             => $news->getNews($archived),
    'showAllNews'           => $showAllNews,
    'writeNumberOfArticles' => $plr->getMsg('plmsgHomeArticlesOnline', $faq->getNumberOfRecords($LANGCODE))));

$tpl->merge('writeContent', 'index');
