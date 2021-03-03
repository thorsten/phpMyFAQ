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
 * @copyright 2012-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2012-12-26
 */

use phpMyFAQ\Category;
use phpMyFAQ\Category\CategoryOrder;
use phpMyFAQ\Category\CategoryPermission;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$ajaxAction = Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);
$csrfToken = Filter::filterInput(INPUT_GET, 'csrf', FILTER_SANITIZE_STRING);

$http = new HttpHelper();
$http->setContentType('application/json');
$http->addHeader();

switch ($ajaxAction) {
    case 'getpermissions':
        $categoryPermission = new CategoryPermission($faqConfig);
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
                'user' => $categoryPermission->get(CategoryPermission::USER, $categories),
                'group' => $categoryPermission->get(CategoryPermission::GROUP, $categories)
            ]
        );
        break;

    case 'update-order':
        if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
            $http->sendJsonWithHeaders(['error' => $PMF_LANG['err_NotAuth']]);
            $http->setStatus(401);
            return;
        }

        $category = new Category($faqConfig, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);

        $categoryOrder = new CategoryOrder($faqConfig);

        $rawData = Filter::filterInputArray(INPUT_POST, [
            'data' => [
                'filter' => FILTER_SANITIZE_STRING,
                'flags' => FILTER_REQUIRE_ARRAY,
            ],
        ]);

        /**
         * Callback function for array_filter()
         * @param $element
         * @return bool
         */
        function filterElement($element): bool
        {
            $element = ucfirst($element);
            return $element !== '';
        }

        $sortedData = array_filter($rawData['data'], 'filterElement');

        $order = 1;
        foreach ($sortedData as $key => $position) {
            $id = explode('-', $position);
            $currentPosition = $categoryOrder->getPositionById((int) $id[1]);

            if (!$currentPosition) {
                $categoryOrder->setPositionById((int) $id[1], (int) $order);
            } else {
                $categoryOrder->updatePositionById((int) $id[1], (int) $order);
            }
            $order++;
        }

        $http->sendJsonWithHeaders(
            []
        );

        break;
}
