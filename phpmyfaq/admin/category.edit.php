<?php
/**
 * Edits a category
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
 * @copyright 2003-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-03-10
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON'){
        $protocol = 'https';
    }
    header('Location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission['editcateg']) {
    $categoryId = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);
    $category   = new PMF_Category($faqConfig, array(), false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $categories      = $category->getAllCategories();
    $userPermission  = $category->getPermissions('user', array($categoryId));
    $groupPermission = $category->getPermissions('group', array($categoryId));

    $templateVars = array(
        'PMF_LANG'               => $PMF_LANG,
        'allGroups'              => $groupPermission[0] == -1,
        'allUsers'               => $userPermission[0] == -1,
        'categoryId'             => $categoryId,
        'categoryDescription'    => $categories[$categoryId]['description'],
        'categoryLanguage'       => $categories[$categoryId]['lang'],
        'categoryName'           => $categories[$categoryId]['name'],
        'csrfToken'              => $user->getCsrfTokenFromSession(),
        'parentId'               => $categories[$categoryId]['parent_id'],
        'renderGroupPermissions' => false,
        'restrictedGroups'       => $groupPermission[0] != -1,
        'restrictedUsers'        => $userPermission[0] != -1,
        'userOptionsOwner'       => $user->getAllUserOptions($categories[$categoryId]['user_id']),
        'userOptionsPermissions' => $user->getAllUserOptions($userPermission[0])
    );

    if ($faqConfig->get('security.permLevel') != 'basic') {
        $templateVars['renderGroupPermissions'] = true;
        $templateVars['groupOptions']           = $user->perm->getAllGroupsOptions($groupPermission);
    }

    $twig->loadTemplate('category/edit.twig')
        ->display($templateVars);

    unset($templateVars, $categoryId, $category, $categories, $userPermission, $groupPermission);
} else {
    require 'noperm.php';
}
