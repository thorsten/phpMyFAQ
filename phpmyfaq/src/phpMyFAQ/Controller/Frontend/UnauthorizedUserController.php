<?php

/**
 * The User Controller for unauthorized users
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
 * @since     2024-07-08
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Filter;
use phpMyFAQ\Mail;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Utils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class UnauthorizedUserController
{
    private const RESET_TOKEN_TTL = 3600;

    private ?Configuration $configuration = null;

    public function __construct()
    {
        $this->configuration = Configuration::getConfigurationInstance();
    }

    /**
     * Step 1: request a password reset link.
     *
     * Always returns a generic success response so the endpoint cannot be used
     * to enumerate accounts. When username and email match an existing user,
     * a signed reset link is emailed to the on-file address. The link contains
     * an HMAC bound to the user's current password hash, which makes it
     * effectively single-use (changing the password invalidates outstanding
     * tokens).
     *
     * @throws Exception
     */
    #[Route(path: 'api/user/password/update', methods: ['PUT'])]
    public function updatePassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());

        $username = trim((string) Filter::filterVar($data->username ?? '', FILTER_SANITIZE_SPECIAL_CHARS));
        $email = trim((string) Filter::filterVar($data->email ?? '', FILTER_VALIDATE_EMAIL));

        $genericSuccess = $this->json(['success' => Translation::get(key: 'lostpwd_mail_okay')], Response::HTTP_OK);

        if ($username === '' || $email === '') {
            return $genericSuccess;
        }

        $user = CurrentUser::getCurrentUser($this->configuration);
        if (!$user->getUserByLogin($username)) {
            return $genericSuccess;
        }

        if (!hash_equals((string) $user->getUserData('email'), $email)) {
            return $genericSuccess;
        }

        $passHash = $this->getUserPasswordHash($user->getLogin());
        if ($passHash === null) {
            return $genericSuccess;
        }

        $expires = time() + self::RESET_TOKEN_TTL;
        $signature = $this->signResetToken($user->getUserId(), $expires, $passHash);

        $resetUrl = sprintf(
            '%s/index.php?action=resetpw&u=%d&exp=%d&sig=%s',
            rtrim($this->configuration->getDefaultUrl(), '/'),
            $user->getUserId(),
            $expires,
            $signature,
        );

        $text =
            Translation::get(key: 'lostpwd_text_1')
            . sprintf('<br><br>Username: %s<br><br>', $username)
            . Translation::get(key: 'resetpwd_text_link')
            . sprintf('<br><a href="%1$s">%1$s</a><br><br>', $resetUrl)
            . Translation::get(key: 'resetpwd_text_expiry');

        $mail = new Mail($this->configuration);
        try {
            $mail->addTo($email);
            $mail->subject = Utils::resolveMarkers('[%sitename%] Password reset request', $this->configuration);
            $mail->message = $text;
            $mail->send();
        } catch (Exception|TransportExceptionInterface $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } finally {
            unset($mail);
        }

        return $genericSuccess;
    }

    /**
     * Step 2: consume the signed reset token and set a new password.
     *
     * @throws Exception
     */
    #[Route(path: 'api/user/password/reset', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());

        $userId = (int) ($data->u ?? 0);
        $expires = (int) ($data->exp ?? 0);
        $signature = (string) ($data->sig ?? '');
        $password = (string) ($data->password ?? '');
        $confirm = (string) ($data->password_repeat ?? '');

        $invalidToken = $this->json([
            'error' => Translation::get(key: 'resetpwd_err_invalid'),
        ], Response::HTTP_BAD_REQUEST);

        if ($userId <= 0 || $expires <= 0 || $signature === '') {
            return $invalidToken;
        }

        if ($expires < time()) {
            return $invalidToken;
        }

        if ($password === '' || $password !== $confirm) {
            return $this->json(['error' => Translation::get(key: 'ad_passwd_fail')], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($password) < 8) {
            return $this->json(['error' => Translation::get(key: 'ad_passwd_fail')], Response::HTTP_BAD_REQUEST);
        }

        $user = CurrentUser::getCurrentUser($this->configuration);
        if (!$user->getUserById($userId)) {
            return $invalidToken;
        }

        $passHash = $this->getUserPasswordHash($user->getLogin());
        if ($passHash === null) {
            return $invalidToken;
        }

        $expected = $this->signResetToken($userId, $expires, $passHash);
        if (!hash_equals($expected, $signature)) {
            return $invalidToken;
        }

        try {
            if (!$user->changePassword($password)) {
                return $this->json(['error' => Translation::get(key: 'ad_passwd_fail')], Response::HTTP_BAD_REQUEST);
            }
        } catch (\Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['success' => Translation::get(key: 'resetpwd_success')], Response::HTTP_OK);
    }

    private function signResetToken(int $userId, int $expires, string $passHash): string
    {
        $secret = (string) $this->configuration->get('main.phpMyFAQToken');
        $payload = $userId . '|' . $expires . '|' . $passHash;
        return hash_hmac('sha256', $payload, $secret);
    }

    private function getUserPasswordHash(string $login): ?string
    {
        $db = $this->configuration->getDb();
        $query = sprintf(
            "SELECT pass FROM %sfaquserlogin WHERE login = '%s'",
            Database::getTablePrefix(),
            $db->escape($login),
        );
        $result = $db->query($query);
        if (!$result) {
            return null;
        }
        $row = $db->fetchArray($result);
        if (!is_array($row) || !isset($row['pass'])) {
            return null;
        }
        return (string) $row['pass'];
    }

    /**
     * @param string[] $headers
     */
    public function json(mixed $data, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }
}
