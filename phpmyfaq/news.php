<?php

/**
 * Shows the page with the news record and - when available - the user
 * comments.
 *
 * 
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2006-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2006-07-23
 */

use phpMyFAQ\Captcha;
use phpMyFAQ\Comment;
use phpMyFAQ\Date;
use phpMyFAQ\Filter;
use phpMyFAQ\Glossary;
use phpMyFAQ\Helper\CaptchaHelper;
use phpMyFAQ\Link;
use phpMyFAQ\News;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$captcha = new Captcha($faqConfig);
$comment = new Comment($faqConfig);

$captcha->setSessionId($sids);
if (!is_null($showCaptcha)) {
    $captcha->showCaptchaImg();
    exit;
}

$oNews = new News($faqConfig);
$newsId = Filter::filterInput(INPUT_GET, 'newsid', FILTER_VALIDATE_INT);

if (is_null($newsId)) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

try {
    $faqsession->userTracking('news_view', $newsId);
} catch (Exception $e) {
    // @todo handle the exception
}

// Define the header of the page
$newsMainHeader = $faqConfig->get('main.titleFAQ').$PMF_LANG['msgNews'];
if ($faqConfig->get('main.enableRssFeeds')) {
    $newsFeed = '&nbsp;<a href="feed/news/rss.php" target="_blank"><i class="fas fa-rss-square"></i></a>';
} else {
    $newsFeed = '';
}

// Get all data from the news record
$news = $oNews->getNewsEntry($newsId);

$newsContent = $news['content'];
$newsHeader = $news['header'];

// Add Glossary entries
$oGlossary = new Glossary($faqConfig);
$newsContent = $oGlossary->insertItemsIntoContent($newsContent);
$newsHeader = $oGlossary->insertItemsIntoContent($newsHeader);

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
if ($user->perm->checkRight($user->getUserId(), 'editnews')) {
    $editThisEntry = sprintf(
                        '<a href="%sadmin/index.php?action=news&amp;do=edit&amp;id=%d">%s</a>',
                        Link::getSystemRelativeUri('index.php'),
                        $newsId,
                        $PMF_LANG['ad_menu_news_edit']);
}

// Is the news item expired?
$expired = (date('YmdHis') > $news['dateEnd']);

// Does the user have the right to add a comment?
if ((-1 === $user->getUserId() && !$faqConfig->get('records.allowCommentsForGuests')) ||
    (!$news['active']) || (!$news['allowComments']) || $expired) {
    $commentMessage = $PMF_LANG['msgWriteNoComment'];
} else {
    $commentMessage = sprintf('<a href="#" class="show-comment-form">%s</a>', $PMF_LANG['newsWriteComment']);
}

// date of news entry
if ($news['active'] && (!$expired)) {
    $date = new Date($faqConfig);
    $newsDate = sprintf('%s<span id="newsLastUpd">%s</span>',
        $PMF_LANG['msgLastUpdateArticle'],
        $date->format($news['date'])
    );
} else {
    $newsDate = '';
}

$captchaHelper = new CaptchaHelper($faqConfig);

$tpl->parse(
    'writeContent',
    array(
        'writeNewsHeader' => $newsMainHeader,
        'writeNewsRSS' => $newsFeed,
        'writeHeader' => $newsHeader,
        'writeContent' => $newsContent,
        'writeDateMsg' => $newsDate,
        'msgAboutThisNews' => $PMF_LANG['msgAboutThisNews'],
        'writeAuthor' => ($news['active'] && (!$expired)) ? $PMF_LANG['msgAuthor'].': '.$news['authorName'] : '',
        'editThisEntry' => $editThisEntry,
        'writeCommentMsg' => $commentMessage,
        'msgWriteComment' => $PMF_LANG['newsWriteComment'],
        'newsId' => $newsId,
        'newsLang' => $news['lang'],
        'msgCommentHeader' => $PMF_LANG['msgCommentHeader'],
        'msgNewContentName' => $PMF_LANG['msgNewContentName'],
        'msgNewContentMail' => $PMF_LANG['msgNewContentMail'],
        'defaultContentMail' => ($user instanceof CurrentUser) ? $user->getUserData('email') : '',
        'defaultContentName' => ($user instanceof CurrentUser) ? $user->getUserData('display_name') : '',
        'msgYourComment' => $PMF_LANG['msgYourComment'],
        'msgNewContentSubmit' => $PMF_LANG['msgNewContentSubmit'],
        'captchaFieldset' => $captchaHelper->renderCaptcha($captcha, 'writecomment', $PMF_LANG['msgCaptcha'], $auth),
        'writeComments' => $comment->getComments($newsId, Comment::COMMENT_TYPE_NEWS),
    )
);

$tpl->parseBlock(
    'index',
    'breadcrumb',
    [
        'breadcrumbHeadline' => $newsMainHeader
    ]
);
