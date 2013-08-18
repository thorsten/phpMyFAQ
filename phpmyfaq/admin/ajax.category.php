<?php
/**
 * AJAX: handling of Ajax category calls
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
 * @copyright 2012-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-12-26
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$ajaxAction = PMF_Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);

switch($ajaxAction) {

    case 'getpermissions':

        $category = new PMF_Category($faqConfig, array(), false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);

        $ajaxData = PMF_Filter::filterInputArray(
            INPUT_POST,
            array(
                'categories' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'flags'  => FILTER_REQUIRE_SCALAR
                )
            )
        );

        if (empty($ajaxData['categories'])) {
            $categories = array(-1); // Access for all users and groups
        } else {
            $categories = explode(',', (int)$ajaxData['categories']);
        }

        echo json_encode(
            array(
                'user'  => $category->getPermissions('user', $categories),
                'group' => $category->getPermissions('group', $categories)
            ),
            JSON_NUMERIC_CHECK
        );

        break;
}