<?php

/**
 * The User Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-02
 */

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route('api/user/data/update', methods: ['PUT'])]
    public function updateData(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        $jsonResponse = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($configuration);

        $data = json_decode($request->getContent());

        $csrfToken = Filter::filterVar($data->{'pmf-csrf-token'}, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance()->verifyToken('ucp', $csrfToken)) {
            $jsonResponse->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $jsonResponse->setData(['error' => Translation::get('ad_msg_noauth')]);
            return $jsonResponse;
        }

        $userId = Filter::filterVar($data->userid, FILTER_VALIDATE_INT);
        $userName = trim((string) Filter::filterVar($data->name, FILTER_SANITIZE_SPECIAL_CHARS));
        $email = Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL);
        $isVisible = Filter::filterVar($data->{'is_visible'}, FILTER_SANITIZE_SPECIAL_CHARS);
        $password = trim((string) Filter::filterVar($data->faqpassword, FILTER_SANITIZE_SPECIAL_CHARS));
        $confirm = trim((string) Filter::filterVar($data->faqpassword_confirm, FILTER_SANITIZE_SPECIAL_CHARS));
        $twoFactorEnabled = Filter::filterVar($data->twofactor_enabled ?? 'off', FILTER_SANITIZE_SPECIAL_CHARS);
        $deleteSecret = Filter::filterVar($data->newsecret ?? '', FILTER_SANITIZE_SPECIAL_CHARS);

        $isAzureAdUser = $user->getUserAuthSource() === 'azure';

        $secret = $deleteSecret === 'on' ? '' : $user->getUserData('secret');

        if ($userId !== $user->getUserId()) {
            $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
            $jsonResponse->setData(['error' => 'User ID mismatch!']);
            return $jsonResponse;
        }

        if (!$isAzureAdUser) {
            if ($password !== $confirm) {
                $jsonResponse->setStatusCode(Response::HTTP_CONFLICT);
                $jsonResponse->setData(['error' => Translation::get('ad_user_error_passwordsDontMatch')]);
                return $jsonResponse;
            }

            if (strlen($password) <= 7 || strlen($confirm) <= 7) {
                $jsonResponse->setStatusCode(Response::HTTP_CONFLICT);
                $jsonResponse->setData(['error' => Translation::get('ad_passwd_fail')]);
                return $jsonResponse;
            } else {
                $userData = [
                    'display_name' => $userName,
                    'email' => $email,
                    'is_visible' => $isVisible === 'on' ? 1 : 0,
                    'twofactor_enabled' => $twoFactorEnabled === 'on' ? 1 : 0,
                    'secret' => $secret
                ];

                $success = $user->setUserData($userData);

                foreach ($user->getAuthContainer() as $auth) {
                    if ($auth->setReadOnly()) {
                        continue;
                    }

                    if (!$auth->update($user->getLogin(), $password)) {
                        $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
                        $jsonResponse->setData(['error' => $auth->error()]);
                        $success = false;
                    } else {
                        $success = true;
                    }
                }
            }
        } else {
            $userData = [
                'is_visible' => $isVisible === 'on' ? 1 : 0,
                'twofactor_enabled' => $twoFactorEnabled === 'on' ? 1 : 0,
                'secret' => $secret
            ];

            $success = $user->setUserData($userData);
        }

        if ($success) {
            $jsonResponse->setStatusCode(Response::HTTP_OK);
            $jsonResponse->setData(['success' => Translation::get('ad_entry_savedsuc')]);
        } else {
            $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
            $jsonResponse->setData(['error' => Translation::get('ad_entry_savedfail')]);
        }

        return $jsonResponse;
    }
}
