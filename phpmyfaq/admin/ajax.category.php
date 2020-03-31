<?php

/**
 * AJAX: handling of Ajax category calls.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2020 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2012-12-26
 */

use phpMyFAQ\Category;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$ajaxAction = Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);
$http = new HttpHelper();
$http->setContentType('application/json');
$http->addHeader();

switch ($ajaxAction) {

    case 'getpermissions':

        $category = new Category($faqConfig, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);

        $ajaxData = Filter::filterInputArray(
            INPUT_GET,
            [
                'categories' => [
                    'filter' => FILTER_SANITIZE_STRING,
                    'flags' => FILTER_REQUIRE_SCALAR,
                ],
            ]
        );

        if (empty($ajaxData['categories'])) {
            $categories = [-1]; // Access for all users and groups
        } else {
            $categories = explode(',', (int)$ajaxData['categories']);
        }

        $http->sendJsonWithHeaders(
            [
                'user' => $category->getPermissions('user', $categories),
                'group' => $category->getPermissions('group', $categories)
            ]
        );
        break;
}
