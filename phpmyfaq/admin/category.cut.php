<?php
/**
 * Cuts out a category
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
 * @since     2003-12-25
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission["editcateg"]) {
    $category = new PMF_Category($faqConfig, array(), false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $category->buildTree();

    $id        = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);
    $parent_id = $category->categoryName[$id]['parent_id'];

    $templateVars = array(
        'PMF_LANG'             => $PMF_LANG,
        'categoryName'         => $category->categoryName[$id]['name'],
        'categoryOptions'      => array(),
        'csrfToken'            => $user->getCsrfTokenFromSession(),
        'displayMainCatOption' => $parent_id != 0,
        'id'                   => $id
    );

    foreach ($category->catTree as $cat) {
        $indent = str_repeat('…', $cat['indent']);
        if ($id != $cat['id']) {
            $templateVars['categoryOptions'][$cat['id']] = $indent . $cat['name'];
        }
    }

    $twig->loadTemplate('category/cut.twig')
        ->display($templateVars);

    unset($templateVars, $category, $id, $cat, $indent);
} else {
    require 'noperm.php';
}