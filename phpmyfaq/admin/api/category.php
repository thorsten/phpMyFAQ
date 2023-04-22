<?php

/**
 * Private phpMyFAQ Admin API: handling of REST category calls.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-12-26
 */

use phpMyFAQ\Category;
use phpMyFAQ\Category\CategoryOrder;
use phpMyFAQ\Category\CategoryPermission;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

//
// Create Request & Response
//
$response = new JsonResponse();
$request = Request::createFromGlobals();

$ajaxAction = Filter::filterVar($request->query->get('ajaxaction'), FILTER_SANITIZE_SPECIAL_CHARS);
$csrfToken = Filter::filterVar($request->query->get('csrf'), FILTER_SANITIZE_SPECIAL_CHARS);

switch ($ajaxAction) {
    case 'getpermissions':
        $categoryPermission = new CategoryPermission($faqConfig);
        $ajaxData = Filter::filterInputArray(
            INPUT_GET,
            [
                'categories' => [
                    'filter' => FILTER_SANITIZE_SPECIAL_CHARS,
                    'flags' => FILTER_REQUIRE_SCALAR,
                ],
            ]
        );

        if (empty($ajaxData['categories'])) {
            $categories = [-1]; // Access for all users and groups
        } else {
            $categories = explode(',', (int)$ajaxData['categories']);
        }

        $response->setData(
            [
                'user' => $categoryPermission->get(CategoryPermission::USER, $categories),
                'group' => $categoryPermission->get(CategoryPermission::GROUP, $categories)
            ]
        );
        $response->send();
        break;

    case 'update-order':
        $postData = json_decode(file_get_contents('php://input', true));

        if (!Token::getInstance()->verifyToken('category', $postData->csrf)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            $response->send();
            exit();
        }

        $category = new Category($faqConfig, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);

        $categoryOrder = new CategoryOrder($faqConfig);

        /**
         * Callback function for array_filter()
         * @param $element
         * @return bool
         */
        function filterElement($element): bool
        {
            return is_numeric($element) ?? (int)$element;
        }

        $sortedData = array_filter($postData->order, 'filterElement');

        $order = 1;
        foreach ($sortedData as $categoryId) {
            $currentPosition = $categoryOrder->getPositionById((int) $categoryId);

            if (!$currentPosition) {
                $categoryOrder->setPositionById((int) $categoryId, $order);
            } else {
                $categoryOrder->updatePositionById((int) $categoryId, $order);
            }
            $order++;
        }

        $response->setData(
            ['success' => Translation::get('ad_categ_save_order')]
        );
        $response->send();
        break;
}
