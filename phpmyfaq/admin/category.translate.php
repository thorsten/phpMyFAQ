<?php

/**
 * Category translation frontend
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Rudi Ferrari <bookcrossers@gmx.de>
 * @copyright 2006-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-09-10
 */

use phpMyFAQ\Category;
use phpMyFAQ\Category\Permission;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\UserHelper;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

if ($user->perm->hasPermission($user->getUserId(), PermissionType::CATEGORY_EDIT->value)) {
    $category = new Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $category->getMissingCategories();

    $categoryPermission = new Permission($faqConfig);

    $userHelper = new UserHelper($user);

    $id = Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT);
    $header = sprintf(
        '%s %s: <em>%s</em>',
        Translation::get('ad_categ_trans_1'),
        Translation::get('ad_categ_trans_2'),
        $category->categoryName[$id]['name']
    );

    $selectedLanguage = Filter::filterInput(INPUT_GET, 'trlang', FILTER_SANITIZE_SPECIAL_CHARS, $faqLangCode);
    if ($selectedLanguage !== $faqLangCode) {
        $action = 'showcategory';
        $showcat = 'yes';
    } else {
        $action = 'updatecategory';
        $showcat = 'no';
    }

    $userPermission = $categoryPermission->get(Permission::USER, [$id]);
    $groupPermission = $categoryPermission->get(Permission::GROUP, [$id]);

    $templateVars = [
        'categoryName' => $category->categoryName[$id]['name'],
        'ad_categ_trans_1' => Translation::get('ad_categ_trans_1'),
        'ad_categ_trans_2' => Translation::get('ad_categ_trans_2'),
        'categoryId' => $id,
        'category' => $category->categoryName[$id],
        'showcat' => $showcat,
        'permLevel' => $faqConfig->get('security.permLevel'),
        'groupPermission' => $groupPermission[0],
        'userPermission' => $userPermission[0],
        'csrf' => Token::getInstance()->getTokenString('update-category'),
        'ad_categ_title' => Translation::get('ad_categ_titel'),
        'ad_categ_lang' => Translation::get('ad_categ_lang'),
        'langToTranslate' => $category->getCategoryLanguagesToTranslate($id, $selectedLanguage),
        'ad_categ_desc' => Translation::get('ad_categ_desc'),
        'ad_categ_owner' => Translation::get('ad_categ_owner'),
        'userOptions' => $userHelper->getAllUserOptions($category->categoryName[$id]['user_id']),
        'ad_categ_transalready' => Translation::get('ad_categ_transalready'),
        'langTranslated' => $category->getCategoryLanguagesTranslated($id),
        'ad_categ_translatecateg' => Translation::get('ad_categ_translatecateg')
    ];

    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
    $template = $twig->loadTemplate('@admin/content/category.translate.twig');

    echo $template->render($templateVars);

} else {
    require __DIR__ . '/no-permission.php';
}
