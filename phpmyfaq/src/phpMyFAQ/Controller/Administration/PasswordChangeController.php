<?php

/**
 * The Change Password Controller
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
 * @since     2024-11-23
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Auth;
use phpMyFAQ\Auth\AuthDriverInterface;
use phpMyFAQ\Auth\AuthException;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

final class PasswordChangeController extends AbstractAdministrationController
{
    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: '/password/change', name: 'admin.password.change', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::PASSWORD_CHANGE);

        return $this->render('@admin/user/password.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
        ]);
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route(path: '/password/update', name: 'admin.password.update', methods: ['POST'])]
    public function update(Request $request): Response
    {
        $this->userHasPermission(PermissionType::PASSWORD_CHANGE);

        $csrfToken = Filter::filterVar($request->request->get('pmf-csrf-token'), FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance($this->container->get(id: 'session'))->verifyToken('password', $csrfToken)) {
            throw new Exception('Invalid CSRF token');
        }

        $auth = $this->container->get(id: 'phpmyfaq.auth');
        $authSource = $auth->selectAuth($this->currentUser->getAuthSource('name'));
        $authSource->getEncryptionContainer($this->currentUser->getAuthData('encType'));

        $authSource->disableReadOnly();
        if ($this->currentUser->getAuthData(key: 'readOnly')) {
            $authSource->enableReadOnly();
        }

        $oldPassword = Filter::filterVar($request->request->get('faqpassword_old'), FILTER_SANITIZE_SPECIAL_CHARS);
        $newPassword = Filter::filterVar($request->request->get('faqpassword'), FILTER_SANITIZE_SPECIAL_CHARS);
        $retypedPassword = Filter::filterVar(
            $request->request->get('faqpassword_confirm'),
            FILTER_SANITIZE_SPECIAL_CHARS,
        );

        if ($this->currentUser->isStepUpLockedOut()) {
            return $this->render(
                '@admin/user/password.twig',
                [
                    ...$this->getHeader($request),
                    ...$this->getFooter(),
                    ...$this->getBaseTemplateVars(),
                    'successMessage' => '',
                    'errorMessage' => Translation::get(key: 'msgStepUpLockedOut'),
                ],
                new Response(status: Response::HTTP_TOO_MANY_REQUESTS),
            );
        }

        $successMessage = '';
        $errorMessage = '';
        if (strlen((string) $newPassword) <= 7 || strlen((string) $retypedPassword) <= 7) {
            $errorMessage = Translation::get(key: 'ad_passwd_fail');
        } elseif (!$this->verifyCurrentPassword($authSource, (string) $oldPassword)) {
            $errorMessage = Translation::get(key: 'ad_passwd_fail');
        } elseif (!hash_equals((string) $newPassword, (string) $retypedPassword)) {
            $errorMessage = Translation::get(key: 'ad_passwd_fail');
        } elseif (!$this->currentUser->changePassword($newPassword)) {
            $errorMessage = Translation::get(key: 'ad_passwd_fail');
        } else {
            $successMessage = Translation::get(key: 'ad_passwdsuc');
        }

        return $this->render('@admin/user/password.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
            'successMessage' => $successMessage,
            'errorMessage' => $errorMessage,
        ]);
    }

    /**
     * Verifies the current password as a step-up check before the password is changed.
     *
     * Failed attempts consume the account's login failure budget and lock the step-up
     * (and the login) once it is exhausted, so a hijacked admin session cannot guess
     * the current password without limit (CWE-307). This mirrors the step-up check of
     * the frontend user API.
     *
     * @throws \Exception
     */
    private function verifyCurrentPassword(Auth $authSource, #[\SensitiveParameter] string $currentPassword): bool
    {
        try {
            $isValid =
                $authSource instanceof AuthDriverInterface
                && $authSource->checkCredentials($this->currentUser->getLogin(), $currentPassword);
        } catch (AuthException) {
            $isValid = false;
        }

        if (!$isValid) {
            $this->currentUser->stepUpFailure();

            return false;
        }

        $this->currentUser->stepUpSuccess();

        return true;
    }

    /**
     * @throws \Exception
     * @return array<string, string>
     */
    private function getBaseTemplateVars(): array
    {
        return [
            'adminHeaderPasswordChange' => Translation::get(key: 'ad_passwd_cop'),
            'csrfToken' => Token::getInstance($this->container->get(id: 'session'))->getTokenString('password'),
            'adminMsgOldPassword' => Translation::get(key: 'ad_passwd_old'),
            'adminMsgNewPassword' => Translation::get(key: 'ad_passwd_new'),
            'adminMsgNewPasswordConfirm' => Translation::get(key: 'ad_passwd_con'),
            'adminMsgButtonNewPassword' => Translation::get(key: 'ad_passwd_change'),
        ];
    }
}
