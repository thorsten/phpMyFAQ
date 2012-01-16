<?php
/**
 * Shows the page with the news record and - when available - the user
 * comments
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
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2006-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2006-07-23
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$captcha = new PMF_Captcha($db, $Language);
$comment = new PMF_Comment();

$captcha->setSessionId($sids);
if (!is_null($showCaptcha)) {
    $captcha->showCaptchaImg();
    exit;
}

$oNews  = new PMF_News($db, $Language);
$newsId = PMF_Filter::filterInput(INPUT_GET, 'newsid', FILTER_VALIDATE_INT);

if (is_null($newsId)) {
    header('Location: http://'.$_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$faqsession->userTracking('news_view', $id);

// Define the header of the page
$newsMainHeader = $faqconfig->get('main.titleFAQ') . $PMF_LANG['msgNews'];
$newsFeed       = '&nbsp;<a href="feed/news/rss.php" target="_blank"><img id="newsRSS" src="images/feed.png" width="16" height="16" alt="RSS" /></a>';

// Get all data from the news record
$news = $oNews->getNewsEntry($newsId);

$newsContent = $news['content'];
$newsHeader  = $news['header'];

// Add Glossary entries
$oGlossary   = new PMF_Glossary();
$newsContent = $oGlossary->insertItemsIntoContent($newsContent);
$newsHeader  = $oGlossary->insertItemsIntoContent($newsHeader);
 
// Add information link if existing
if (strlen($news['link']) > 0) {
    $newsContent .= sprintf('</p><p>%s<a href="%s" target="%s">%s</a>',
        $PMF_LANG['msgInfo'],
        $news['link'],
        $news['target'],
        $news['linkTitle']);
}

// Show link to edit the news?
$editThisEntry = '';
if (isset($permission['editnews'])) {
    $editThisEntry = sprintf(
                        '<a href="%sadmin/index.php?action=news&amp;do=edit&amp;id=%d">%s</a>',
                        PMF_Link::getSystemRelativeUri('index.php'),
                        $newsId,
                        $PMF_LANG['ad_menu_news_edit']);
}

// Is the news item expired?
$expired = (date('YmdHis') > $news['dateEnd']);

// Does the user have the right to add a comment?
if ((!$news['active']) || (!$news['allowComments']) || $expired) {
    $commentMessage = $PMF_LANG['msgWriteNoComment'];
} else {
    $commentMessage = sprintf(
        '<a href="javascript:void(0);" onclick="javascript:$(\'#commentForm\').show();">%s</a>',
        $PMF_LANG['newsWriteComment']);
}

// date of news entry
if ($news['active'] && (!$expired)) {
    $newsDate = sprintf('%s<span id="newsLastUpd">%s</span>',
        $PMF_LANG['msgLastUpdateArticle'],
        PMF_Date::format($news['date'])
    );
} else {
    $newsDate = '';
}

// Set the template variables
$tpl->parse(
    'writeContent',
    array(
        'writeNewsHeader'     => $newsMainHeader,
        'writeNewsRSS'        => $newsFeed,
        'writeHeader'         => $newsHeader,
        'writeContent'        => $newsContent,
        'writeDateMsg'        => $newsDate,
        'writeAuthor'         => ($news['active'] && (!$expired)) ? $PMF_LANG['msgAuthor'] . ': ' . $news['authorName'] : '',
        'editThisEntry'       => $editThisEntry,
        'writeCommentMsg'     => $commentMessage,
        'msgWriteComment'     => $PMF_LANG['newsWriteComment'],
        'newsId'              => $newsId,
        'newsLang'            => $news['lang'],
        'msgCommentHeader'    => $PMF_LANG['msgCommentHeader'],
        'msgNewContentName'   => $PMF_LANG['msgNewContentName'],
        'msgNewContentMail'   => $PMF_LANG['msgNewContentMail'],
        'defaultContentMail'  => ($user instanceof PMF_User_CurrentUser) ? $user->getUserData('email') : '',
        'defaultContentName'  => ($user instanceof PMF_User_CurrentUser) ? $user->getUserData('display_name') : '',
        'msgYourComment'      => $PMF_LANG['msgYourComment'],
        'msgNewContentSubmit' => $PMF_LANG['msgNewContentSubmit'],
        'captchaFieldset'     => PMF_Helper_Captcha::getInstance()->renderCaptcha(
            $captcha,
            'writecomment',
            $PMF_LANG['msgCaptcha']
        ),
        'writeComments'       => $comment->getComments($newsId, PMF_Comment::COMMENT_TYPE_NEWS)
    )
);

$tpl->merge('writeContent', 'index');
