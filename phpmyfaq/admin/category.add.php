<?php
/**
 * Adds a new (sub-)category, a new sub-category inherits the permissions from
 * its parent category.
 *
 * PHP Version 5.4
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-12-20
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON'){
        $protocol = 'https';
    }
    header('Location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($user->perm->checkRight($user->getUserId(), 'addcateg')) {
    $parentId = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);

    $templateVars = array(
        'LANGCODE'               => $LANGCODE,
        'PMF_LANG'               => $PMF_LANG,
        'csrfToken'              => $user->getCsrfTokenFromSession(),
        'parentId'               => $parentId,
        'renderGroupPermissions' => false,
        'userOptions'            => $user->getAllUserOptions()
    );

    $category = new PMF_Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);

    if ($parentId > 0) {
        $userAllowed                            = $category->getPermissions('user', array($parentId));
        $groupsAllowed                          = $category->getPermissions('group', array($parentId));
        $templateVars['userAllowed']            = $userAllowed[0];
        $templateVars['groupsAllowed']          = $groupsAllowed;
        $templateVars['parentCategoryName']     = $category->categoryName[$parentId]['name'];
        $templateVars['parentCategoryLanguage'] = $languageCodes[PMF_String::strtoupper($category->categoryName[$parentId]['lang'])];
    } elseif ($faqConfig->get('security.permLevel') != 'basic') {
        $templateVars['renderGroupPermissions'] = true;
        $templateVars['groupOptions']           = $user->perm->getAllGroupsOptions([]);
    }

    $twig->loadTemplate('category/add.twig')
        ->display($templateVars);

    unset($templateVars, $parentId, $category, $userAllowed, $groupsAllowed);

} else {
    require 'noperm.php';
}