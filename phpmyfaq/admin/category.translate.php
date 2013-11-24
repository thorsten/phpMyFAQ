<?php
/**
 * Translates a category
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Rudi Ferrari <bookcrossers@gmx.de>
 * @copyright 2006-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2006-09-10
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON'){
        $protocol = 'https';
    }
    header('Location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission["editcateg"]) {
    $category = new PMF_Category($faqConfig, array(), false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $category->getMissingCategories();
    $id               = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT);
    $user_permission  = $category->getPermissions('user', array($id));
    $group_permission = $category->getPermissions('group', array($id));
    $selectedLanguage = PMF_Filter::filterInput(INPUT_GET, 'trlang', FILTER_SANITIZE_STRING, $LANGCODE);

    $twig->loadTemplate('category/translate.twig')
        ->display(
            array(
                'PMF_LANG'        => $PMF_LANG,
                'categoryName'    => $category->categoryName[$id]['name'],
                'csrfToken'       => $user->getCsrfTokenFromSession(),
                'languageOptions' => $category->getCategoryLanguagesToTranslate($id, $selectedLanguage),
                'groupPermission' => $faqConfig->get('security.permLevel') !== 'basic' ? $group_permission[0] : -1,
                'id'              => $id,
                'parentId'        => $category->categoryName[$id]['parent_id'],
                'showcat'         => $selectedLanguage !== $LANGCODE,
                'translations'    => $category->getCategoryLanguagesTranslated($id),
                'userOptions'     => $user->getAllUserOptions($category->categoryName[$id]['user_id']),
                'userPermission'  => $user_permission[0]
            )
        );

    unset($category, $id, $user_permission, $group_permission, $selectedLanguage);
} else {
    require 'noperm.php';
}
