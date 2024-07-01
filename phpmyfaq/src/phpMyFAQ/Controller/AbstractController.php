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

use OpenApi\Attributes as OA;
use phpMyFAQ\Captcha\Captcha;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Template\TemplateException;
use phpMyFAQ\Template\TranslateTwigExtension;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Twig\Extension\DebugExtension;

#[OA\Info(
    version: '3.0',
    description: 'phpMyFAQ includes a REST API and offers APIs for various services like fetching the phpMyFAQ ' .
    'version or doing a search against the phpMyFAQ installation.',
    title: 'REST API for phpMyFAQ 4.0',
    contact: new OA\Contact(
        name: 'phpMyFAQ Team',
        email: 'support@phpmyfaq.de'
    ),
)]
#[OA\Server(url: 'https://localhost', description: 'Local dockerized server')]
#[OA\License(name: 'Mozilla Public Licence 2.0', url: 'https://www.mozilla.org/MPL/2.0/')]
abstract class AbstractController
{
    protected ?Configuration $configuration = null;
    /**
     * Check if the FAQ should be secured.
     * @throws Exception
     */
    public function __construct()
    {
        $this->configuration = Configuration::getConfigurationInstance();
        $this->isSecured();
    }

    /**
     * Returns a Twig rendered template as response.
     *
     * @param string        $pathToTwigFile
     * @param array         $templateVars
     * @param Response|null $response
     * @return Response
     * @throws Exception
     */
    public function render(string $pathToTwigFile, array $templateVars = [], Response $response = null): Response
    {
        $response ??= new Response();
        $twigWrapper = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
        $twigWrapper->addExtension(new DebugExtension());
        $twigWrapper->addExtension(new TranslateTwigExtension());
        $template = $twigWrapper->loadTemplate($pathToTwigFile);

        $response->setContent($template->render($templateVars));

        return $response;
    }

    /**
     * Returns a Twig rendered template as string.
     *
     * @param string $pathToTwigFile
     * @param array  $templateVars
     * @return string
     * @throws Exception
     */
    public function renderView(string $pathToTwigFile, array $templateVars = []): string
    {
        $twigWrapper = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
        $twigWrapper->addExtension(new DebugExtension());
        $twigWrapper->addExtension(new TranslateTwigExtension());
        $template = $twigWrapper->loadTemplate($pathToTwigFile);

        return $template->render($templateVars);
    }

    /**
     * Returns a JsonResponse that uses json_encode().
     *
     * @param mixed $data
     * @param int $status
     * @param string[] $headers
     * @return JsonResponse
     */
    public function json(mixed $data, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }

    /**
     * @throws UnauthorizedHttpException
     */
    protected function hasValidToken(): void
    {
        $request = Request::createFromGlobals();
        if ($this->configuration->get('api.apiClientToken') !== $request->headers->get('x-pmf-token')) {
            throw new UnauthorizedHttpException('"x-pmf-token" is not valid.');
        }
    }

    /**
     * @throws Exception
     */
    protected function isSecured(): void
    {
        $currentUser = CurrentUser::getCurrentUser($this->configuration);
        if (!$currentUser->isLoggedIn() && $this->configuration->get('security.enableLoginOnly')) {
            throw new UnauthorizedHttpException('You are not allowed to view this content.');
        }
    }

    /**
     * @throws UnauthorizedHttpException
     * @throws Exception
     */
    protected function userIsAuthenticated(): void
    {
        if (!CurrentUser::getCurrentUser($this->configuration)->isLoggedIn()) {
            throw new UnauthorizedHttpException('User is not authenticated.');
        }
    }

    /**
     * @throws UnauthorizedHttpException
     * @throws Exception
     */
    protected function userIsSuperAdmin(): void
    {
        if (!CurrentUser::getCurrentUser($this->configuration)->isSuperAdmin()) {
            throw new UnauthorizedHttpException('User is not super admin.');
        }
    }

    /**
     * @throws UnauthorizedHttpException
     * @throws Exception
     */
    protected function userHasGroupPermission(): void
    {
        $currentUser = CurrentUser::getCurrentUser($this->configuration);
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
     * @throws Exception
     */
    protected function userHasUserPermission(): void
    {
        $currentUser = CurrentUser::getCurrentUser($this->configuration);
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
     * @throws Exception
     */
    protected function userHasPermission(PermissionType $permissionType): void
    {
        $configuration = Configuration::getConfigurationInstance();
        $currentUser = CurrentUser::getCurrentUser($configuration);
        if (!$currentUser->perm->hasPermission($currentUser->getUserId(), $permissionType->value)) {
            throw new UnauthorizedHttpException(sprintf('User has no "%s" permission.', $permissionType->value));
        }
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    protected function captchaCodeIsValid(Request $request): bool
    {
        $currentUser = CurrentUser::getCurrentUser($this->configuration);
        $captcha = Captcha::getInstance($this->configuration);
        $captcha->setUserIsLoggedIn($currentUser->isLoggedIn());

        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);

        if ($this->configuration->get('security.enableGoogleReCaptchaV2')) {
            $code = Filter::filterVar($data->{'g-recaptcha-response'} ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
            $code = Filter::filterVar($data->captcha ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        }

        if ($captcha->checkCaptchaCode($code)) {
            return true;
        }

        return false;
    }
}
