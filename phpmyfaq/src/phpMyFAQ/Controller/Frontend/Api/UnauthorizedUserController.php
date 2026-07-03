<?php

/**
 * Public, unauthenticated user endpoints (password reset).
 *
 * Exposes two endpoints:
 *
 *  - PUT  /api/user/password/update  -> request a reset link by email.
 *  - POST /api/user/password/reset   -> consume a signed reset link and set a new password.
 *
 * The issuance endpoint always returns the same generic response to defeat
 * username/email enumeration. Both endpoints are rate-limited per client IP
 * on top of the global API rate limiter.
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

namespace phpMyFAQ\Controller\Frontend\Api;

use Closure;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Http\RateLimiter;
use phpMyFAQ\Mail;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\PasswordResetTokenService;
use phpMyFAQ\Utils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class UnauthorizedUserController
{
    private const int ISSUE_LIMIT_PER_IP = 5;
    private const int ISSUE_LIMIT_INTERVAL = 900;
    private const int ISSUE_LIMIT_PER_LOGIN = 3;
    private const int ISSUE_LIMIT_PER_LOGIN_INTERVAL = 3600;
    private const int VERIFY_LIMIT_PER_IP = 10;
    private const int VERIFY_LIMIT_INTERVAL = 900;
    private const int RESET_TOKEN_LIFETIME_SECONDS = 3600;
    private const int MIN_PASSWORD_LENGTH = 8;

    private readonly Configuration $configuration;
    private readonly PasswordResetTokenService $tokenService;
    private readonly RateLimiter $rateLimiter;

    /**
     * @param ?Closure(Configuration): CurrentUser $currentUserFactory
     * @param ?Closure(Configuration): Mail $mailFactory
     */
    public function __construct(
        private readonly ?Closure $currentUserFactory = null,
        private readonly ?Closure $mailFactory = null,
        ?PasswordResetTokenService $tokenService = null,
        ?RateLimiter $rateLimiter = null,
        ?Configuration $configuration = null,
    ) {
        $this->configuration = $configuration ?? Configuration::getConfigurationInstance();
        $this->tokenService = $tokenService ?? new PasswordResetTokenService();
        $this->rateLimiter = $rateLimiter ?? new RateLimiter();
    }

    /**
     * Request a password reset email. Always returns a generic success response
     * regardless of whether the username/email exists, to prevent enumeration.
     *
     * @throws Exception
     */
    #[Route(path: 'user/password/update', name: 'api.private.user.password', methods: ['PUT'])]
    public function requestReset(Request $request): JsonResponse
    {
        if (!$this->rateLimiter->check(
            'pwreset:issue:ip:' . $this->clientIp($request),
            self::ISSUE_LIMIT_PER_IP,
            self::ISSUE_LIMIT_INTERVAL,
        )) {
            return $this->tooManyRequests();
        }

        $data = json_decode($request->getContent());
        if (!is_object($data)) {
            return $this->genericIssuanceResponse();
        }

        $username = trim((string) Filter::filterVar($data->username ?? '', FILTER_SANITIZE_SPECIAL_CHARS));
        $email = trim((string) Filter::filterEmail($data->email ?? ''));

        if ($username === '' || $email === '') {
            return $this->genericIssuanceResponse();
        }

        if (!$this->rateLimiter->check(
            'pwreset:issue:user:' . hash('sha256', $username),
            self::ISSUE_LIMIT_PER_LOGIN,
            self::ISSUE_LIMIT_PER_LOGIN_INTERVAL,
        )) {
            return $this->genericIssuanceResponse();
        }

        $user = ($this->currentUserFactory ?? CurrentUser::getCurrentUser(...))($this->configuration);
        $loginExists = $user->getUserByLogin($username, false);

        if (!$loginExists) {
            return $this->genericIssuanceResponse();
        }

        if (!hash_equals((string) $user->getUserData('email'), $email)) {
            return $this->genericIssuanceResponse();
        }

        $passwordKey = $user->getEncryptedPassword();
        if ($passwordKey === '') {
            return $this->genericIssuanceResponse();
        }

        $token = $this->tokenService->issue($user->getUserId(), $passwordKey, self::RESET_TOKEN_LIFETIME_SECONDS);

        try {
            $this->sendResetLinkEmail($email, $username, $token);
        } catch (Exception|TransportExceptionInterface $exception) {
            // Swallow delivery errors so we do not leak account existence via timing or error.
            error_log('phpMyFAQ password reset email failed: ' . $exception->getMessage());
        }

        return $this->genericIssuanceResponse();
    }

    /**
     * Consume a signed reset link and update the password.
     *
     * @throws Exception
     */
    #[Route(path: 'user/password/reset', name: 'api.private.user.password.reset', methods: ['POST'])]
    public function reset(Request $request): JsonResponse
    {
        if (!$this->rateLimiter->check(
            'pwreset:verify:ip:' . $this->clientIp($request),
            self::VERIFY_LIMIT_PER_IP,
            self::VERIFY_LIMIT_INTERVAL,
        )) {
            return $this->tooManyRequests();
        }

        $data = json_decode($request->getContent());
        if (!is_object($data)) {
            return $this->json(['error' => Translation::get('resetpwd_err_invalid')], Response::HTTP_BAD_REQUEST);
        }

        $userId = (int) Filter::filterVar($data->u ?? null, FILTER_VALIDATE_INT);
        $expires = (int) Filter::filterVar($data->exp ?? null, FILTER_VALIDATE_INT);
        $signature = (string) Filter::filterVar($data->sig ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        $newPassword = is_string($data->password ?? null) ? $data->password : '';
        $repeatPassword = is_string($data->password_repeat ?? null) ? $data->password_repeat : '';

        if ($userId <= 0 || $expires <= 0 || $signature === '') {
            return $this->json(['error' => Translation::get('resetpwd_err_invalid')], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($newPassword) < self::MIN_PASSWORD_LENGTH || strlen($repeatPassword) < self::MIN_PASSWORD_LENGTH) {
            return $this->json(['error' => Translation::get('msgPasswordTooShort')], Response::HTTP_BAD_REQUEST);
        }

        if (!hash_equals($newPassword, $repeatPassword)) {
            return $this->json(['error' => Translation::get('ad_passwd_fail')], Response::HTTP_BAD_REQUEST);
        }

        $user = ($this->currentUserFactory ?? CurrentUser::getCurrentUser(...))($this->configuration);
        if (!$user->getUserById($userId, true)) {
            return $this->json(['error' => Translation::get('resetpwd_err_invalid')], Response::HTTP_BAD_REQUEST);
        }

        $passwordKey = $user->getEncryptedPassword();
        if ($passwordKey === '') {
            return $this->json(['error' => Translation::get('resetpwd_err_invalid')], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->tokenService->verify($userId, $expires, $signature, $passwordKey)) {
            return $this->json(['error' => Translation::get('resetpwd_err_invalid')], Response::HTTP_BAD_REQUEST);
        }

        if (!$user->changePassword($newPassword)) {
            return $this->json(['error' => Translation::get('ad_passwd_fail')], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['success' => Translation::get('resetpwd_success')], Response::HTTP_OK);
    }

    /**
     * Returns a JsonResponse that uses json_encode().
     *
     * @param string[] $headers
     */
    public function json(mixed $data, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }

    /**
     * @param array{userId: int, expires: int, signature: string} $token
     * @throws Exception|TransportExceptionInterface
     */
    private function sendResetLinkEmail(string $email, string $username, #[\SensitiveParameter] array $token): void
    {
        $baseUrl = rtrim((string) $this->configuration->getDefaultUrl(), characters: '/');
        $link = sprintf(
            '%s/user/reset-password?u=%d&exp=%d&sig=%s',
            $baseUrl,
            $token['userId'],
            $token['expires'],
            rawurlencode($token['signature']),
        );

        $message =
            Translation::getString('lostpwd_text_1')
            . "\r\n\r\n"
            . Translation::getString('resetpwd_text_link')
            . "\r\n"
            . $link
            . "\r\n\r\n"
            . Translation::getString('resetpwd_text_expiry')
            . "\r\n\r\nUsername: "
            . $username;

        $mail = ($this->mailFactory
        ?? static fn(Configuration $configuration): Mail => new Mail($configuration))($this->configuration);
        $mail->addTo($email);
        $mail->subject = Utils::resolveMarkers('[%sitename%] Password reset request', $this->configuration);
        $mail->message = $message;
        $mail->send();
        unset($mail);
    }

    private function genericIssuanceResponse(): JsonResponse
    {
        return $this->json(['success' => Translation::get('lostpwd_mail_okay')], Response::HTTP_OK);
    }

    private function tooManyRequests(): JsonResponse
    {
        return $this->json(['error' => 'Too many requests. Please retry later.'], Response::HTTP_TOO_MANY_REQUESTS);
    }

    private function clientIp(Request $request): string
    {
        return $request->getClientIp() ?? 'anonymous';
    }
}
