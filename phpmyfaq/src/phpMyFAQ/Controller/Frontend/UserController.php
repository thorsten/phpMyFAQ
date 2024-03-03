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
use phpMyFAQ\Mail;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Utils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
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

    /**
     * @throws Exception
     */
    #[Route('api/user/data/update', methods: ['PUT'])]
    public function updatePassword(Request $request): JsonResponse
    {
        $jsonResponse = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($configuration);

        $data = json_decode($request->getContent());

        $username = trim((string) Filter::filterVar($data->username, FILTER_SANITIZE_SPECIAL_CHARS));
        $email = trim((string) Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL));
        if ($username !== '' && $username !== '0' && ($email !== '' && $email !== '0')) {
            $loginExist = $user->getUserByLogin($username);

            if ($loginExist && ($email == $user->getUserData('email'))) {
                try {
                    $newPassword = $user->createPassword();
                } catch (Exception $exception) {
                    $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
                    $jsonResponse->setData(['error' => $exception->getMessage()]);
                    return $jsonResponse;
                }

                try {
                    $user->changePassword($newPassword);
                } catch (\Exception $exception) {
                    $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
                    $jsonResponse->setData(['error' => $exception->getMessage()]);
                    return $jsonResponse;
                }

                $text = Translation::get('lostpwd_text_1') .
                    sprintf('<br><br>Username: %s', $username) .
                    sprintf('<br>New Password: %s<br><br>', $newPassword) .
                    Translation::get('lostpwd_text_2');

                $mailer = new Mail($configuration);
                try {
                    $mailer->addTo($email);
                } catch (Exception $exception) {
                    $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
                    $jsonResponse->setData(['error' => $exception->getMessage()]);
                    return $jsonResponse;
                }

                $mailer->subject = Utils::resolveMarkers('[%sitename%] Username / password request', $configuration);
                $mailer->message = $text;
                try {
                    $result = $mailer->send();
                } catch (Exception | TransportExceptionInterface $exception) {
                    $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
                    $jsonResponse->setData(['error' => $exception->getMessage()]);
                    return $jsonResponse;
                }

                unset($mailer);
                // Trust that the email has been sent
                $jsonResponse->setStatusCode(Response::HTTP_OK);
                $jsonResponse->setData(['success' => Translation::get('lostpwd_mail_okay')]);
            } else {
                $jsonResponse->setStatusCode(Response::HTTP_CONFLICT);
                $jsonResponse->setData(['error' => Translation::get('lostpwd_err_1')]);
                return $jsonResponse;
            }
        } else {
            $jsonResponse->setStatusCode(Response::HTTP_CONFLICT);
            $jsonResponse->setData(['error' => Translation::get('lostpwd_err_2')]);
            return $jsonResponse;
        }

        return $jsonResponse;
    }
}
