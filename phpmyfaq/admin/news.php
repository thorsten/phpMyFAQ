<?php

/**
 * The main administration file for the news.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-23
 */

use phpMyFAQ\Comments;
use phpMyFAQ\Date;
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Entity\NewsMessage;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\News;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use Twig\Extension\DebugExtension;
use Twig\TwigFilter;
use phpMyFAQ\Configuration;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$news = new News($faqConfig);

$csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);

$templateVars = [
    'action' => $action,
    'permissionAddNews' => $user->perm->hasPermission($user->getUserId(), 'addnews'),
    'permissionEditNews' => $user->perm->hasPermission($user->getUserId(), 'editnews'),
    'permissionDeleteNews' => $user->perm->hasPermission($user->getUserId(), 'delnews'),
    'defaultUrl' => $faqConfig->getDefaultUrl(),
    'enableWysiwyg' => $faqConfig->get('main.enableWysiwygEditor'),
    'ad_news_add' => Translation::get('ad_news_add'),
    'csrfToken_save-news' => Token::getInstance()->getTokenString('save-news'),
    'ad_news_author_name' => Translation::get('ad_news_author_name'),
    'ad_news_set_active' => Translation::get('ad_news_set_active'),
    'ad_news_link_url' => Translation::get('ad_news_link_url'),
    'ad_news_link_title' => Translation::get('ad_news_link_title'),
    'ad_news_link_target' => Translation::get('ad_news_link_target'),
    'ad_news_link_window' => Translation::get('ad_news_link_window'),
    'ad_news_link_faq' => Translation::get('ad_news_link_faq'),
    'ad_news_link_parent' => Translation::get('ad_news_link_parent'),
    'selectLanguage' => LanguageHelper::renderSelectLanguage($faqLangCode, false, [], 'langTo'),
    'ad_news_expiration_window' => Translation::get('ad_news_expiration_window'),
    'ad_news_from' => Translation::get('ad_news_from'),
    'ad_news_to' => Translation::get('ad_news_to'),
    'ad_entry_back' => Translation::get('ad_entry_back'),
    'ad_menu_news_add' => Translation::get('ad_menu_news_add'),
    'ad_news_headline' => Translation::get('ad_news_headline'),
    'ad_news_date' => Translation::get('ad_news_date'),
    'ad_news_update' => Translation::get('ad_news_update'),
    'ad_news_delete' => Translation::get('ad_news_delete'),
    'ad_news_nodata' => Translation::get('ad_news_nodata'),
    'ad_news_edit' => Translation::get('ad_news_edit'),
    'ad_news_header' => Translation::get('ad_news_header'),
    'ad_news_text' => Translation::get('ad_news_text'),
    'ad_news_allowComments' => Translation::get('ad_news_allowComments'),
    'ad_entry_locale' => Translation::get('ad_entry_locale'),
    'ad_entry_comment' => Translation::get('ad_entry_comment'),
    'ad_entry_commentby' => Translation::get('ad_entry_commentby'),
    'newsCommentDate' => Translation::get('newsCommentDate'),
    'ad_news_insertfail' => Translation::get('ad_news_insertfail'),
    'msgNews' => Translation::get('msgNews'),
    'ad_news_data' => Translation::get('ad_news_data'),
    'ad_news_del' => Translation::get('ad_news_del'),
    'ad_news_nodelete' => Translation::get('ad_news_nodelete'),
    'ad_news_yesdelete' => Translation::get('ad_news_yesdelete'),
    'ad_news_delsuc' => Translation::get('ad_news_delsuc'),
    'ad_news_updatesuc' => Translation::get('ad_news_updatesuc')
];

$filterCreateIsoDate = new TwigFilter('createIsoDate', function ($string) {
    return Date::createIsoDate($string);
});

$filterFormatDate = new TwigFilter('formatDate', function ($string) {
    $faqConfig = Configuration::getConfigurationInstance();
    $date = new Date($faqConfig);
    return $date->format($string);
});

