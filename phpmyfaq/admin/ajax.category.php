<?php

/**
 * AJAX: handling of Ajax category calls.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2012-12-26
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$ajaxAction = PMF_Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);

switch ($ajaxAction) {

    case 'getpermissions':

        $category = new PMF_Category($faqConfig, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);

        $ajaxData = PMF_Filter::filterInputArray(
            INPUT_POST,
            array(
                'categories' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'flags' => FILTER_REQUIRE_SCALAR,
                ),
            )
        );

        if (empty($ajaxData['categories'])) {
            $categories = array(-1); // Access for all users and groups
        } else {
            $categories = explode(',', (int) $ajaxData['categories']);
        }

        echo json_encode(
            array(
                'user' => $category->getPermissions('user', $categories),
                'group' => $category->getPermissions('group', $categories),
            ),
            JSON_NUMERIC_CHECK
        );

        break;
}
