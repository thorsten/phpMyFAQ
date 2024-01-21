<?php

/**
 * The Admin Image Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-26
 */

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ImageController extends AbstractController
{
    #[Route('admin/api/content/images')]
    public function upload(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        $response = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();
        $uploadDir =  PMF_CONTENT_DIR . '/user/images/';
        $validFileExtensions = ['gif', 'jpg', 'jpeg', 'png'];
        $timestamp = time();

        if (!Token::getInstance()->verifyToken('edit-faq', $request->query->get('csrf'))) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            $response->send();
        }

        reset($_FILES);
        $temp = current($_FILES);
        if (is_uploaded_file($temp['tmp_name'])) {
            if ($request->server->get('HTTP_ORIGIN') !== null) {
                if ($request->server->get('HTTP_ORIGIN') . '/' === $configuration->getDefaultUrl()) {
                    $response->headers->set('Access-Control-Allow-Origin', $request->server->get('HTTP_ORIGIN'));
                }
            }

            // Sanitize input
            if (preg_match("/([^\w\s\d\-_~,;:\[\]\(\).])|([\.]{2,})/", $temp['name'])) {
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                return $response;
            }

            // Verify extension
            if (!in_array(strtolower(pathinfo($temp['name'], PATHINFO_EXTENSION)), $validFileExtensions)) {
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                return $response;
            }

            // Accept upload if there was no origin, or if it is an accepted origin
            $fileName = $timestamp . '_' . $temp['name'];
            move_uploaded_file($temp['tmp_name'], $uploadDir . $fileName);

            // Respond to the successful upload with JSON with the full URL of the uploaded image.
            $response->setStatusCode(Response::HTTP_OK);
            $response->setData(['location' => $configuration->getDefaultUrl() . 'content/user/images/' . $fileName]);
        } else {
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $response;
    }
}
