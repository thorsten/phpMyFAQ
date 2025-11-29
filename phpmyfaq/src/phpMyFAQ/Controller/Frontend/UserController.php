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
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-02
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User\TwoFactor;
use RobThree\Auth\TwoFactorAuthException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use ZipArchive;

final class UserController extends AbstractController
{
    /**
     * @throws \Exception
     */
    #[Route(path: 'api/user/data/update', name: 'api.private.user.update', methods: ['PUT'])]
    public function updateData(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        $data = json_decode($request->getContent());

        $csrfToken = Filter::filterVar($data->{'pmf-csrf-token'}, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance($this->container->get(id: 'session'))->verifyToken('ucp', $csrfToken)) {
            return $this->json(['error' => Translation::get(key: 'ad_msg_noauth')], Response::HTTP_UNAUTHORIZED);
        }

        $userId = Filter::filterVar($data->userid, FILTER_VALIDATE_INT);
        $userName = trim(strip_tags((string) $data->name));
        $email = Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL);
        $isVisible = Filter::filterVar($data->{'is_visible'}, FILTER_SANITIZE_SPECIAL_CHARS);
        $password = trim((string) Filter::filterVar($data->faqpassword, FILTER_SANITIZE_SPECIAL_CHARS));
        $confirm = trim((string) Filter::filterVar($data->faqpassword_confirm, FILTER_SANITIZE_SPECIAL_CHARS));
        $twoFactorEnabled = Filter::filterVar($data->twofactor_enabled ?? 'off', FILTER_SANITIZE_SPECIAL_CHARS);
        $secret = Filter::filterVar($data->secret ?? '', FILTER_SANITIZE_SPECIAL_CHARS);

        $isAzureAdUser = $this->currentUser->getUserAuthSource() === 'azure';
        $isWebAuthnUser = $this->currentUser->getUserAuthSource() === 'webauthn';

        if ($userId !== $this->currentUser->getUserId()) {
            return $this->json(['error' => 'User ID mismatch!'], Response::HTTP_BAD_REQUEST);
        }

