<?php
/**
 * Deletes a category
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
 * @since     2003-12-20
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission['delcateg']) {
    $category = new PMF_Category($faqConfig, array(), false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $categories = $category->getAllCategories();
    $id         = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);

    $twig->loadTemplate('category/delete.twig')
        ->display(
            array(
                'LANGCODE'            => $LANGCODE,
                'PMF_LANG'            => $PMF_LANG,
                'categoryDescription' => $categories[$id]['description'],
                'categoryName'        => $categories[$id]['name'],
                'csrfToken'           => $user->getCsrfTokenFromSession(),
                'id'                  => $id
            )
        );

    unset($category, $categories, $id);
} else {
    require 'noperm.php';
}