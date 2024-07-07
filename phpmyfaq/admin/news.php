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
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\News;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Template\FormatDateTwigExtension;
use phpMyFAQ\Template\IsoDateTwigExtension;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$news = new News($faqConfig);

$csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);

$templateVars = [
    'action' => $action,
    'permissionAddNews' => $user->perm->hasPermission($user->getUserId(), PermissionType::NEWS_ADD),
    'permissionEditNews' => $user->perm->hasPermission($user->getUserId(), PermissionType::NEWS_EDIT),
    'permissionDeleteNews' => $user->perm->hasPermission($user->getUserId(), PermissionType::NEWS_DELETE),
    'defaultUrl' => $faqConfig->getDefaultUrl(),
    'enableWysiwyg' => $faqConfig->get('main.enableWysiwygEditor'),
    'ad_news_add' => Translation::get('ad_news_add'),
    'csrfToken_saveNews' => Token::getInstance()->getTokenString('save-news'),
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
    'ad_news_updatesuc' => Translation::get('ad_news_updatesuc'),
    'msgDeleteNews' => Translation::get('msgDeleteNews'),
    'csrfToken_deleteNews' => Token::getInstance()->getTokenString('delete-news'),
    'csrfToken_updateNews' => Token::getInstance()->getTokenString('update-news'),
    'ad_entry_active' => Translation::get('ad_entry_active'),
    'csrfToken_activateNews' => Token::getInstance()->getTokenString('activate-news')
];

if ('add-news' == $action && $user->perm->hasPermission($user->getUserId(), PermissionType::NEWS_ADD)) {
    $templateVars = [
        ...$templateVars,
        'userEmail' => $user->getUserData('email'),
        'userName' => $user->getUserData('display_name')
    ];
} elseif ('news' == $action && $user->perm->hasPermission($user->getUserId(), PermissionType::NEWS_EDIT)) {
    $newsHeaders = $news->getHeader();

    $templateVars = [
        ...$templateVars,
        'news' => $newsHeaders,
    ];
} elseif ('edit-news' == $action && $user->perm->hasPermission($user->getUserId(), PermissionType::NEWS_EDIT)) {
    $id = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $newsData = $news->get($id, true);

    $newsId = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $oComment = new Comments($faqConfig);
    $comments = $oComment->getCommentsData($newsId, CommentType::NEWS);

    $templateVars = [
        ...$templateVars,
        'newsData' => $newsData,
        'newsDataContent' => (isset($newsData['content']) ? htmlspecialchars(
            (string)$newsData['content'],
            ENT_QUOTES
        ) : ''),
        'comments' => $comments,
        'newsId' => $newsId,
        'commentTypeNews' => CommentType::NEWS
    ];
} else {
    require __DIR__ . '/no-permission.php';
    exit();
}

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
$twig->addExtension(new IsoDateTwigExtension());
$twig->addExtension(new FormatDateTwigExtension());
$template = $twig->loadTemplate('./admin/content/news.twig');

echo $template->render($templateVars);
