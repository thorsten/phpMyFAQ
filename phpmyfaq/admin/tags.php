<?php

/**
 * Administration frontend for Tags.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-24
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Tags;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();
$user = CurrentUser::getCurrentUser($faqConfig);

$tagId = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
$template = $twig->loadTemplate('./admin/content/tags.twig');

$tags = new Tags($faqConfig);

if ('delete-tag' === $action) {
    $tagId = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($tags->delete($tagId)) {
        $deleteSuccess = true;
    } else {
        $deleteSuccess = false;
    }
}

$tagData = $tags->getAllTags();

$templateVars = [
    'adminHeaderTags' => Translation::get('ad_entry_tags'),
    'csrfToken' => Token::getInstance($container->get('session'))->getTokenInput('tags'),
    'isDelete' => 'delete-tag' === $action,
    'isDeleteSuccess' => $deleteSuccess ?? false,
    'msgDeleteSuccess' => Translation::get('ad_tag_delete_success'),
    'msgDeleteError' => Translation::get('ad_tag_delete_error'),
    'tags' => $tagData,
    'noTags' => Translation::get('ad_news_nodata'),
    'buttonEdit' => Translation::get('ad_user_edit'),
    'msgConfirm' => Translation::get('ad_user_del_3'),
    'buttonDelete' => Translation::get('msgDelete'),
];

echo $template->render($templateVars);

if (!$user->perm->hasPermission($user->getUserId(), PermissionType::FAQ_EDIT->value)) {
    require __DIR__ . '/no-permission.php';
}

