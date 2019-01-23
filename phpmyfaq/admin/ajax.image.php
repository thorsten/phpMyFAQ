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
 * @copyright 2015-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2015-10-10
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$http = new HttpHelper();

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
        if ($csrfOkay) {
            reset($_FILES);
            $temp = current($_FILES);
            if (is_uploaded_file($temp['tmp_name'])) {
                if (isset($_SERVER['HTTP_ORIGIN'])) {
                    if ($_SERVER['HTTP_ORIGIN'] . '/' === $faqConfig->getDefaultUrl()) {
                        $http->sendCorsHeader();
                    } else {
                        $http->sendStatus(403);
                        return;
                    }
                }

                // Sanitize input
                if (preg_match("/([^\w\s\d\-_~,;:\[\]\(\).])|([\.]{2,})/", $temp['name'])) {
                    $http->sendStatus(400);
                    return;
                }

                // Verify extension
                if (!in_array(strtolower(pathinfo($temp['name'], PATHINFO_EXTENSION)), $validFileExtensions)) {
                    $http->sendStatus(400);
                    return;
                }

                // Accept upload if there was no origin, or if it is an accepted origin
                $fileToWrite = $uploadDir . $temp['name'];
                move_uploaded_file($temp['tmp_name'], $fileToWrite);

                // Respond to the successful upload with JSON.
                $http->sendJsonWithHeaders(['location' => $fileToWrite]);
            } else {
                $http->sendStatus(500);
            }
        } else {
            $http->sendStatus(401);
        }
        break;
}