        if (!$isAzureAdUser) {
            if ($password !== $confirm) {
                return $this->json(['error' => Translation::get(
                    'ad_user_error_passwordsDontMatch',
                )], Response::HTTP_CONFLICT);
            }

            if ((strlen($password) <= 7 || strlen($confirm) <= 7) && !$isWebAuthnUser) {
                return $this->json(['error' => Translation::get(key: 'ad_passwd_fail')], Response::HTTP_CONFLICT);
            }

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
                    'twofactor_enabled' => $twoFactorEnabled === 'on' ? 1 : 0,
                ];
            }

            $success = $this->currentUser->setUserData($userData);

            foreach ($this->currentUser->getAuthContainer() as $authDriver) {
                if ($authDriver->disableReadOnly()) {
                    continue;
                }

                if (!$authDriver->update($this->currentUser->getLogin(), $password)) {
                    return $this->json(['error' => $authDriver->getErrors()], Response::HTTP_BAD_REQUEST);
                }

                $success = true;
            }
        } else {
            $userData = [
                'is_visible' => $isVisible === 'on' ? 1 : 0,
                'twofactor_enabled' => $twoFactorEnabled === 'on' ? 1 : 0,
                'secret' => $secret,
            ];

            $success = $this->currentUser->setUserData($userData);
        }

        if ($success) {
            return $this->json(['success' => Translation::get(key: 'ad_entry_savedsuc')], Response::HTTP_OK);
        }

        return $this->json(['error' => Translation::get(key: 'ad_entry_savedfail')], Response::HTTP_BAD_REQUEST);
    }

    /**
     * Export userdata of the currently logged-in user as a ZIP file.
     *
     * @throws \Exception
     */
    #[Route(path: 'api/user/data/export', name: 'api.private.user.data.export', methods: ['POST'])]
    public function exportUserData(Request $request): Response
    {
        $this->userIsAuthenticated();

        $data = $request->getPayload();

        $csrfToken = Filter::filterVar($data->get('pmf-csrf-token'), FILTER_SANITIZE_SPECIAL_CHARS);
        $userIdInput = Filter::filterVar($data->get('userid') ?? null, FILTER_VALIDATE_INT);

        if (!Token::getInstance($this->container->get(id: 'session'))->verifyToken('export-userdata', $csrfToken)) {
            return $this->json(['error' => Translation::get(key: 'ad_msg_noauth')], Response::HTTP_UNAUTHORIZED);
        }

        if (null !== $userIdInput && $userIdInput !== $this->currentUser->getUserId()) {
            return $this->json(['error' => 'User ID mismatch!'], Response::HTTP_BAD_REQUEST);
        }

        if (!class_exists(ZipArchive::class)) {
            return $this->json(['error' => 'ZIP extension not available.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $userData = [
            'user_id' => $this->currentUser->getUserId(),
            'last_modified' => (string) ($this->currentUser->getUserData('last_modified') ?? ''),
            'display_name' => (string) ($this->currentUser->getUserData('display_name') ?? ''),
            'email' => (string) ($this->currentUser->getUserData('email') ?? ''),
            'is_visible' => (int) ($this->currentUser->getUserData('is_visible') ?? 0),
            'twofactor_enabled' => (int) ($this->currentUser->getUserData('twofactor_enabled') ?? 0),
            'secret' => (string) ($this->currentUser->getUserData('secret') ?? ''),
        ];

        $json = json_encode($userData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return $this->json(['error' => 'Failed to encode userdata.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Create a temporary ZIP file
        $tmpFile = tempnam(sys_get_temp_dir(), 'pmf_userdata_');
        if ($tmpFile === false) {
            return $this->json(['error' => 'Failed to create temp file.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $zip = new ZipArchive();
        if ($zip->open($tmpFile, ZipArchive::OVERWRITE) !== true) {
            return $this->json(['error' => 'Failed to create ZIP archive.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $zip->addFromString('userdata.json', $json);
        $zip->close();

        $fileName = sprintf('phpmyfaq-userdata-%d-%s.zip', $this->currentUser->getUserId(), date(format: 'YmdHis'));

        $response = new BinaryFileResponse($tmpFile);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName);
        $response->headers->set('Content-Type', 'application/zip');
        $response->deleteFileAfterSend();

        return $response;
    }

    /**
     * @throws Exception|\Exception
     */
    #[Route(path: 'api/user/request-removal', methods: ['POST'])]
    public function requestUserRemoval(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());

        $csrfToken = Filter::filterVar($data->{'pmf-csrf-token'}, FILTER_SANITIZE_SPECIAL_CHARS);
        if (!Token::getInstance($this->container->get(id: 'session'))->verifyToken('request-removal', $csrfToken)) {
            return $this->json(['error' => Translation::get(key: 'ad_msg_noauth')], Response::HTTP_UNAUTHORIZED);
        }

        $userId = Filter::filterVar($data->userId, FILTER_VALIDATE_INT);
        $author = trim((string) Filter::filterVar($data->name, FILTER_SANITIZE_SPECIAL_CHARS));
        $loginName = trim((string) Filter::filterVar($data->loginname, FILTER_SANITIZE_SPECIAL_CHARS));
        $email = trim((string) Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL));
        $question = trim((string) Filter::filterVar($data->question, FILTER_SANITIZE_SPECIAL_CHARS));

        // Validate User ID, Username and email
        if (
            !$this->currentUser->getUserById($userId)
            || $userId !== $this->currentUser->getUserId()
            || $loginName !== $this->currentUser->getLogin()
            || $email !== $this->currentUser->getUserData('email')
        ) {
            return $this->json(['error' => Translation::get(
                key: 'ad_user_error_loginInvalid',
            )], Response::HTTP_BAD_REQUEST);
        }

        $stopWords = $this->container->get(id: 'phpmyfaq.stop-words');
        if (
            $author !== ''
            && $author !== '0'
            && $email !== ''
            && $email !== '0'
            && $question !== ''
            && $question !== '0'
            && $stopWords->checkBannedWord($question)
        ) {
            $question = sprintf(
                '%s %s<br>%s %s<br>%s %s<br><br>%s',
                Translation::get(key: 'msgUsername'),
                $loginName,
                Translation::get(key: 'msgNewContentName'),
                $author,
                Translation::get(key: 'msgNewContentMail'),
                $email,
                $question,
            );

            $mailer = $this->container->get(id: 'phpmyfaq.mail');
            try {
                $mailer->setReplyTo($email, $author);
                $mailer->addTo($this->configuration->getAdminEmail());
                $mailer->setReplyTo($this->configuration->getNoReplyEmail());
                $mailer->subject = $this->configuration->getTitle() . ': Remove User Request';
                $mailer->message = $question;
                $mailer->send();
                unset($mailer);

                return $this->json(['success' => Translation::get(key: 'msgMailContact')], Response::HTTP_OK);
            } catch (Exception|TransportExceptionInterface $exception) {
                return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        }

        return $this->json(['error' => Translation::get(key: 'err_sendMail')], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws \Exception|Exception|TwoFactorAuthException
     */
    #[Route(path: 'api/user/remove-twofactor', methods: ['POST'])]
    public function removeTwofactorConfig(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        $twoFactor = new TwoFactor($this->configuration, $this->currentUser);

        $csrfToken = Filter::filterVar($data->csrfToken, FILTER_SANITIZE_SPECIAL_CHARS);
        if (!Token::getInstance($this->container->get(id: 'session'))->verifyToken('remove-twofactor', $csrfToken)) {
            return $this->json(['error' => Translation::get(key: 'ad_msg_noauth')], Response::HTTP_UNAUTHORIZED);
        }

        if ($this->currentUser->isLoggedIn()) {
            $newSecret = $twoFactor->generateSecret();

            if ($this->currentUser->setUserData(['secret' => $newSecret, 'twofactor_enabled' => 0])) {
                return $this->json(['success' => Translation::get(
                    'msgRemoveTwofactorConfigSuccessful',
                )], Response::HTTP_OK);
            }

            return $this->json(['error' => Translation::get(key: 'msgErrorOccurred')], Response::HTTP_BAD_REQUEST);
        }

        throw new Exception('The user is not logged in.');
    }
}
