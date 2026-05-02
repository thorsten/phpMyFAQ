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
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-02
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Mail;
use phpMyFAQ\Session\Token;
use phpMyFAQ\StopWords;
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
    public function __construct(
        private readonly StopWords $stopWords,
        private readonly Mail $mailer,
    ) {
        parent::__construct();
    }

    /**
     * @throws \Exception
     */
    #[Route(path: 'user/data/update', name: 'api.private.user.update', methods: ['PUT'])]
    public function updateData(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        $data = json_decode($request->getContent());

        $csrfToken = Filter::filterVar($data->{'pmf-csrf-token'}, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance($this->session)->verifyToken('ucp', $csrfToken)) {
            return $this->json(['error' => Translation::get(key: 'ad_msg_noauth')], Response::HTTP_UNAUTHORIZED);
        }

        $userId = Filter::filterVar($data->userid, FILTER_VALIDATE_INT);
        $userName = trim(strip_tags((string) $data->name));
        $email = Filter::filterEmail($data->email);
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

        $success = false;
        if (!$isAzureAdUser) {
            if (!hash_equals($password, $confirm)) {
                return $this->json([
                    'error' => Translation::get('ad_user_error_passwordsDontMatch'),
                ], Response::HTTP_CONFLICT);
            }

            if ((strlen($password) <= 7 || strlen($confirm) <= 7) && !$isWebAuthnUser) {
                return $this->json(['error' => Translation::get(key: 'ad_passwd_fail')], Response::HTTP_CONFLICT);
            }

            $userData = [
                'display_name' => $userName,
                'is_visible' => $isVisible === 'on' ? 1 : 0,
            ];
            if (!$isWebAuthnUser) {
                $userData['email'] = $email;
                $userData['twofactor_enabled'] = $twoFactorEnabled === 'on' ? 1 : 0;
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
        }

        if ($isAzureAdUser) {
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
    #[Route(path: 'user/data/export', name: 'api.private.user.data.export', methods: ['POST'])]
    public function exportUserData(Request $request): Response
    {
        $this->userIsAuthenticated();

        $inputBag = $request->getPayload();

        $csrfToken = Filter::filterVar($inputBag->get('pmf-csrf-token'), FILTER_SANITIZE_SPECIAL_CHARS);
        $userIdInput = Filter::filterVar($inputBag->get('userid') ?? null, FILTER_VALIDATE_INT);

        if (!Token::getInstance($this->session)->verifyToken('export-userdata', $csrfToken)) {
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
        $tmpFile = tempnam(directory: sys_get_temp_dir(), prefix: 'pmf_userdata_');
        if ($tmpFile === false) {
            return $this->json(['error' => 'Failed to create temp file.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $zipArchive = new ZipArchive();
        if ($zipArchive->open($tmpFile, ZipArchive::OVERWRITE) !== true) {
            return $this->json(['error' => 'Failed to create ZIP archive.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $zipArchive->addFromString('userdata.json', $json);
        $zipArchive->close();

        $fileName = sprintf('phpmyfaq-userdata-%d-%s.zip', $this->currentUser->getUserId(), date(format: 'YmdHis'));

        $binaryFileResponse = new BinaryFileResponse($tmpFile);
        $binaryFileResponse->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName);
        $binaryFileResponse->headers->set('Content-Type', 'application/zip');
        $binaryFileResponse->deleteFileAfterSend();

        return $binaryFileResponse;
    }

    /**
     * @throws Exception|\Exception
     */
    #[Route(path: 'user/request-removal', name: 'api.private.user.request-removal', methods: ['POST'])]
    public function requestUserRemoval(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());

        if (!$data) {
            throw new Exception('Invalid JSON data');
        }

        if (($data->{'pmf-csrf-token'} ?? null) === null) {
            throw new Exception('Missing CSRF token');
        }

        $csrfToken = Filter::filterVar($data->{'pmf-csrf-token'}, FILTER_SANITIZE_SPECIAL_CHARS);
        if (!Token::getInstance($this->session)->verifyToken('request-removal', $csrfToken)) {
            throw new Exception('Invalid CSRF token');
        }

        $userId = Filter::filterVar($data->userId, FILTER_VALIDATE_INT);
        $author = trim((string) Filter::filterVar($data->name, FILTER_SANITIZE_SPECIAL_CHARS));
        $loginName = trim((string) Filter::filterVar($data->loginname, FILTER_SANITIZE_SPECIAL_CHARS));
        $email = trim((string) Filter::filterEmail($data->email));
        $question = trim((string) Filter::filterVar($data->question, FILTER_SANITIZE_SPECIAL_CHARS));

        // Validate User ID, Username and email
        if (
            !$this->currentUser->getUserById($userId)
            || $userId !== $this->currentUser->getUserId()
            || $loginName !== $this->currentUser->getLogin()
            || $email !== $this->currentUser->getUserData('email')
        ) {
            return $this->json([
                'error' => Translation::get(key: 'ad_user_error_loginInvalid'),
            ], Response::HTTP_BAD_REQUEST);
        }

        if (
            $author !== ''
            && $author !== '0'
            && $email !== ''
            && $email !== '0'
            && $question !== ''
            && $question !== '0'
            && $this->stopWords->checkBannedWord($question)
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

            try {
                $this->mailer->setReplyTo($email, $author);
                $this->mailer->addTo($this->configuration->getAdminEmail());
                $this->mailer->subject = $this->configuration->getTitle() . ': Remove User Request';
                $this->mailer->message = $question;
                $this->mailer->send();

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
    #[Route(path: 'user/remove-twofactor', name: 'api.private.user.remove-twofactor', methods: ['POST'])]
    public function removeTwofactorConfig(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());

        if (!$data) {
            throw new Exception('Invalid JSON data');
        }

        if (($data->csrfToken ?? null) === null) {
            throw new Exception('Missing CSRF token');
        }

        $twoFactor = new TwoFactor($this->configuration, $this->currentUser);

        $csrfToken = Filter::filterVar($data->csrfToken, FILTER_SANITIZE_SPECIAL_CHARS);
        if (!Token::getInstance($this->session)->verifyToken('remove-twofactor', $csrfToken)) {
            throw new Exception('Invalid CSRF token');
        }

        if (!$this->currentUser->isLoggedIn()) {
            throw new Exception('The user is not logged in.');
        }

        $newSecret = $twoFactor->generateSecret();

        if ($this->currentUser->setUserData(['secret' => $newSecret, 'twofactor_enabled' => 0])) {
            return $this->json([
                'success' => Translation::get('msgRemoveTwofactorConfigSuccessful'),
            ], Response::HTTP_OK);
        }

        return $this->json(['error' => Translation::get(key: 'msgErrorOccurred')], Response::HTTP_BAD_REQUEST);
    }
}
