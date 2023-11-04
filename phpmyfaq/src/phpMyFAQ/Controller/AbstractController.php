<?php

/**
 * Abstract Controller for phpMyFAQ
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
 * @since     2023-10-24
 */

namespace phpMyFAQ\Controller;

use phpMyFAQ\Configuration;
use phpMyFAQ\Template;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

abstract class AbstractController
{
    /**
     * @param string $pathToTwigFile
     * @param string[] $templateVars
     * @return Response
     * @throws Template\TemplateException
     */
    public function render(string $pathToTwigFile, array $templateVars = []): Response
    {
        $response = new Response();
        $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
        $template = $twig->loadTemplate($pathToTwigFile);

        $response->setContent($template->render($templateVars));

        return $response;
    }

    /**
     * @throws UnauthorizedHttpException
     */
    public function userIsAuthenticated(): void
    {
        $configuration = Configuration::getConfigurationInstance();
        if (!CurrentUser::getCurrentUser($configuration)->isLoggedIn()) {
            throw new UnauthorizedHttpException('User is not authenticated.');
        }
    }

    /**
     * @throws UnauthorizedHttpException
     */
    public function userIsSuperAdmin(): void
    {
        $configuration = Configuration::getConfigurationInstance();
        if (!CurrentUser::getCurrentUser($configuration)->isSuperAdmin()) {
            throw new UnauthorizedHttpException('User is not super admin.');
        }
    }

    /**
     * @throws UnauthorizedHttpException
     */
    public function userHasGroupPermission(): void
    {
        $configuration = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($configuration);
        if (
            !$user->perm->hasPermission($user->getUserId(), 'add_user') ||
            !$user->perm->hasPermission($user->getUserId(), 'edit_user') ||
            !$user->perm->hasPermission($user->getUserId(), 'delete_user') ||
            !$user->perm->hasPermission($user->getUserId(), 'editgroup')
        ) {
            throw new UnauthorizedHttpException('User has no group permission.');
        }
    }

    public function userHasUserPermission(): void
    {
        $configuration = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($configuration);
        if (
            !$user->perm->hasPermission($user->getUserId(), 'add_user') ||
            !$user->perm->hasPermission($user->getUserId(), 'edit_user') ||
            !$user->perm->hasPermission($user->getUserId(), 'delete_user')
        ) {
            throw new UnauthorizedHttpException('User has no user permission.');
        }
    }

    public function userHasPermission(string $permission): void
    {
        $configuration = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($configuration);
        if (!$user->perm->hasPermission($user->getUserId(), $permission)) {
            throw new UnauthorizedHttpException(sprintf('User has no "%s" permission.', $permission));
        }
    }
}
