<?php

/**
 * The Login Controller for the REST API
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-30
 */

namespace phpMyFAQ\Api\Controller;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\UserAuthentication;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LoginController
{
    /**
     * @throws \JsonException
     */
    public function login(Request $request): JsonResponse
    {
        $response = new JsonResponse();
        $faqConfig = Configuration::getConfigurationInstance();

        $postBody = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);

        $faqUsername = Filter::filterVar($postBody->username, FILTER_SANITIZE_SPECIAL_CHARS);
        $faqPassword = Filter::filterVar($postBody->password, FILTER_SANITIZE_SPECIAL_CHARS);

        $user = new CurrentUser($faqConfig);
        $userAuth = new UserAuthentication($faqConfig, $user);
        try {
            $user = $userAuth->authenticate($faqUsername, $faqPassword);
            $response->setStatusCode(Response::HTTP_OK);
            $result = [
                'loggedin' => true
            ];
        } catch (Exception $e) {
            $faqConfig->getLogger()->error('Failed login: ' . $e->getMessage());
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $result = [
                'loggedin' => false,
                'error' => Translation::get('ad_auth_fail')
            ];
        }
        $response->setData($result);

        return $response;
    }
}
