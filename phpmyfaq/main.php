<?php
/**
* $Id: main.php,v 1.15 2006-09-19 21:28:32 matteo Exp $
*
* The main start page with the Top10 and the latest messages
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2002-08-23
* @copyright    (c) 2001-2006 phpMyFAQ Team
*
* The contents of this file are subject to the Mozilla Public License
* Version 1.1 (the "License""); you may not use this file except in
* compliance with the License. You may obtain a copy of the License at
* http://www.mozilla.org/MPL/
*
* Software distributed under the License is distributed on an "AS IS"
* basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
* License for the specific language governing rights and limitations
* under the License.
*/

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST]'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

require_once('inc/News.php');

$news = new PMF_News($db, $LANGCODE);

$archived = (isset($_GET['newsid']) && ('0' == $_GET['newsid']));
if ($archived) {
    $writeNewsHeader = $PMF_CONF['title'].$PMF_LANG['newsArchive'];
    $writeNewsRSS = '';
    $showAllNews = '<a href="'.$_SERVER['PHP_SELF'].'?'.$sids.'">'.$PMF_LANG['newsShowCurrent'].'</a>';
} else {
    $writeNewsHeader = $PMF_CONF['title'].$PMF_LANG['msgNews'];
    $writeNewsRSS = '<a href="feed/news/rss.php" target="_blank"><img id="newsRSS" src="images/rss.png" width="28" height="16" alt="RSS" /></a>';
    $showAllNews = '<a href="'.$_SERVER['PHP_SELF'].'?'.$sids.'newsid=0">'.$PMF_LANG['newsShowArchive'].'</a>';
}

$tpl->processTemplate('writeContent', array(
    'writeNewsHeader'       => $writeNewsHeader,
    'writeNewsRSS'          => $writeNewsRSS,
    'writeNews'             => $news->getNews($archived),
    'showAllNews'           => $showAllNews,
    'writeNumberOfArticles' => $PMF_LANG['msgHomeThereAre'].$faq->getNumberOfRecords($LANGCODE).$PMF_LANG['msgHomeArticlesOnline']));

$tpl->includeTemplate('writeContent', 'index');