if ('add-news' == $action && $user->perm->hasPermission($user->getUserId(), 'addnews')) {
    $templateVars = [
        ...$templateVars,
        'userEmail' => $user->getUserData('email'),
        'userName' => $user->getUserData('display_name')
    ];
} elseif ('news' == $action && $user->perm->hasPermission($user->getUserId(), 'editnews')) {
    $newsHeader = $news->getHeader();
    $date = new Date($faqConfig);

    $templateVars = [
        ...$templateVars,
        'newsHeader' => $newsHeader,
    ];
} elseif ('edit-news' == $action && $user->perm->hasPermission($user->getUserId(), 'editnews')) {
    $id = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $newsData = $news->get($id, true);

    $dateStart = ($newsData['dateStart'] != '00000000000000' ? Date::createIsoDate($newsData['dateStart'], 'Y-m-d') : '');
    $dateEnd = ($newsData['dateEnd'] != '99991231235959' ? Date::createIsoDate($newsData['dateEnd'], 'Y-m-d') : '');

    $newsId = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $oComment = new Comments($faqConfig);
    $comments = $oComment->getCommentsData($newsId, CommentType::NEWS);

    $templateVars = [
        ...$templateVars,
        'newsData' => $newsData,
        'csrfToken_update-news' => Token::getInstance()->getTokenString('update-news'),
        'newsDataContent' => (isset($newsData['content']) ? htmlspecialchars(
            (string)$newsData['content'],
            ENT_QUOTES) : ''
        ),
        'dateStart' => $dateStart,
        'dateEnd' => $dateEnd,
        'comments' => $comments,
        'newsId' => $newsId,
        'commentTypeNews' => CommentType::NEWS
    ];
} elseif ('save-news' == $action && $user->perm->hasPermission($user->getUserId(), 'addnews')) {
    $dateStart = Filter::filterInput(INPUT_POST, 'dateStart', FILTER_SANITIZE_SPECIAL_CHARS);
    $dateEnd = Filter::filterInput(INPUT_POST, 'dateEnd', FILTER_SANITIZE_SPECIAL_CHARS);
    $header = Filter::filterInput(INPUT_POST, 'newsheader', FILTER_SANITIZE_SPECIAL_CHARS);
    $content = Filter::filterInput(INPUT_POST, 'news', FILTER_SANITIZE_SPECIAL_CHARS);
    $author = Filter::filterInput(INPUT_POST, 'authorName', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = Filter::filterInput(INPUT_POST, 'authorEmail', FILTER_VALIDATE_EMAIL);
    $active = Filter::filterInput(INPUT_POST, 'active', FILTER_SANITIZE_SPECIAL_CHARS);
    $comment = Filter::filterInput(INPUT_POST, 'comment', FILTER_SANITIZE_SPECIAL_CHARS);
    $link = Filter::filterInput(INPUT_POST, 'link', FILTER_SANITIZE_SPECIAL_CHARS);
    $linkTitle = Filter::filterInput(INPUT_POST, 'linkTitle', FILTER_SANITIZE_SPECIAL_CHARS);
    $newsLang = Filter::filterInput(INPUT_POST, 'langTo', FILTER_SANITIZE_SPECIAL_CHARS);
    $target = Filter::filterInput(INPUT_POST, 'target', FILTER_SANITIZE_SPECIAL_CHARS);

    $newsMessage = new NewsMessage();
    $newsMessage
        ->setLanguage($newsLang)
        ->setHeader($header)
        ->setMessage(html_entity_decode((string)$content))
        ->setAuthor($author)
        ->setEmail($email)
        ->setActive(!is_null($active))
        ->setComment(!is_null($comment))
        ->setDateStart(new DateTime($dateStart))
        ->setDateEnd(new DateTime($dateEnd))
        ->setLink($link ?? '')
        ->setLinkTitle($linkTitle ?? '')
        ->setLinkTarget($target ?? '')
        ->setCreated(new DateTime());

    $templateVars = [
        ...$templateVars,
        'createNewsStatus' => $news->create($newsMessage)
    ];
} elseif ('update-news' == $action && $user->perm->hasPermission($user->getUserId(), 'editnews')) {

    $newsId = Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $dateStart = Filter::filterInput(INPUT_POST, 'dateStart', FILTER_SANITIZE_SPECIAL_CHARS);
    $dateEnd = Filter::filterInput(INPUT_POST, 'dateEnd', FILTER_SANITIZE_SPECIAL_CHARS);
    $header = Filter::filterInput(INPUT_POST, 'newsheader', FILTER_SANITIZE_SPECIAL_CHARS);
    $content = Filter::filterInput(INPUT_POST, 'news', FILTER_SANITIZE_SPECIAL_CHARS);
    $author = Filter::filterInput(INPUT_POST, 'authorName', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = Filter::filterInput(INPUT_POST, 'authorEmail', FILTER_VALIDATE_EMAIL);
    $active = Filter::filterInput(INPUT_POST, 'active', FILTER_SANITIZE_SPECIAL_CHARS);
    $comment = Filter::filterInput(INPUT_POST, 'comment', FILTER_SANITIZE_SPECIAL_CHARS);
    $link = Filter::filterInput(INPUT_POST, 'link', FILTER_SANITIZE_SPECIAL_CHARS);
    $linkTitle = Filter::filterInput(INPUT_POST, 'linkTitle', FILTER_SANITIZE_SPECIAL_CHARS);
    $newsLang = Filter::filterInput(INPUT_POST, 'langTo', FILTER_SANITIZE_SPECIAL_CHARS);
    $target = Filter::filterInput(INPUT_POST, 'target', FILTER_SANITIZE_SPECIAL_CHARS);

    $newsMessage = new NewsMessage();
    $newsMessage
        ->setId($newsId)
        ->setLanguage($newsLang)
        ->setHeader($header)
        ->setMessage(html_entity_decode((string)$content))
        ->setAuthor($author)
        ->setEmail($email)
        ->setActive(!is_null($active))
        ->setComment(!is_null($comment))
        ->setDateStart(new DateTime($dateStart))
        ->setDateEnd(new DateTime($dateEnd))
        ->setLink($link ?? '')
        ->setLinkTitle($linkTitle ?? '')
        ->setLinkTarget($target ?? '')
        ->setCreated(new DateTime());

    $templateVars = [
        ...$templateVars,
        'statusUpdateNews' => $news->update($newsMessage)
    ];
} elseif ('delete-news' == $action && $user->perm->hasPermission($user->getUserId(), 'delnews')) {
    $precheck = Filter::filterInput(INPUT_POST, 'really', FILTER_SANITIZE_SPECIAL_CHARS, 'no');
    $deleteId = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    $templateVars = [
        ...$templateVars,
        'precheck' => $precheck,
        'deleteId' => $deleteId,
        'csrfToken_deleteNews' => Token::getInstance()->getTokenString('delete-news'),
        'verifyCsrf_deleteNews' => Token::getInstance()->verifyToken('delete-news', $csrfToken)
    ];

    if ('no' !== $precheck) {
        if (Token::getInstance()->verifyToken('delete-news', $csrfToken)) {
            $deleteId = Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $news->delete((int)$deleteId);
        }
    }
} else {
    require __DIR__ . '/no-permission.php';
    exit();
}

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
$twig->addExtension(new DebugExtension());
$twig->addFilter($filterFormatDate);
$twig->addFilter($filterCreateIsoDate);
$template = $twig->loadTemplate('./admin/content/news.twig');

echo $template->render($templateVars);
