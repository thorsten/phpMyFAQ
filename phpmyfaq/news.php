<?php
/**
* $Id: news.php,v 1.3 2006-08-17 23:54:24 matteo Exp $
*
* Shows the page with the news record and - when available - the user
* comments
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Matteo Scaramuccia <matteo@scaramuccia.com>
* @since        2006-07-23
* @copyright    (c) 2006 phpMyFAQ Team
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
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$captcha = new PMF_Captcha($db, $sids, $pmf->language, $_SERVER['HTTP_USER_AGENT'], $_SERVER['REMOTE_ADDR']);

if (isset($_GET['gen'])) {
    $captcha->showCaptchaImg();
    exit();
}

require_once('inc/News.php');

$oNews = new PMF_News($db, $LANGCODE);

if (isset($_REQUEST['newsid']) && is_numeric($_REQUEST['newsid'])) {
    $id = (int)$_REQUEST['newsid'];
}
else {
    // Wrong access
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

Tracking('news_view', $id);

// Define the header of the page
$writeNewsHeader = $PMF_CONF['title'].$PMF_LANG['msgNews'];
$writeNewsRSS = '<a href="feed/news/rss.php" target="_blank"><img id="newsRSS" src="images/rss.png" width="28" height="16" alt="RSS" /></a>';

// Get all data from the news record
$news = $oNews->getNewsEntry($id);

$content = $news['content'];
$header  = $news['header'];

// Add Glossary entries
$oG = new PMF_Glossary($db, $LANGCODE);
$content = $oG->insertItemsIntoContent($content);
$header  = $oG->insertItemsIntoContent($header);

// Is the news item expired?
$expired = (date('YmdHis') > $news['dateEnd']);

// Does the user have the right to add a comment?
if ((!$news['active']) || (!$news['allowComments']) || $expired) {
    $commentMessage = $PMF_LANG['msgWriteNoComment'];
} else {
    $commentMessage = sprintf('%s<a onclick="show(\'comment\');" href="#comment">%s</a>',
        $PMF_LANG['msgYouCan'],
        $PMF_LANG['newsWriteComment']);
}

// Set the template variables
$tpl->processTemplate ("writeContent", array(
    'writeNewsHeader'           => $writeNewsHeader,
    'writeNewsRSS'              => $writeNewsRSS,
    'writeHeader'               => $header,
    'writeContent'              => $content,
    'writeDateMsg'              => ($news['active'] && (!$expired)) ? $PMF_LANG['msgLastUpdateArticle'].$news['date'] : '',
    'writeAuthor'               => ($news['active'] && (!$expired)) ? '<br />'.$PMF_LANG['msgAuthor'].$news['authorName'] : '',
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
