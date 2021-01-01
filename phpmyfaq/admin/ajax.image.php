<?php

/**
 * AJAX: handles an image upload from TinyMCE.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2015-10-10
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$http = new HttpHelper();
$http->setContentType('application/json');
$http->addHeader();

$ajaxAction = Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);
$upload = Filter::filterInput(INPUT_GET, 'image', FILTER_VALIDATE_INT);
$uploadedFile = isset($_FILES['upload']) ? $_FILES['upload'] : '';

$csrfOkay = true;
$csrfToken = Filter::filterInput(INPUT_GET, 'csrf', FILTER_SANITIZE_STRING);
if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
    $csrfOkay = false;
}
switch ($ajaxAction) {
    case 'upload':
        $uploadDir = '../images/';
        $validFileExtensions = ['gif', 'jpg', 'jpeg', 'png'];
        $timestamp = time();
        if ($csrfOkay) {
            reset($_FILES);
            $temp = current($_FILES);
            if (is_uploaded_file($temp['tmp_name'])) {
                if (isset($_SERVER['HTTP_ORIGIN'])) {
                    if ($_SERVER['HTTP_ORIGIN'] . '/' === $faqConfig->getDefaultUrl()) {
                        $http->sendCorsHeader();
                    }
                }

                // Sanitize input
                if (preg_match("/([^\w\s\d\-_~,;:\[\]\(\).])|([\.]{2,})/", $temp['name'])) {
                    $http->setStatus(400);
                    return;
                }

                // Verify extension
                if (!in_array(strtolower(pathinfo($temp['name'], PATHINFO_EXTENSION)), $validFileExtensions)) {
                    $http->setStatus(400);
                    return;
                }

                // Accept upload if there was no origin, or if it is an accepted origin
                $fileName = $timestamp . $temp['name'];
                move_uploaded_file($temp['tmp_name'], $uploadDir . $fileName);

                // Respond to the successful upload with JSON with the full URL of the uploaded image.
                $http->sendJsonWithHeaders(['location' => $faqConfig->getDefaultUrl() . 'images/' . $fileName]);
            } else {
                $http->setStatus(500);
            }
        } else {
            $http->setStatus(401);
        }
        break;
}
