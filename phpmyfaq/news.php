<?php
/**
 * Shows the page with the news record and - when available - the user
 * comments
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
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2006-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2006-07-23
 */

use Symfony\Component\HttpFoundation\RedirectResponse;

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$captcha = new PMF_Captcha($faqConfig);
$comment = new PMF_Comment($faqConfig);

$captcha->setSessionId($sids);
if (!is_null($showCaptcha)) {
    $captcha->showCaptchaImg();
    exit;
}

$oNews  = new PMF_News($faqConfig);
$newsId = PMF_Filter::filterInput(INPUT_GET, 'newsid', FILTER_VALIDATE_INT);

if (is_null($newsId)) {
    RedirectResponse::create('http://'.$_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']))
        ->send();
    exit;
}

$faqsession->userTracking('news_view', $categoryId);

// Define the header of the page
$newsMainHeader = $faqConfig->get('main.titleFAQ') . $PMF_LANG['msgNews'];
$newsFeed       = '&nbsp;<a href="feed/news/rss.php" target="_blank"><img id="newsRSS" src="assets/img/feed.png" width="16" height="16" alt="RSS" /></a>';

// Get all data from the news record
$news = $oNews->getNewsEntry($newsId);

$newsContent = $news['content'];
$newsHeader  = $news['header'];

// Add Glossary entries
$oGlossary   = new PMF_Glossary($faqConfig);
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
    $date = new PMF_Date($faqConfig);
    $newsDate = sprintf('%s<span id="newsLastUpd">%s</span>',
        $PMF_LANG['msgLastUpdateArticle'],
        $date->format($news['date'])
    );
} else {
    $newsDate = '';
}

$captchaHelper = new PMF_Helper_Captcha($faqConfig);

$tpl->parse(
    'writeContent',
    array(
        'writeNewsHeader'     => $newsMainHeader,
        'writeNewsRSS'        => $newsFeed,
        'writeHeader'         => $newsHeader,
        'writeContent'        => $newsContent,
        'writeDateMsg'        => $newsDate,
        'msgAboutThisNews'    => $PMF_LANG['msgAboutThisNews'],
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
        'captchaFieldset'     => $captchaHelper->renderCaptcha($captcha, 'writecomment', $PMF_LANG['msgCaptcha'], $auth),
        'writeComments'       => $comment->getComments($newsId, PMF_Comment::COMMENT_TYPE_NEWS)
    )
);

$tpl->merge('writeContent', 'index');
