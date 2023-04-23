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
 * @copyright 2006-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-07-23
 */

use phpMyFAQ\Captcha\Captcha;
use phpMyFAQ\Captcha\Helper\CaptchaHelper;
use phpMyFAQ\Comments;
use phpMyFAQ\Date;
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Filter;
use phpMyFAQ\Glossary;
use phpMyFAQ\News;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\Request;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$captcha = Captcha::getInstance($faqConfig);
$captcha->setSessionId($sids);

$comment = new Comments($faqConfig);

if ($showCaptcha !== '') {
    $captcha->drawCaptchaImage();
    exit;
}

$request = Request::createFromGlobals();
$newsId = Filter::filterVar($request->query->get('newsid'), FILTER_VALIDATE_INT);

$oNews = new News($faqConfig);

try {
    $faqSession->userTracking('news_view', $newsId);
} catch (Exception) {
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
if (strlen((string) $news['link']) > 0) {
    $newsContent .= sprintf(
        '</p><p>%s<a href="%s" target="%s">%s</a>',
        Translation::get('msgInfo'),
        Strings::htmlentities($news['link']),
        $news['target'],
        Strings::htmlentities($news['linkTitle'])
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
    $commentMessage = sprintf(
        '<a href="#" data-bs-toggle="modal" data-bs-target="#pmf-modal-add-comment">%s</a>',
        Translation::get('newsWriteComment')
    );
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

$captchaHelper = CaptchaHelper::getInstance($faqConfig);

$template->parse(
    'mainPageContent',
    [
        'writeNewsHeader' => $newsMainHeader,
        'newsHeader' => Strings::htmlentities($newsHeader),
        'mainPageContent' => $newsContent,
        'writeDateMsg' => $newsDate,
        'msgAboutThisNews' => Translation::get('msgAboutThisNews'),
        'writeAuthor' => ($news['active'] && (!$expired)) ? Translation::get('msgAuthor') . ': ' .
            Strings::htmlentities($news['authorName']) : '',
        'editThisEntry' => $editThisEntry,
        'writeCommentMsg' => $commentMessage,
        'msgWriteComment' => Translation::get('newsWriteComment'),
        'newsId' => $newsId,
        'newsLang' => $news['lang'],
        'msgCommentHeader' => Translation::get('msgCommentHeader'),
        'msgNewContentName' => Translation::get('msgNewContentName'),
        'msgNewContentMail' => Translation::get('msgNewContentMail'),
        'defaultContentMail' => ($user->getUserId() > 0) ? $user->getUserData('email') : '',
        'defaultContentName' =>
            ($user->getUserId() > 0) ? Strings::htmlentities($user->getUserData('display_name')) : '',
        'msgYourComment' => Translation::get('msgYourComment'),
        'csrfInput' => Token::getInstance()->getTokenInput('add-comment'),
        'msgCancel' => Translation::get('ad_gen_cancel'),
        'msgNewContentSubmit' => Translation::get('msgNewContentSubmit'),
        'captchaFieldset' => $captchaHelper->renderCaptcha(
            $captcha,
            'writecomment',
            Translation::get('msgCaptcha'),
            $user->isLoggedIn()
        ),
        'renderComments' => $comment->getComments($newsId, CommentType::NEWS),
    ]
);
