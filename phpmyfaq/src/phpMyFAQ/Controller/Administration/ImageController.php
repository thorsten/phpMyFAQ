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

        $jsonResponse = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();
        $uploadDir =  PMF_CONTENT_DIR . '/user/images/';
        $validFileExtensions = ['gif', 'jpg', 'jpeg', 'png'];
        $timestamp = time();

        if (!Token::getInstance()->verifyToken('edit-faq', $request->query->get('csrf'))) {
            $jsonResponse->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $jsonResponse->setData(['error' => Translation::get('err_NotAuth')]);
            $jsonResponse->send();
        }

        reset($_FILES);
        $temp = current($_FILES);
        if (is_uploaded_file($temp['tmp_name'])) {
            if (
                $request->server->get('HTTP_ORIGIN') !== null &&
                $request->server->get('HTTP_ORIGIN') . '/' === $configuration->getDefaultUrl()
            ) {
                $jsonResponse->headers->set('Access-Control-Allow-Origin', $request->server->get('HTTP_ORIGIN'));
            }

            // Sanitize input
            if (preg_match("/([^\w\s\d\-_~,;:\[\]\(\).])|([\.]{2,})/", (string) $temp['name'])) {
                $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
                return $jsonResponse;
            }

            // Verify extension
            if (!in_array(strtolower(pathinfo((string) $temp['name'], PATHINFO_EXTENSION)), $validFileExtensions)) {
                $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
                return $jsonResponse;
            }

            // Accept upload if there was no origin, or if it is an accepted origin
            $fileName = $timestamp . '_' . $temp['name'];
            move_uploaded_file($temp['tmp_name'], $uploadDir . $fileName);

            // Respond to the successful upload with JSON with the full URL of the uploaded image.
            $jsonResponse->setStatusCode(Response::HTTP_OK);
            $jsonResponse->setData(
                ['location' => $configuration->getDefaultUrl() . 'content/user/images/' . $fileName]
            );
        } else {
            $jsonResponse->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $jsonResponse;
    }
}
