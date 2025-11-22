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
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-11-23
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

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
    #[Route('/password/change', name: 'admin.password.change', methods: ['GET'])]
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
    #[Route('/password/update', name: 'admin.password.update', methods: ['POST'])]
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

        $successMessage = '';
        $errorMessage = '';
        if (strlen((string) $newPassword) <= 7 || strlen((string) $retypedPassword) <= 7) {
            $errorMessage = Translation::get(languageKey: 'ad_passwd_fail');
        } elseif (
            $authSource->checkCredentials($this->currentUser->getLogin(), $oldPassword)
            && $newPassword == $retypedPassword
        ) {
            if (!$this->currentUser->changePassword($newPassword)) {
                $errorMessage = Translation::get(languageKey: 'ad_passwd_fail');
            }

            $successMessage = Translation::get(languageKey: 'ad_passwdsuc');
        } else {
            $errorMessage = Translation::get(languageKey: 'ad_passwd_fail');
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
     * @throws \Exception
     * @return array<string, string>
     */
    private function getBaseTemplateVars(): array
    {
        return [
            'adminHeaderPasswordChange' => Translation::get(languageKey: 'ad_passwd_cop'),
            'csrfToken' => Token::getInstance($this->container->get(id: 'session'))->getTokenString('password'),
            'adminMsgOldPassword' => Translation::get(languageKey: 'ad_passwd_old'),
            'adminMsgNewPassword' => Translation::get(languageKey: 'ad_passwd_new'),
            'adminMsgNewPasswordConfirm' => Translation::get(languageKey: 'ad_passwd_con'),
            'adminMsgButtonNewPassword' => Translation::get(languageKey: 'ad_passwd_change'),
        ];
    }
}
