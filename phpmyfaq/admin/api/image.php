<?php

/**
 * Private phpMyFAQ Admin API: handles an image upload from TinyMCE.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-10-10
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
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
$upload = Filter::filterVar($request->query->get('image'), FILTER_VALIDATE_INT);
$uploadedFile = $_FILES['upload'] ?? '';

$csrfOkay = true;
$csrfToken = Filter::filterVar($request->query->get('csrf'), FILTER_SANITIZE_SPECIAL_CHARS);

if (!Token::getInstance()->verifyToken('edit-faq', $csrfToken)) {
    $csrfOkay = false;
}

if ($ajaxAction === 'upload') {
    $uploadDir = '../images/';
    $validFileExtensions = ['gif', 'jpg', 'jpeg', 'png'];
    $timestamp = time();
    if ($csrfOkay) {
        reset($_FILES);
        $temp = current($_FILES);
        if (is_uploaded_file($temp['tmp_name'])) {
            if ($request->server->get('HTTP_ORIGIN') !== null) {
                if ($request->server->get('HTTP_ORIGIN') . '/' === $faqConfig->getDefaultUrl()) {
                    $response->headers->set('Access-Control-Allow-Origin', $request->server->get('HTTP_ORIGIN'));
                }
            }

            // Sanitize input
            if (preg_match("/([^\w\s\d\-_~,;:\[\]\(\).])|([\.]{2,})/", $temp['name'])) {
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                return;
            }

            // Verify extension
            if (!in_array(strtolower(pathinfo($temp['name'], PATHINFO_EXTENSION)), $validFileExtensions)) {
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                return;
            }

            // Accept upload if there was no origin, or if it is an accepted origin
            $fileName = $timestamp . '_' . $temp['name'];
            move_uploaded_file($temp['tmp_name'], $uploadDir . $fileName);

            // Respond to the successful upload with JSON with the full URL of the uploaded image.
            $response->setStatusCode(Response::HTTP_OK);
            $response->setData(['location' => $faqConfig->getDefaultUrl() . 'images/' . $fileName]);
        } else {
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    } else {
        $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
    }

    $response->send();
}
