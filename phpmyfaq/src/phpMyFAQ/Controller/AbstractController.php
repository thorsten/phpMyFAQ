<?php

/**
 * Abstract Controller for phpMyFAQ
 *
 * This Source Code Form is subject to the terms of the Mozilla protected License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla protected License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-24
 */

namespace phpMyFAQ\Controller;

use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Template\TemplateException;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

abstract class AbstractController
{
    /**
     * Returns a Twig rendered template as response.
     *
     * @param string[]      $templateVars
     * @param Response|null $response
     * @throws TemplateException
     */
    protected function render(string $pathToTwigFile, array $templateVars = [], Response $response = null): Response
    {
        $response ??= new Response();
        $twigWrapper = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
        $template = $twigWrapper->loadTemplate($pathToTwigFile);

        $response->setContent($template->render($templateVars));

        return $response;
    }

    /**
     * Returns a JsonResponse that uses json_encode().
     */
    protected function json(mixed $data, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }

    /**
     * @throws UnauthorizedHttpException
     */
    protected function userIsAuthenticated(): void
    {
        $configuration = Configuration::getConfigurationInstance();
        if (!CurrentUser::getCurrentUser($configuration)->isLoggedIn()) {
            throw new UnauthorizedHttpException('User is not authenticated.');
        }
    }

    /**
     * @throws UnauthorizedHttpException
     */
    protected function userIsSuperAdmin(): void
    {
        $configuration = Configuration::getConfigurationInstance();
        if (!CurrentUser::getCurrentUser($configuration)->isSuperAdmin()) {
            throw new UnauthorizedHttpException('User is not super admin.');
        }
    }

    /**
     * @throws UnauthorizedHttpException
     */
    protected function userHasGroupPermission(): void
    {
        $configuration = Configuration::getConfigurationInstance();
        $currentUser = CurrentUser::getCurrentUser($configuration);
        if (
            !$currentUser->perm->hasPermission($currentUser->getUserId(), PermissionType::USER_ADD->value) ||
            !$currentUser->perm->hasPermission($currentUser->getUserId(), PermissionType::USER_EDIT->value) ||
            !$currentUser->perm->hasPermission($currentUser->getUserId(), PermissionType::USER_DELETE->value) ||
            !$currentUser->perm->hasPermission($currentUser->getUserId(), PermissionType::GROUP_EDIT->value)
        ) {
            throw new UnauthorizedHttpException('User has no group permission.');
        }
    }

    /**
     * @throws UnauthorizedHttpException
     */
    protected function userHasUserPermission(): void
    {
        $configuration = Configuration::getConfigurationInstance();
        $currentUser = CurrentUser::getCurrentUser($configuration);
        if (
            !$currentUser->perm->hasPermission($currentUser->getUserId(), PermissionType::USER_ADD->value) ||
            !$currentUser->perm->hasPermission($currentUser->getUserId(), PermissionType::USER_EDIT->value) ||
            !$currentUser->perm->hasPermission($currentUser->getUserId(), PermissionType::USER_DELETE->value)
        ) {
            throw new UnauthorizedHttpException('User has no user permission.');
        }
    }

    /**
     * @throws UnauthorizedHttpException
     */
    protected function userHasPermission(PermissionType $permissionType): void
    {
        $configuration = Configuration::getConfigurationInstance();
        $currentUser = CurrentUser::getCurrentUser($configuration);
        if (!$currentUser->perm->hasPermission($currentUser->getUserId(), $permissionType)) {
            throw new UnauthorizedHttpException(sprintf('User has no "%s" permission.', $permissionType->value));
        }
    }
}
