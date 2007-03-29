<?php
/**
 * $Id: main.php,v 1.21 2007-03-29 18:47:40 thorstenr Exp $
 *
 * The main start page with the Top10 and the latest messages
 *
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since       2002-08-23
 * @copyright   (c) 2002-2007 phpMyFAQ Team
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
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

require_once('inc/News.php');

$news = new PMF_News($db, $LANGCODE);

$archived        = (isset($_GET['newsid']) && ('0' == $_GET['newsid']));
$writeNewsHeader = PMF_htmlentities($PMF_CONF['main.titleFAQ'], ENT_QUOTES, $PMF_LANG['metaCharset']);

if ($archived) {
    $writeNewsHeader .= $PMF_LANG['newsArchive'];
    $writeNewsRSS = '';
    $showAllNews = '<a href="'.$_SERVER['PHP_SELF'].'?'.$sids.'">'.$PMF_LANG['newsShowCurrent'].'</a>';
} else {
    $writeNewsHeader .= $PMF_LANG['msgNews'];
    $writeNewsRSS = '<a href="feed/news/rss.php" target="_blank"><img id="newsRSS" src="images/rss.png" width="28" height="16" alt="RSS" /></a>';
    $showAllNews = '<a href="'.$_SERVER['PHP_SELF'].'?'.$sids.'newsid=0">'.$PMF_LANG['newsShowArchive'].'</a>';
}

$tpl->processTemplate('writeContent', array(
    'writeNewsHeader'       => $writeNewsHeader,
    'writeNewsRSS'          => $writeNewsRSS,
    'writeNews'             => $news->getNews($archived),
    'showAllNews'           => $showAllNews,
    'writeNumberOfArticles' => $PMF_LANG['msgHomeThereAre'] .
                               $faq->getNumberOfRecords($LANGCODE) .
                               $PMF_LANG['msgHomeArticlesOnline']));

$tpl->includeTemplate('writeContent', 'index');
