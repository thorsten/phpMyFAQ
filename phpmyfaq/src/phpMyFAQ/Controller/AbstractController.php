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
 * @copyright 2023-2025 phpMyFAQ Team
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
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Twig\Error\LoaderError;
use Twig\Extension\ExtensionInterface;
use Twig\TwigFilter;

#[OA\Info(
    version: '3.0',
    description: 'phpMyFAQ includes a REST API and offers APIs for various services like fetching the phpMyFAQ ' .
    'version or doing a search against the phpMyFAQ installation.',
    title: 'REST API for phpMyFAQ 4.1',
    contact: new OA\Contact(
        name: 'phpMyFAQ Team',
        email: 'support@phpmyfaq.de'
    ),
)]
#[OA\Server(url: 'https://localhost', description: 'Local dockerized server')]
#[OA\License(name: 'Mozilla Public Licence 2.0', url: 'https://www.mozilla.org/MPL/2.0/')]
abstract class AbstractController
{
    protected ?ContainerBuilder $container = null;
    /** @var Configuration|null */
    protected ?Configuration $configuration = null;
    /** @var CurrentUser|null */
    protected ?CurrentUser $currentUser = null;
    /** @var ExtensionInterface[] */
    private array $twigExtensions = [];

    /** @var TwigFilter[] */
    private array $twigFilters = [];

    /**
     * Check if the FAQ should be secured.
     *
     * @throws Exception
     * @throws \Exception
     */
    public function __construct()
    {
        $this->container = $this->createContainer();
        $this->configuration = $this->container->get('phpmyfaq.configuration');
        $this->currentUser = $this->container->get('phpmyfaq.user.current_user');
        TwigWrapper::setTemplateSetName($this->configuration->get('layout.templateSet'));
        $this->isSecured();
    }

    /**
     * Returns a Twig rendered template as response.
     *
     * @param string        $pathToTwigFile
     * @param string[]      $templateVars
     * @param Response|null $response
     * @return Response
     * @throws Exception|LoaderError
     */
    public function render(string $pathToTwigFile, array $templateVars = [], ?Response $response = null): Response
    {
        $response ??= new Response();
        $twigWrapper = $this->getTwigWrapper();
        $template = $twigWrapper->loadTemplate($pathToTwigFile);

        $response->setContent($template->render($templateVars));

        return $response;
    }

    /**
     * Returns a Twig rendered template as string.
     *
     * @param string                                                  $pathToTwigFile
     * @param array<string, array<int<0, max>, array<string, mixed>>> $templateVars
     * @return string
     * @throws Exception|LoaderError
     */
    public function renderView(string $pathToTwigFile, array $templateVars = []): string
    {
        $twigWrapper = $this->getTwigWrapper();
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
     * @return TwigWrapper
     * @throws LoaderError
     */
    public function getTwigWrapper(): TwigWrapper
    {
        $twigWrapper = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');

        if (!empty($this->twigExtensions)) {
            foreach ($this->twigExtensions as $extension) {
                $twigWrapper->addExtension($extension);
            }
        }

        if (!empty($this->twigFilters)) {
            foreach ($this->twigFilters as $filter) {
                $twigWrapper->addFilter($filter);
            }
        }

        return $twigWrapper;
    }

    /**
     * @throws UnauthorizedHttpException|\Exception
     */
    protected function hasValidToken(): void
    {
        $request = Request::createFromGlobals();
        if ($this->configuration->get('api.apiClientToken') !== $request->headers->get('x-pmf-token')) {
            throw new UnauthorizedHttpException('"x-pmf-token" is not valid.');
        }
    }

    /**
     * @throws Exception|\Exception
     */
    protected function isSecured(): void
    {
        if (!$this->currentUser->isLoggedIn() && $this->configuration->get('security.enableLoginOnly')) {
            throw new UnauthorizedHttpException('You are not allowed to view this content.');
        }
    }

    /**
     * @throws UnauthorizedHttpException
     */
    protected function userIsAuthenticated(): void
    {
        if (!$this->currentUser->isLoggedIn()) {
            throw new UnauthorizedHttpException('User is not authenticated.');
        }
    }

    /**
     * @throws UnauthorizedHttpException
     */
    protected function userIsSuperAdmin(): void
    {
        if (!$this->currentUser->isSuperAdmin()) {
            throw new UnauthorizedHttpException('User is not super admin.');
        }
    }

    /**
     * @throws UnauthorizedHttpException
     */
    protected function userHasGroupPermission(): void
    {
        $currentUser = $this->currentUser;
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
        $currentUser = $this->currentUser;
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
        $currentUser = $this->currentUser;
        if (!$currentUser->perm->hasPermission($currentUser->getUserId(), $permissionType->value)) {
            throw new UnauthorizedHttpException(sprintf('User has no "%s" permission.', $permissionType->value));
        }
    }

    /**
     * @throws \JsonException
     * @throws \Exception
     */
    protected function captchaCodeIsValid(Request $request): bool
    {
        $captcha = Captcha::getInstance($this->configuration);
        $captcha->setUserIsLoggedIn($this->currentUser->isLoggedIn());

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

    public function isApiEnabled(): bool
    {
        return (bool) $this->configuration->get('api.enableAccess');
    }

    public function addExtension(ExtensionInterface $extension): void
    {
        $this->twigExtensions[] = $extension;
    }

    public function addFilter(TwigFilter $filter): void
    {
        $this->twigFilters[] = $filter;
    }

    protected function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__));
        try {
            $loader->load('../../services.php');
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        return $container;
    }
}
