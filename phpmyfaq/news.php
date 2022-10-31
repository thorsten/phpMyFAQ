<?php

/**
 * Shows the page with the news record and - when available - the user
 * comments.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2006-2022 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-07-23
 */

use phpMyFAQ\Captcha;
use phpMyFAQ\Comments;
use phpMyFAQ\Date;
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Filter;
use phpMyFAQ\Glossary;
use phpMyFAQ\Helper\CaptchaHelper;
use phpMyFAQ\News;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$captcha = new Captcha($faqConfig);
$comment = new Comments($faqConfig);

$captcha->setSessionId($sids);
if (!is_null($showCaptcha)) {
    try {
        $captcha->drawCaptchaImage();
    } catch (Exception $e) {
        // handle exception
    }
    exit;
}

$oNews = new News($faqConfig);
$newsId = Filter::filterInput(INPUT_GET, 'newsid', FILTER_VALIDATE_INT);

if (is_null($newsId)) {
    $http->setStatus(404);
}

try {
    $faqSession->userTracking('news_view', $newsId);
} catch (Exception $e) {
    // @todo handle the exception
}

// Define the header of the page
$newsMainHeader = $faqConfig->getTitle() . Translation::get('msgNews');

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
    $newsContent .= sprintf(
        '</p><p>%s<a href="%s" target="%s">%s</a>',
        Translation::get('msgInfo'),
        $news['link'],
        $news['target'],
        $news['linkTitle']
    );
}

// Show link to edit the news?
$editThisEntry = '';
if ($user->perm->hasPermission($user->getUserId(), 'editnews')) {
    $editThisEntry = sprintf(
        '<a href="./admin/index.php?action=news&amp;do=edit&amp;id=%d">%s</a>',
        $newsId,
        Translation::get('ad_menu_news_edit')
    );
}

// Is the news item expired?
$expired = (date('YmdHis') > $news['dateEnd']);

// Does the user have the right to add a comment?
if (
    (-1 === $user->getUserId() && !$faqConfig->get('records.allowCommentsForGuests')) ||
    (!$news['active']) || (!$news['allowComments']) || $expired
) {
    $commentMessage = Translation::get('msgWriteNoComment');
} else {
    $commentMessage = sprintf('<a href="#" class="show-comment-form">%s</a>', Translation::get('newsWriteComment'));
}

// date of news entry
if ($news['active'] && (!$expired)) {
    $date = new Date($faqConfig);
    $newsDate = sprintf(
        '%s<span id="newsLastUpd">%s</span>',
        Translation::get('msgLastUpdateArticle'),
        $date->format($news['date'])
    );
} else {
    $newsDate = '';
}

$captchaHelper = new CaptchaHelper($faqConfig);

$template->parse(
    'mainPageContent',
    [
        'writeNewsHeader' => $newsMainHeader,
        'writeHeader' => $newsHeader,
        'mainPageContent' => $newsContent,
        'writeDateMsg' => $newsDate,
        'msgAboutThisNews' => Translation::get('msgAboutThisNews'),
        'writeAuthor' => ($news['active'] && (!$expired)) ? Translation::get('msgAuthor') . ': ' . $news['authorName'] : '',
        'editThisEntry' => $editThisEntry,
        'writeCommentMsg' => $commentMessage,
        'msgWriteComment' => Translation::get('newsWriteComment'),
        'newsId' => $newsId,
        'newsLang' => $news['lang'],
        'msgCommentHeader' => Translation::get('msgCommentHeader'),
        'msgNewContentName' => Translation::get('msgNewContentName'),
        'msgNewContentMail' => Translation::get('msgNewContentMail'),
        'defaultContentMail' => ($user instanceof CurrentUser) ? $user->getUserData('email') : '',
        'defaultContentName' => ($user instanceof CurrentUser) ? $user->getUserData('display_name') : '',
        'msgYourComment' => Translation::get('msgYourComment'),
        'msgNewContentSubmit' => Translation::get('msgNewContentSubmit'),
        'captchaFieldset' => $captchaHelper->renderCaptcha($captcha, 'writecomment', Translation::get('msgCaptcha'), $auth),
        'renderComments' => $comment->getComments($newsId, CommentType::NEWS),
    ]
);

$template->parseBlock(
    'index',
    'breadcrumb',
    [
        'breadcrumbHeadline' => $newsMainHeader
    ]
);
