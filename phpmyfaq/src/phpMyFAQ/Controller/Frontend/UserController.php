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

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Mail;
use phpMyFAQ\Session\Token;
use phpMyFAQ\StopWords;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\TwoFactor;
use phpMyFAQ\Utils;
use RobThree\Auth\TwoFactorAuthException;
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

        $user = CurrentUser::getCurrentUser($this->configuration);

        $data = json_decode($request->getContent());

        $csrfToken = Filter::filterVar($data->{'pmf-csrf-token'}, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance()->verifyToken('ucp', $csrfToken)) {
            return $this->json(['error' => Translation::get('ad_msg_noauth')], Response::HTTP_UNAUTHORIZED);
        }

        $userId = Filter::filterVar($data->userid, FILTER_VALIDATE_INT);
        $userName = trim((string) Filter::filterVar($data->name, FILTER_SANITIZE_SPECIAL_CHARS));
        $email = Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL);
        $isVisible = Filter::filterVar($data->{'is_visible'}, FILTER_SANITIZE_SPECIAL_CHARS);
        $password = trim((string) Filter::filterVar($data->faqpassword, FILTER_SANITIZE_SPECIAL_CHARS));
        $confirm = trim((string) Filter::filterVar($data->faqpassword_confirm, FILTER_SANITIZE_SPECIAL_CHARS));
        $twoFactorEnabled = Filter::filterVar($data->twofactor_enabled ?? 'off', FILTER_SANITIZE_SPECIAL_CHARS);

        $isAzureAdUser = $user->getUserAuthSource() === 'azure';
        $isWebAuthnUser = $user->getUserAuthSource() === 'webauthn';

        if ($userId !== $user->getUserId()) {
            return $this->json(['error' => 'User ID mismatch!'], Response::HTTP_BAD_REQUEST);
        }

        if (!$isAzureAdUser) {
            if ($password !== $confirm) {
                return $this->json(
                    ['error' => Translation::get('ad_user_error_passwordsDontMatch')],
                    Response::HTTP_CONFLICT
                );
            }

            if ((strlen($password) <= 7 || strlen($confirm) <= 7) && !$isWebAuthnUser) {
                return $this->json(['error' => Translation::get('ad_passwd_fail')], Response::HTTP_CONFLICT);
            } else {
                if ($isWebAuthnUser) {
                    $userData = [
                        'display_name' => $userName,
                        'is_visible' => $isVisible === 'on' ? 1 : 0,
                    ];
                } else {
                    $userData = [
                        'display_name' => $userName,
                        'email' => $email,
                        'is_visible' => $isVisible === 'on' ? 1 : 0,
                        'twofactor_enabled' => $twoFactorEnabled === 'on' ? 1 : 0
                    ];
                }

                $success = $user->setUserData($userData);

                foreach ($user->getAuthContainer() as $auth) {
                    if ($auth->setReadOnly()) {
                        continue;
                    }

                    if (!$auth->update($user->getLogin(), $password)) {
                        return $this->json(['error' => $auth->getErrors()], Response::HTTP_BAD_REQUEST);
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
            return $this->json(['success' => Translation::get('ad_entry_savedsuc')], Response::HTTP_OK);
        } else {
            return $this->json(['error' => Translation::get('ad_entry_savedfail')], Response::HTTP_BAD_REQUEST);
        }
    }


    /**
     * @throws Exception
     */
    #[Route('api/user/request-removal', methods: ['POST'])]
    public function requestUserRemoval(Request $request): JsonResponse
    {
        $stopWords = new StopWords($this->configuration);
        $user = CurrentUser::getCurrentUser($this->configuration);

        $data = json_decode($request->getContent());

        $csrfToken = Filter::filterVar($data->{'pmf-csrf-token'}, FILTER_SANITIZE_SPECIAL_CHARS);
        if (!Token::getInstance()->verifyToken('request-removal', $csrfToken)) {
            return $this->json(['error' => Translation::get('ad_msg_noauth')], Response::HTTP_UNAUTHORIZED);
        }

        $userId = Filter::filterVar($data->userId, FILTER_VALIDATE_INT);
        $author = trim((string) Filter::filterVar($data->name, FILTER_SANITIZE_SPECIAL_CHARS));
        $loginName = trim((string) Filter::filterVar($data->loginname, FILTER_SANITIZE_SPECIAL_CHARS));
        $email = trim((string) Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL));
        $question = trim((string) Filter::filterVar($data->question, FILTER_SANITIZE_SPECIAL_CHARS));

        // Validate User ID, Username and email
        if (
            !$user->getUserById($userId) ||
            $userId !== $user->getUserId() ||
            $loginName !== $user->getLogin() ||
            $email !== $user->getUserData('email')
        ) {
            return $this->json(['error' => Translation::get('ad_user_error_loginInvalid')], Response::HTTP_BAD_REQUEST);
        }

        if (
            $author !== '' &&
            $author !== '0' &&
            ($email !== '' && $email !== '0') &&
            ($question !== '' && $question !== '0') &&
            $stopWords->checkBannedWord($question)
        ) {
            $question = sprintf(
                "%s %s<br>%s %s<br>%s %s<br><br>%s",
                Translation::get('msgUsername'),
                $loginName,
                Translation::get('msgNewContentName'),
                $author,
                Translation::get('msgNewContentMail'),
                $email,
                $question
            );

            $mailer = new Mail($this->configuration);
            try {
                $mailer->setReplyTo($email, $author);
                $mailer->addTo($this->configuration->getAdminEmail());
                $mailer->setReplyTo($this->configuration->getNoReplyEmail());
                $mailer->subject = $this->configuration->getTitle() . ': Remove User Request';
                $mailer->message = $question;
                $mailer->send();
                unset($mailer);

                return $this->json(['success' => Translation::get('msgMailContact')], Response::HTTP_OK);
            } catch (Exception | TransportExceptionInterface $exception) {
                return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        } else {
            return $this->json(['error' => Translation::get('err_sendMail')], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws Exception|TwoFactorAuthException
     */
    #[Route('api/user/remove-twofactor', methods: ['POST'])]
    public function removeTwofactorConfig(Request $request): JsonResponse
    {
        $user = CurrentUser::getCurrentUser($this->configuration);

        $data = json_decode($request->getContent());
        $twoFactor = new TwoFactor($this->configuration, $user);

        $csrfToken = Filter::filterVar($data->csrfToken, FILTER_SANITIZE_SPECIAL_CHARS);
        if (!Token::getInstance()->verifyToken('remove-twofactor', $csrfToken)) {
            return $this->json(['error' => Translation::get('ad_msg_noauth')], Response::HTTP_UNAUTHORIZED);
        }

        if ($user->isLoggedIn()) {
            $newSecret = $twoFactor->generateSecret();

            if ($user->setUserData(['secret' => $newSecret, 'twofactor_enabled' => 0])) {
                return $this->json(
                    ['success' => Translation::get('msgRemoveTwofactorConfigSuccessful')],
                    Response::HTTP_OK
                );
            } else {
                return $this->json(['error' => Translation::get('ad_entryins_fail')], Response::HTTP_BAD_REQUEST);
            }
        } else {
            throw new Exception('The user is not logged in.');
        }
    }
}
