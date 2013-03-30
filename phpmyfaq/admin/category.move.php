<?php
/**
 * Select a category to move
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
 * @copyright 2004-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2004-04-29
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission["editcateg"]) {
    $id        = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT);
    $parent_id = PMF_Filter::filterInput(INPUT_GET, 'parent_id', FILTER_VALIDATE_INT);
    $category  = new PMF_Category($faqConfig, array(), false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $categories = $category->getAllCategories();

    $category->categories = null;
    unset($category->categories);
    $category->getCategories($parent_id, false);
    $category->buildTree($parent_id);

    $templateVars = array(
        'PMF_LANG'        => $PMF_LANG,
        'categoryName'    => $category->categories[$id]['name'],
        'categoryOptions' => array(),
        'csrfToken'       => $user->getCsrfTokenFromSession(),
        'id'              => $id
    );

    foreach ($category->categories as $cat) {
        if ($id != $cat["id"]) {
            $templateVars['categoryOptions'][$cat['id']] = $cat['name'];
        }
    }

    $twig->loadTemplate('category/move.twig')
        ->display($templateVars);

    unset($templateVars, $category, $id, $parent_id, $cat);
} else {
    require 'noperm.php';
}