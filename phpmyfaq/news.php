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
 * @copyright 2006-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-07-23
 */

use phpMyFAQ\Captcha\Helper\CaptchaHelper;
use phpMyFAQ\Comments;
use phpMyFAQ\Date;
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Glossary;
use phpMyFAQ\Helper\CommentHelper;
use phpMyFAQ\Helper\FaqHelper;
use phpMyFAQ\News;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = $container->get('phpmyfaq.configuration');
$user = $container->get('phpmyfaq.user.current_user');

$faqSession = $container->get('phpmyfaq.session');
$faqSession->setCurrentUser($user);

$captcha = $container->get('phpmyfaq.captcha');
$captcha->setSessionId($sids);

$comment = new Comments($faqConfig);

$request = Request::createFromGlobals();
$newsId = Filter::filterVar($request->query->get('newsid'), FILTER_VALIDATE_INT);

$oNews = new News($faqConfig);

$faqSession->userTracking('news_view', $newsId);

// Define the header of the page
$newsMainHeader = $faqConfig->getTitle() . Translation::get('msgNews');

// Get all data from the news record
$news = $oNews->get($newsId);

$newsContent = $news['content'];
$newsHeader = $news['header'];

// Add Glossary entries
$oGlossary = new Glossary($faqConfig);
$newsContent = $oGlossary->insertItemsIntoContent($newsContent ?? '');
$newsHeader = $oGlossary->insertItemsIntoContent($newsHeader ?? '');

$helper = new FaqHelper($faqConfig);
$newsContent = $helper->cleanUpContent($newsContent);

// Add an information link if existing
if (strlen((string) $news['link']) > 0) {
    $newsContent .= sprintf(
        '</p><p>%s<a href="%s" target="%s">%s</a>',
        Translation::get('msgInfo'),
        Strings::htmlentities($news['link']),
        $news['target'],
        Strings::htmlentities($news['linkTitle'])
    );
}

// Show a link to edit the news?
$editThisEntry = '';
if ($user->perm->hasPermission($user->getUserId(), PermissionType::NEWS_EDIT)) {
    $editThisEntry = sprintf(
        '<a href="./admin/index.php?action=news&amp;do=edit&amp;id=%d">%s</a>',
        $newsId,
        Translation::get('ad_menu_news_edit')
    );
}

// Does the user have the right to add a comment?
if (
    (-1 === $user->getUserId() && !$faqConfig->get('records.allowCommentsForGuests')) ||
    (!$news['active']) || (!$news['allowComments'])
) {
    $commentMessage = Translation::get('msgWriteNoComment');
} else {
    $commentMessage = sprintf(
        '<a href="#" data-bs-toggle="modal" data-bs-target="#pmf-modal-add-comment">%s</a>',
        Translation::get('newsWriteComment')
    );
}

// date of news entry
if ($news['active']) {
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

$commentHelper = new CommentHelper();
$commentHelper->setConfiguration($faqConfig);

$comment = new Comments($faqConfig);
$comments = $comment->getCommentsData($newsId, CommentType::NEWS);

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates/');
$twigTemplate = $twig->loadTemplate('./news.twig');

$templateVars = [
    ... $templateVars,
    'writeNewsHeader' => $newsMainHeader,
    'newsHeader' => $newsHeader,
    'mainPageContent' => $newsContent,
    'writeDateMsg' => $newsDate,
    'msgAboutThisNews' => Translation::get('msgAboutThisNews'),
    'writeAuthor' => ($news['active']) ? Translation::get('msgAuthor') . ': ' . $news['authorName'] : '',
    'editThisEntry' => $editThisEntry,
    'writeCommentMsg' => $commentMessage,
    'msgWriteComment' => Translation::get('newsWriteComment'),
    'newsId' => $newsId,
    'newsLang' => $news['lang'],
    'msgCommentHeader' => Translation::get('msgCommentHeader'),
    'msgNewContentName' => Translation::get('msgNewContentName'),
    'msgNewContentMail' => Translation::get('msgNewContentMail'),
    'defaultContentMail' => ($user->getUserId() > 0) ? $user->getUserData('email') : '',
    'defaultContentName' => ($user->getUserId() > 0) ? $user->getUserData('display_name') : '',
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
    'renderComments' => $commentHelper->getComments($comments),
];

return $templateVars;
