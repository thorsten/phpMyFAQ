<?php
/**
* $Id: news.php,v 1.15 2008-05-23 13:06:06 thorstenr Exp $
*
* Shows the page with the news record and - when available - the user
* comments
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Matteo Scaramuccia <matteo@scaramuccia.com>
* @since        2006-07-23
* @copyright    (c) 2006-2007 phpMyFAQ Team
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
*/

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$captcha = new PMF_Captcha($sids);

if (isset($_GET['gen'])) {
    $captcha->showCaptchaImg();
    exit();
}

require_once('inc/News.php');

$oNews = new PMF_News($db, $LANGCODE);

if (isset($_REQUEST['newsid']) && is_numeric($_REQUEST['newsid'])) {
    $id = (int)$_REQUEST['newsid'];
} else {
    // Wrong access
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$faqsession->userTracking('news_view', $id);

// Define the header of the page
$writeNewsHeader = PMF_htmlentities($PMF_CONF['main.titleFAQ'], ENT_QUOTES, $PMF_LANG['metaCharset']).$PMF_LANG['msgNews'];
$writeNewsRSS = '<a href="feed/news/rss.php" target="_blank"><img id="newsRSS" src="images/rss.png" width="28" height="16" alt="RSS" /></a>';

// Get all data from the news record
$news = $oNews->getNewsEntry($id);

$content = $news['content'];
$header  = $news['header'];

// Add Glossary entries
$oG = new PMF_Glossary($db, $LANGCODE);
$content = $oG->insertItemsIntoContent($content);
$header  = $oG->insertItemsIntoContent($header);

// Show link to edit the news?
$editThisEntry = '';
if (isset($permission['editnews'])) {
    $editThisEntry = sprintf(
                        '<a href="%sadmin/index.php?action=news&amp;do=edit&amp;id=%d">%s</a>',
                        PMF_Link::getSystemRelativeUri('index.php'),
                        $id,
                        $PMF_LANG['ad_menu_news_edit']
                        );
}

// Is the news item expired?
$expired = (date('YmdHis') > $news['dateEnd']);

// Does the user have the right to add a comment?
if ((!$news['active']) || (!$news['allowComments']) || $expired) {
    $commentMessage = $PMF_LANG['msgWriteNoComment'];
} else {
    $oLink = new PMF_Link($_SERVER['PHP_SELF'].'?'.str_replace('&', '&amp;',$_SERVER['QUERY_STRING']));
    $oLink->itemTitle = $header;
    $commentHref = $oLink->toString().'#comment';
    $commentMessage = sprintf(
        '%s<a onclick="show(\'comment\');" href="%s">%s</a>',
        $PMF_LANG['msgYouCan'],
        $commentHref,
        $PMF_LANG['newsWriteComment']
    );
}

// Set the template variables
$tpl->processTemplate ("writeContent", array(
    'writeNewsHeader'           => $writeNewsHeader,
    'writeNewsRSS'              => $writeNewsRSS,
    'writeHeader'               => $header,
    'writeContent'              => $content,
    'writeDateMsg'              => ($news['active'] && (!$expired)) ? $PMF_LANG['msgLastUpdateArticle'].'<span id="newsLastUpd">'.$news['date'].'</span>' : '',
    'writeAuthor'               => ($news['active'] && (!$expired)) ? $PMF_LANG['msgAuthor'].$news['authorName'] : '',
    'editThisEntry'             => $editThisEntry,
    'writeCommentMsg'           => $commentMessage,
    'msgWriteComment'           => $PMF_LANG['newsWriteComment'],
    'writeSendAdress'           => $_SERVER['PHP_SELF'].'?'.$sids.'action=savecomment',
    'newsId'                    => $id,
    'newsLang'                  => $news['lang'],
    'msgCommentHeader'          => $PMF_LANG['msgCommentHeader'],
    'msgNewContentName'         => $PMF_LANG['msgNewContentName'],
    'msgNewContentMail'         => $PMF_LANG['msgNewContentMail'],
    'defaultContentMail'        => getEmailAddress(),
    'defaultContentName'        => getFullUserName(),
    'msgYourComment'            => $PMF_LANG['msgYourComment'],
    'msgNewContentSubmit'       => $PMF_LANG['msgNewContentSubmit'],
    'captchaFieldset'           => printCaptchaFieldset($PMF_LANG['msgCaptcha'], $captcha->printCaptcha('writecomment'), $captcha->caplength),
    'writeComments'             => $oNews->getComments($id)));

$tpl->includeTemplate('writeContent', 'index');
