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
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla protected License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-24
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller;

use OpenApi\Attributes as OA;
use phpMyFAQ\Captcha\Captcha;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\Exception\ForbiddenException;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Form\FormsServiceProvider;
use phpMyFAQ\Twig\TwigWrapper;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Twig\Error\LoaderError;
use Twig\Extension\ExtensionInterface;
use Twig\TwigFilter;

#[OA\Info(
    version: '3.2',
    description: 'phpMyFAQ includes a REST API and offers APIs for various services like fetching the phpMyFAQ '
    . 'version or doing a search against the phpMyFAQ installation.',
    title: 'REST API for phpMyFAQ 4.2',
    contact: new OA\Contact(name: 'phpMyFAQ Team', email: 'support@phpmyfaq.de'),
)]
#[OA\Server(url: 'https://localhost', description: 'Local dockerized server')]
#[OA\License(name: 'Mozilla Public Licence 2.0', url: 'https://www.mozilla.org/MPL/2.0/')]
abstract class AbstractController
{
    protected ?ContainerBuilder $container = null;

    protected ?Configuration $configuration = null;

    protected ?CurrentUser $currentUser = null;

    protected ?SessionInterface $session = null;

    /** @var ExtensionInterface[] */
    private array $twigExtensions = [];

    /** @var TwigFilter[] */
    private array $twigFilters = [];

    /**
     * Check if the FAQ should be secured.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $this->container = $this->createContainer();
        $this->configuration = $this->container->get(id: 'phpmyfaq.configuration');
        $this->currentUser = $this->container->get(id: 'phpmyfaq.user.current_user');
        $this->session = $this->container->get(id: 'session');

        TwigWrapper::setTemplateSetName($this->configuration->getTemplateSet());
        $this->isSecured();
    }

    /**
     * Returns a Twig-rendered template as a response.
     *
     * @param string[] $context
     * @throws Exception|LoaderError
     */
    public function render(string $file, array $context = [], ?Response $response = null): Response
    {
        $response ??= new Response();
        $twigWrapper = $this->getTwigWrapper();
        $templateWrapper = $twigWrapper->loadTemplate($file);

        $response->setContent($templateWrapper->render($context));

        return $response;
    }

    /**
     * Returns a Twig-rendered template as a string.
     *
     * @param array<string, array<int<0, max>, array<string, mixed>>> $templateVars
     * @throws Exception|LoaderError
     */
    public function renderView(string $pathToTwigFile, array $templateVars = []): string
    {
        $twigWrapper = $this->getTwigWrapper();
        $templateWrapper = $twigWrapper->loadTemplate($pathToTwigFile);

        return $templateWrapper->render($templateVars);
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
     * @throws LoaderError
     */
    public function getTwigWrapper(): TwigWrapper
    {
        $twigWrapper = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');

        foreach ($this->twigExtensions as $twigExtension) {
            $twigWrapper->addExtension($twigExtension);
        }

        foreach ($this->twigFilters as $twigFilter) {
            $twigWrapper->addFilter($twigFilter);
        }

        return $twigWrapper;
    }

    /**
     * @throws UnauthorizedHttpException|\Exception
     */
    protected function hasValidToken(): void
    {
        $request = Request::createFromGlobals();
        if ($this->configuration->get(item: 'api.apiClientToken') !== $request->headers->get(key: 'x-pmf-token')) {
            throw new UnauthorizedHttpException(challenge: '"x-pmf-token" is not valid.');
        }
    }

    /**
     * @throws \Exception
     */
    protected function isSecured(): void
    {
        if (!$this->currentUser->isLoggedIn() && $this->configuration->get(item: 'security.enableLoginOnly')) {
            throw new UnauthorizedHttpException(challenge: 'You are not allowed to view this content.');
        }
    }

    /**
     * @throws UnauthorizedHttpException
     */
    protected function userIsAuthenticated(): void
    {
        if (!$this->currentUser->isLoggedIn()) {
            throw new UnauthorizedHttpException(challenge: 'User is not authenticated.');
        }
    }

    /**
     * @throws UnauthorizedHttpException
     */
    protected function userIsSuperAdmin(): void
    {
        if (!$this->currentUser->isSuperAdmin()) {
            throw new UnauthorizedHttpException(challenge: 'User is not super admin.');
        }
    }

    /**
     * @throws UnauthorizedHttpException|ForbiddenException
     */
    protected function userHasGroupPermission(): void
    {
        if (!$this->currentUser->isLoggedIn()) {
            throw new UnauthorizedHttpException(challenge: 'User is not authenticated.');
        }

        $currentUser = $this->currentUser;
        if (
            !$currentUser->perm->hasPermission($currentUser->getUserId(), PermissionType::USER_ADD->value)
            || !$currentUser->perm->hasPermission($currentUser->getUserId(), PermissionType::USER_EDIT->value)
            || !$currentUser->perm->hasPermission($currentUser->getUserId(), PermissionType::USER_DELETE->value)
            || !$currentUser->perm->hasPermission($currentUser->getUserId(), PermissionType::GROUP_EDIT->value)
        ) {
            throw new ForbiddenException(message: 'User has no group permission.');
        }
    }

    /**
     * @throws UnauthorizedHttpException|ForbiddenException
     */
    protected function userHasUserPermission(): void
    {
        if (!$this->currentUser->isLoggedIn()) {
            throw new UnauthorizedHttpException(challenge: 'User is not authenticated.');
        }

        $currentUser = $this->currentUser;
        if (
            !$currentUser->perm->hasPermission($currentUser->getUserId(), PermissionType::USER_ADD->value)
            || !$currentUser->perm->hasPermission($currentUser->getUserId(), PermissionType::USER_EDIT->value)
            || !$currentUser->perm->hasPermission($currentUser->getUserId(), PermissionType::USER_DELETE->value)
        ) {
            throw new ForbiddenException(message: 'User has no user permission.');
        }
    }

    /**
     * @throws UnauthorizedHttpException|ForbiddenException
     */
    protected function userHasPermission(PermissionType $permissionType): void
    {
        if (!$this->currentUser->isLoggedIn()) {
            throw new UnauthorizedHttpException(challenge: 'User is not authenticated.');
        }

        $currentUser = $this->currentUser;
        if (!$currentUser->perm->hasPermission($currentUser->getUserId(), $permissionType->value)) {
            throw new ForbiddenException(message: sprintf('User has no "%s" permission.', $permissionType->name));
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

        $data = json_decode($request->getContent(), associative: false, depth: 512, flags: JSON_THROW_ON_ERROR);

        $code = Filter::filterVar($data->captcha ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        if ($this->configuration->get(item: 'security.enableGoogleReCaptchaV2')) {
            $code = Filter::filterVar($data->{'g-recaptcha-response'} ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        }

        return $captcha->checkCaptchaCode($code);
    }

    public function isApiEnabled(): bool
    {
        return (bool) $this->configuration->get(item: 'api.enableAccess');
    }

    public function addExtension(ExtensionInterface $extension): void
    {
        $this->twigExtensions[] = $extension;
    }

    public function addFilter(TwigFilter $twigFilter): void
    {
        $this->twigFilters[] = $twigFilter;
    }

    protected function createContainer(): ContainerBuilder
    {
        $containerBuilder = new ContainerBuilder();
        $phpFileLoader = new PhpFileLoader($containerBuilder, new FileLocator(__DIR__));
        try {
            $phpFileLoader->load(resource: '../../services.php');
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }

        // Register Forms services
        FormsServiceProvider::register($containerBuilder);

        return $containerBuilder;
    }
}
