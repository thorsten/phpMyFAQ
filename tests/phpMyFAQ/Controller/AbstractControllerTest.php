<?php

namespace phpMyFAQ\Controller;

use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\Administration\SkipsAuthenticationCheck;
use phpMyFAQ\Controller\Exception\ForbiddenException;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Permission\BasicPermission;
use phpMyFAQ\Permission\RightGrant;
use phpMyFAQ\Permission\RightScope;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Twig\Extension\ExtensionInterface;
use Twig\TwigFilter;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(AbstractController::class)]
#[UsesNamespace('phpMyFAQ')]
class AbstractControllerTest extends TestCase
{
    private AbstractController $abstractController;
    private Configuration $configurationMock;
    private CurrentUser $currentUserMock;
    private BasicPermission $permissionMock;

    protected function setUp(): void
    {
        // Create mocks
        $this->configurationMock = $this->createMock(Configuration::class);
        $this->currentUserMock = $this->createMock(CurrentUser::class);
        $this->permissionMock = $this->createMock(BasicPermission::class);

        // Setup currentUser mock with permission object
        $this->currentUserMock->perm = $this->permissionMock;

        // Create a simplified test implementation that bypasses container creation
        $this->abstractController = new class($this->configurationMock, $this->currentUserMock) extends
            AbstractController {
            private Configuration $testConfiguration;
            private CurrentUser $testCurrentUser;

            public function __construct(Configuration $config, CurrentUser $user)
            {
                // Skip parent constructor completely to avoid container issues
                $this->configuration = $config;
                $this->currentUser = $user;
                $this->testConfiguration = $config;
                $this->testCurrentUser = $user;
            }

            // Make protected methods public for testing
            public function hasValidTokenPublic(): void
            {
                $this->hasValidToken();
            }

            public function isSecuredPublic(): void
            {
                $this->isSecured();
            }

            public function userIsAuthenticatedPublic(): void
            {
                $this->userIsAuthenticated();
            }

            public function userIsSuperAdminPublic(): void
            {
                $this->userIsSuperAdmin();
            }

            public function userHasGroupPermissionPublic(): void
            {
                $this->userHasGroupPermission();
            }

            public function userHasUserPermissionPublic(): void
            {
                $this->userHasUserPermission();
            }

            public function userHasPermissionPublic(PermissionType $permissionType): void
            {
                $this->userHasPermission($permissionType);
            }

            public function captchaCodeIsValidPublic(Request $request): bool
            {
                return $this->captchaCodeIsValid($request);
            }

            public function userHasPermissionForCategoriesPublic(PermissionType $permissionType, array $ids): void
            {
                $this->userHasPermissionForCategories($permissionType, $ids);
            }

            public function userHasPermissionForLanguagePublic(PermissionType $permissionType, string $lang): void
            {
                $this->userHasPermissionForLanguage($permissionType, $lang);
            }

            public function userHasPermissionForCategoriesInLanguagePublic(
                PermissionType $permissionType,
                array $ids,
                string $lang,
            ): void {
                $this->userHasPermissionForCategoriesInLanguage($permissionType, $ids, $lang);
            }

            public function userMayPublishInPublic(array $categoryIds, string $language): bool
            {
                return $this->userMayPublishIn($categoryIds, $language);
            }

            public function userMayPublic(PermissionType $permissionType): bool
            {
                return $this->userMay($permissionType);
            }

            public function setSessionPublic(FlashBagAwareSessionInterface $session): void
            {
                $this->session = $session;
            }

            public function verifySessionCsrfTokenPublic(string $page, string $requestToken): bool
            {
                return $this->verifySessionCsrfToken($page, $requestToken);
            }
        };
    }

    public function testJsonReturnsJsonResponse(): void
    {
        $data = ['key' => 'value'];
        $status = 201;
        $headers = ['Content-Type' => 'application/json'];

        $response = $this->abstractController->json($data, $status, $headers);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals($status, $response->getStatusCode());
        $this->assertEquals('{"key":"value"}', $response->getContent());
    }

    public function testJsonWithDefaultParameters(): void
    {
        $data = ['test' => 'data'];

        $response = $this->abstractController->json($data);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('{"test":"data"}', $response->getContent());
    }

    public function testIsApiEnabledReturnsTrueWhenEnabled(): void
    {
        $this->configurationMock->expects($this->once())->method('get')->with('api.enableAccess')->willReturn('1');

        $result = $this->abstractController->isApiEnabled();

        $this->assertTrue($result);
    }

    public function testIsApiEnabledReturnsFalseWhenDisabled(): void
    {
        $this->configurationMock->expects($this->once())->method('get')->with('api.enableAccess')->willReturn('0');

        $result = $this->abstractController->isApiEnabled();

        $this->assertFalse($result);
    }

    public function testIsApiEnabledWithNullValue(): void
    {
        $this->configurationMock->expects($this->once())->method('get')->with('api.enableAccess')->willReturn(null);

        $result = $this->abstractController->isApiEnabled();

        $this->assertFalse($result);
    }

    public function testIsApiEnabledWithNumericValues(): void
    {
        $this->configurationMock->expects($this->once())->method('get')->with('api.enableAccess')->willReturn(1);

        $result = $this->abstractController->isApiEnabled();
        $this->assertTrue($result);
    }

    public function testAddExtension(): void
    {
        $extension = $this->createStub(ExtensionInterface::class);

        $this->abstractController->addExtension($extension);

        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    public function testAddFilter(): void
    {
        $filter = new TwigFilter('test', function ($value) {
            return $value;
        });

        $this->abstractController->addFilter($filter);

        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    public function testUserIsAuthenticatedThrowsExceptionWhenNotLoggedIn(): void
    {
        $this->currentUserMock->expects($this->once())->method('isLoggedIn')->willReturn(false);

        $this->expectException(UnauthorizedHttpException::class);

        $this->abstractController->userIsAuthenticatedPublic();
    }

    public function testUserIsAuthenticatedSucceedsWhenLoggedIn(): void
    {
        $this->currentUserMock->expects($this->once())->method('isLoggedIn')->willReturn(true);

        $this->abstractController->userIsAuthenticatedPublic();
        $this->assertTrue(true);
    }

    public function testUserIsSuperAdminThrowsExceptionWhenNotSuperAdmin(): void
    {
        $this->currentUserMock->expects($this->once())->method('isSuperAdmin')->willReturn(false);

        $this->expectException(UnauthorizedHttpException::class);

        $this->abstractController->userIsSuperAdminPublic();
    }

    public function testUserIsSuperAdminSucceedsWhenSuperAdmin(): void
    {
        $this->currentUserMock->expects($this->once())->method('isSuperAdmin')->willReturn(true);

        $this->abstractController->userIsSuperAdminPublic();
        $this->assertTrue(true);
    }

    public function testUserHasGroupPermissionThrowsExceptionWhenMissingPermissions(): void
    {
        // Mock that user is logged in (so we get to the permission check)
        $this->currentUserMock->expects($this->once())->method('isLoggedIn')->willReturn(true);

        $this->currentUserMock->expects($this->once())->method('getUserId')->willReturn(1); // Only called once since the exception is thrown on the first check

        // Mock that the user lacks the first required permission (USER_ADD)
        $this->permissionMock
            ->expects($this->once())
            ->method('hasPermission')
            ->with(1, PermissionType::USER_ADD->value)
            ->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->abstractController->userHasGroupPermissionPublic();
    }

    public function testUserHasGroupPermissionSucceedsWhenAllPermissionsPresent(): void
    {
        // Mock that user is logged in
        $this->currentUserMock->expects($this->once())->method('isLoggedIn')->willReturn(true);

        $this->currentUserMock->expects($this->exactly(4))->method('getUserId')->willReturn(1);

        // Mock that user has all required permissions
        $this->permissionMock
            ->expects($this->exactly(4))
            ->method('hasPermission')
            ->willReturnCallback(function ($userId, $permission) {
                $this->assertEquals(1, $userId);
                $this->assertContains($permission, [
                    PermissionType::USER_ADD->value,
                    PermissionType::USER_EDIT->value,
                    PermissionType::USER_DELETE->value,
                    PermissionType::GROUP_EDIT->value,
                ]);
                return true;
            });

        $this->abstractController->userHasGroupPermissionPublic();
        $this->assertTrue(true);
    }

    public function testUserHasUserPermissionThrowsExceptionWhenMissingPermissions(): void
    {
        // Mock that user is logged in (so we get to the permission check)
        $this->currentUserMock->expects($this->once())->method('isLoggedIn')->willReturn(true);

        $this->currentUserMock->expects($this->once())->method('getUserId')->willReturn(1); // Only called once since exception is thrown on first check

        // Mock that user lacks the first required permission (USER_ADD)
        $this->permissionMock
            ->expects($this->once())
            ->method('hasPermission')
            ->with(1, PermissionType::USER_ADD->value)
            ->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->abstractController->userHasUserPermissionPublic();
    }

    public function testUserHasUserPermissionSucceedsWhenAllPermissionsPresent(): void
    {
        // Mock that user is logged in
        $this->currentUserMock->expects($this->once())->method('isLoggedIn')->willReturn(true);

        $this->currentUserMock->expects($this->exactly(3))->method('getUserId')->willReturn(1);

        // Mock that user has all required permissions
        $this->permissionMock
            ->expects($this->exactly(3))
            ->method('hasPermission')
            ->willReturnCallback(function ($userId, $permission) {
                $this->assertEquals(1, $userId);
                $this->assertContains($permission, [
                    PermissionType::USER_ADD->value,
                    PermissionType::USER_EDIT->value,
                    PermissionType::USER_DELETE->value,
                ]);
                return true;
            });

        $this->abstractController->userHasUserPermissionPublic();
        $this->assertTrue(true);
    }

    public function testUserHasPermissionThrowsExceptionWhenMissingPermission(): void
    {
        // Mock that user is logged in (so we get to the permission check)
        $this->currentUserMock->expects($this->once())->method('isLoggedIn')->willReturn(true);

        $permissionType = PermissionType::FAQ_ADD;
        $this->currentUserMock->expects($this->once())->method('getUserId')->willReturn(1);

        $this->permissionMock
            ->expects($this->once())
            ->method('hasPermission')
            ->with(1, $permissionType->value)
            ->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->abstractController->userHasPermissionPublic($permissionType);
    }

    public function testUserHasPermissionSucceedsWhenPermissionPresent(): void
    {
        // Mock that user is logged in
        $this->currentUserMock->expects($this->once())->method('isLoggedIn')->willReturn(true);

        $permissionType = PermissionType::FAQ_ADD;
        $this->currentUserMock->expects($this->once())->method('getUserId')->willReturn(1);

        $this->permissionMock
            ->expects($this->once())
            ->method('hasPermission')
            ->with(1, $permissionType->value)
            ->willReturn(true);

        $this->abstractController->userHasPermissionPublic($permissionType);
        $this->assertTrue(true);
    }

    public function testIsSecuredThrowsExceptionWhenNotLoggedInAndLoginRequired(): void
    {
        $this->currentUserMock->expects($this->once())->method('isLoggedIn')->willReturn(false);

        $this->configurationMock
            ->expects($this->once())
            ->method('get')
            ->with('security.enableLoginOnly')
            ->willReturn(true);

        $this->expectException(UnauthorizedHttpException::class);

        $this->abstractController->isSecuredPublic();
    }

    public function testIsSecuredSucceedsWhenLoggedIn(): void
    {
        $this->currentUserMock->expects($this->once())->method('isLoggedIn')->willReturn(true);

        $this->abstractController->isSecuredPublic();
        $this->assertTrue(true);
    }

    public function testIsSecuredSucceedsWhenLoginNotRequired(): void
    {
        $this->currentUserMock->expects($this->once())->method('isLoggedIn')->willReturn(false);

        $this->configurationMock
            ->expects($this->once())
            ->method('get')
            ->with('security.enableLoginOnly')
            ->willReturn(false);

        $this->abstractController->isSecuredPublic();
        $this->assertTrue(true);
    }

    public function testIsSecuredSkipsCheckForControllersImplementingSkipsAuthenticationCheck(): void
    {
        $this->currentUserMock->expects($this->never())->method('isLoggedIn');
        $this->configurationMock->expects($this->never())->method('get');

        $loginController = new class($this->configurationMock, $this->currentUserMock) extends
            AbstractController implements SkipsAuthenticationCheck {
            public function __construct(Configuration $config, CurrentUser $user)
            {
                // Skip parent constructor completely to avoid container issues
                $this->configuration = $config;
                $this->currentUser = $user;
            }

            public function isSecuredPublic(): void
            {
                $this->isSecured();
            }
        };

        $loginController->isSecuredPublic();
        $this->assertTrue(true); // Login page stays reachable in "login only" mode
    }

    public function testIsSecuredSucceedsOnLoginPathWhenNotLoggedInAndLoginRequired(): void
    {
        $_SERVER['REQUEST_URI'] = '/login';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $this->currentUserMock->expects($this->once())->method('isLoggedIn')->willReturn(false);

        $this->configurationMock
            ->expects($this->once())
            ->method('get')
            ->with('security.enableLoginOnly')
            ->willReturn(true);

        try {
            $this->abstractController->isSecuredPublic();
            $this->assertTrue(true);
        } finally {
            unset($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']);
        }
    }

    public function testIsSecuredSucceedsOnAuthenticatePathWhenNotLoggedInAndLoginRequired(): void
    {
        $_SERVER['REQUEST_URI'] = '/authenticate';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $this->currentUserMock->expects($this->once())->method('isLoggedIn')->willReturn(false);

        $this->configurationMock
            ->expects($this->once())
            ->method('get')
            ->with('security.enableLoginOnly')
            ->willReturn(true);

        try {
            $this->abstractController->isSecuredPublic();
            $this->assertTrue(true);
        } finally {
            unset($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']);
        }
    }

    #[DataProvider('publicStaticPathsProvider')]
    public function testIsSecuredSucceedsOnPublicStaticPathWhenNotLoggedInAndLoginRequired(string $path): void
    {
        $_SERVER['REQUEST_URI'] = $path;
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $this->currentUserMock->expects($this->once())->method('isLoggedIn')->willReturn(false);

        $this->configurationMock
            ->expects($this->once())
            ->method('get')
            ->with('security.enableLoginOnly')
            ->willReturn(true);

        try {
            $this->abstractController->isSecuredPublic();
            $this->assertTrue(true);
        } finally {
            unset($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']);
        }
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function publicStaticPathsProvider(): array
    {
        return [
            'contact' => ['/contact.html'],
            'imprint' => ['/imprint.html'],
            'privacy' => ['/privacy.html'],
            'terms' => ['/terms.html'],
            'accessibility' => ['/accessibility.html'],
        ];
    }

    #[DataProvider('publicAuthenticationPathsProvider')]
    public function testIsSecuredSucceedsOnPublicAuthenticationPath(
        string $routingContext,
        string $scriptName,
        string $requestUri,
    ): void {
        $this->currentUserMock->expects($this->once())->method('isLoggedIn')->willReturn(false);
        $this->configurationMock
            ->expects($this->once())
            ->method('get')
            ->with('security.enableLoginOnly')
            ->willReturn(true);

        $this->runIsSecuredInRoutingContext($routingContext, $scriptName, $requestUri);
        $this->assertTrue(true);
    }

    /**
     * The REST API is served by api/index.php, so production requests carry the base
     * URL "/api" and a path info without it.
     *
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function publicAuthenticationPathsProvider(): array
    {
        return [
            'passkey login options' => ['api', '/api/index.php', '/api/webauthn/prepare-login'],
            'passkey login' => ['api', '/api/index.php', '/api/webauthn/login'],
            'REST API login' => ['api', '/api/index.php', '/api/v4.0/login'],
            'passkey login in a subdirectory' => ['api', '/faq/api/index.php', '/faq/api/webauthn/login'],
            'mailed password reset link' => ['public', '/index.php', '/user/reset-password?u=1&exp=2&sig=3'],
            'Entra ID logout' => ['public', '/index.php', '/auth/azure/logout'],
            'legacy Entra ID logout' => ['public', '/index.php', '/services/azure/logout'],
            'login in a subdirectory' => ['public', '/faq/index.php', '/faq/login'],
            'OAuth2 token endpoint' => ['api', '/api/index.php', '/api/oauth/token'],
            'OAuth2 authorization endpoint' => ['api', '/api/index.php', '/api/oauth/authorize?client_id=1'],
            'OAuth2 server metadata' => ['public', '/index.php', '/.well-known/oauth-authorization-server'],
            'captcha image' => ['api', '/api/index.php', '/api/captcha?gen=img&ck=1'],
            'contact form submission' => ['api', '/api/index.php', '/api/contact'],
            'contact form submission in a subdirectory' => ['api', '/faq/api/index.php', '/faq/api/contact'],
        ];
    }

    #[DataProvider('protectedPathsProvider')]
    public function testIsSecuredRejectsPathOutsideTheAllowlistOfItsRoutingContext(
        string $routingContext,
        string $scriptName,
        string $requestUri,
    ): void {
        $this->currentUserMock->expects($this->once())->method('isLoggedIn')->willReturn(false);
        $this->configurationMock
            ->expects($this->once())
            ->method('get')
            ->with('security.enableLoginOnly')
            ->willReturn(true);

        $this->expectException(UnauthorizedHttpException::class);
        $this->runIsSecuredInRoutingContext($routingContext, $scriptName, $requestUri);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function protectedPathsProvider(): array
    {
        return [
            'API path without a public entry' => ['api', '/api/index.php', '/api/v4.0/faqs'],
            'frontend-only path on the API' => ['api', '/api/index.php', '/api/login'],
            'API-only path on the frontend' => ['public', '/index.php', '/webauthn/login'],
            'admin API' => ['admin-api', '/admin/api/index.php', '/admin/api/login'],
            'API path next to the OAuth2 endpoints' => ['api', '/api/index.php', '/api/oauth/clients'],
            'passkey removal' => ['api', '/api/index.php', '/api/webauthn/revoke'],
            'question submission' => ['api', '/api/index.php', '/api/question'],
            'REST API registration lookalike' => ['api', '/api/index.php', '/api/v4.0/register/1'],
            'captcha path on the frontend' => ['public', '/index.php', '/captcha'],
            'contact endpoint on the frontend' => ['public', '/index.php', '/contact'],
            'OAuth2 token path on the frontend' => ['public', '/index.php', '/oauth/token'],
            'contact page on the API' => ['api', '/api/index.php', '/api/contact.html'],
            'user control panel' => ['public', '/index.php', '/user/ucp'],
            'OAuth2 token path on the admin API' => ['admin-api', '/admin/api/index.php', '/admin/api/oauth/token'],
        ];
    }

    /**
     * An instance that lets people register has to let them reach the registration form and
     * the endpoints it submits to before they can log in.
     */
    #[DataProvider('registrationPathsProvider')]
    public function testIsSecuredSucceedsOnRegistrationPathWhileRegistrationIsEnabled(
        string $routingContext,
        string $scriptName,
        string $requestUri,
    ): void {
        $this->currentUserMock->expects($this->once())->method('isLoggedIn')->willReturn(false);
        $this->configurationMock
            ->method('get')
            ->willReturnMap([
                ['security.enableLoginOnly',    true],
                ['security.enableRegistration', true],
            ]);

        $this->runIsSecuredInRoutingContext($routingContext, $scriptName, $requestUri);
        $this->assertTrue(true);
    }

    #[DataProvider('registrationPathsProvider')]
    public function testIsSecuredRejectsRegistrationPathWhileRegistrationIsDisabled(
        string $routingContext,
        string $scriptName,
        string $requestUri,
    ): void {
        $this->currentUserMock->expects($this->once())->method('isLoggedIn')->willReturn(false);
        $this->configurationMock
            ->method('get')
            ->willReturnMap([
                ['security.enableLoginOnly',    true],
                ['security.enableRegistration', false],
            ]);

        $this->expectException(UnauthorizedHttpException::class);
        $this->runIsSecuredInRoutingContext($routingContext, $scriptName, $requestUri);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function registrationPathsProvider(): array
    {
        return [
            'registration page' => ['public', '/index.php', '/user/register'],
            'registration page in a subdirectory' => ['public', '/faq/index.php', '/faq/user/register'],
            'registration form submission' => ['api', '/api/index.php', '/api/register'],
            'REST API registration' => ['api', '/api/index.php', '/api/v4.0/register'],
            'passkey sign-up options' => ['api', '/api/index.php', '/api/webauthn/prepare'],
            'passkey sign-up' => ['api', '/api/index.php', '/api/webauthn/register'],
        ];
    }

    /**
     * Enabled registration opens the registration paths of the routing context only, and
     * nothing else.
     */
    #[DataProvider('pathsEnabledRegistrationDoesNotOpenProvider')]
    public function testIsSecuredRejectsOtherPathsWhileRegistrationIsEnabled(
        string $routingContext,
        string $scriptName,
        string $requestUri,
    ): void {
        $this->currentUserMock->expects($this->once())->method('isLoggedIn')->willReturn(false);
        $this->configurationMock
            ->method('get')
            ->willReturnMap([
                ['security.enableLoginOnly',    true],
                ['security.enableRegistration', true],
            ]);

        $this->expectException(UnauthorizedHttpException::class);
        $this->runIsSecuredInRoutingContext($routingContext, $scriptName, $requestUri);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function pathsEnabledRegistrationDoesNotOpenProvider(): array
    {
        return [
            'registration page path on the API' => ['api', '/api/index.php', '/api/user/register'],
            'registration endpoint on the frontend' => ['public', '/index.php', '/register'],
            'registration endpoint on the admin API' => ['admin-api', '/admin/api/index.php', '/admin/api/register'],
            'REST API path' => ['api', '/api/index.php', '/api/v4.0/faqs'],
            'start page' => ['public', '/index.php', '/'],
        ];
    }

    private function runIsSecuredInRoutingContext(string $routingContext, string $scriptName, string $requestUri): void
    {
        $container = new \Symfony\Component\DependencyInjection\Container();
        $container->set('kernel', new \phpMyFAQ\Kernel(routingContext: $routingContext));
        new \ReflectionProperty(AbstractController::class, 'container')->setValue(
            $this->abstractController,
            $container,
        );

        $serverBackup = $_SERVER;
        $_SERVER['REQUEST_URI'] = $requestUri;
        $_SERVER['SCRIPT_NAME'] = $scriptName;
        $_SERVER['SCRIPT_FILENAME'] = '/var/www/html' . $scriptName;

        try {
            $this->abstractController->isSecuredPublic();
        } finally {
            $_SERVER = $serverBackup;
        }
    }

    public function testSetContainerReEvaluatesIsSecuredWhenContainerChanges(): void
    {
        $controller = new class() extends AbstractController {
            public function __construct()
            {
            }
        };

        $session = $this->createMock(FlashBagAwareSessionInterface::class);

        $firstConfiguration = $this->createMock(Configuration::class);
        $firstConfiguration->expects($this->once())->method('getTemplateSet')->willReturn('default');

        $firstCurrentUser = $this->createMock(CurrentUser::class);
        $firstCurrentUser->expects($this->once())->method('isLoggedIn')->willReturn(true);

        $firstContainer = $this->createMock(ContainerInterface::class);
        $firstContainer
            ->method('get')
            ->willReturnCallback(static function (string $id) use ($firstConfiguration, $firstCurrentUser, $session) {
                return match ($id) {
                    'phpmyfaq.configuration' => $firstConfiguration,
                    'phpmyfaq.user.current_user' => $firstCurrentUser,
                    'session' => $session,
                    default => throw new \InvalidArgumentException(sprintf('Unexpected service id "%s".', $id)),
                };
            });

        $controller->setContainer($firstContainer);

        $secondConfiguration = $this->createMock(Configuration::class);
        $secondConfiguration->expects($this->once())->method('getTemplateSet')->willReturn('default');
        $secondConfiguration
            ->method('get')
            ->willReturnCallback(static fn(string $item) => match ($item) {
                'security.enableLoginOnly' => true,
                default => null,
            });

        $secondCurrentUser = $this->createMock(CurrentUser::class);
        $secondCurrentUser->expects($this->once())->method('isLoggedIn')->willReturn(false);

        $secondContainer = $this->createMock(ContainerInterface::class);
        $secondContainer
            ->method('get')
            ->willReturnCallback(static function (string $id) use ($secondConfiguration, $secondCurrentUser, $session) {
                return match ($id) {
                    'phpmyfaq.configuration' => $secondConfiguration,
                    'phpmyfaq.user.current_user' => $secondCurrentUser,
                    'session' => $session,
                    default => throw new \InvalidArgumentException(sprintf('Unexpected service id "%s".', $id)),
                };
            });

        $this->expectException(UnauthorizedHttpException::class);
        $controller->setContainer($secondContainer);
    }

    public function testConstructionReusesTheSharedKernelContainer(): void
    {
        $sharedContainer = $this->createMock(ContainerInterface::class);
        $session = $this->createMock(FlashBagAwareSessionInterface::class);
        $sharedContainer
            ->method('get')
            ->willReturnCallback(function (string $id) use ($session) {
                return match ($id) {
                    'phpmyfaq.configuration' => $this->configurationMock,
                    'phpmyfaq.user.current_user' => $this->currentUserMock,
                    'session' => $session,
                    default => throw new \InvalidArgumentException(sprintf('Unexpected service id "%s".', $id)),
                };
            });

        \phpMyFAQ\Container\ContainerRegistry::set($sharedContainer);

        try {
            $controller = new class extends AbstractController {};

            $reflectionProperty = new \ReflectionProperty(AbstractController::class, 'container');
            $this->assertSame($sharedContainer, $reflectionProperty->getValue($controller));
        } finally {
            \phpMyFAQ\Container\ContainerRegistry::reset();
        }
    }

    public function testHasValidTokenThrowsExceptionWithInvalidToken(): void
    {
        $this->configurationMock
            ->expects($this->once())
            ->method('get')
            ->with('api.apiClientToken')
            ->willReturn('valid-token');

        // Mock a request with invalid token in global state
        $_SERVER['HTTP_X_PMF_TOKEN'] = 'invalid-token';

        $this->expectException(UnauthorizedHttpException::class);

        $this->abstractController->hasValidTokenPublic();

        // Cleanup
        unset($_SERVER['HTTP_X_PMF_TOKEN']);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function emptyConfiguredTokenProvider(): array
    {
        return [
            'empty string' => [''],
            'null' => [null],
            'non-string' => [false],
        ];
    }

    /**
     * An installation whose api.apiClientToken is empty must not accept a request that simply
     * omits or blanks the header: hash_equals('', '') would otherwise open the API to everybody.
     */
    #[DataProvider('emptyConfiguredTokenProvider')]
    public function testHasValidTokenThrowsExceptionWithEmptyConfiguredToken(mixed $configuredToken): void
    {
        $this->configurationMock
            ->expects($this->once())
            ->method('get')
            ->with('api.apiClientToken')
            ->willReturn($configuredToken);

        $_SERVER['HTTP_X_PMF_TOKEN'] = '';

        try {
            $this->expectException(UnauthorizedHttpException::class);
            $this->abstractController->hasValidTokenPublic();
        } finally {
            unset($_SERVER['HTTP_X_PMF_TOKEN']);
        }
    }

    public function testHasValidTokenSucceedsWithValidToken(): void
    {
        $this->configurationMock
            ->expects($this->once())
            ->method('get')
            ->with('api.apiClientToken')
            ->willReturn('valid-token');

        // Mock a request with valid token in global state
        $_SERVER['HTTP_X_PMF_TOKEN'] = 'valid-token';

        $this->abstractController->hasValidTokenPublic();
        $this->assertTrue(true);

        // Cleanup
        unset($_SERVER['HTTP_X_PMF_TOKEN']);
    }

    public function testCaptchaCodeIsValidWithInvalidJson(): void
    {
        $request = Request::create('/', 'POST', [], [], [], [], 'invalid-json');
        $request->headers->set('Content-Type', 'application/json');

        $this->expectException(\JsonException::class);

        $this->abstractController->captchaCodeIsValidPublic($request);
    }

    public function testCaptchaCodeIsValidUsesTheRecaptchaResponseWhenGoogleRecaptchaIsEnabled(): void
    {
        $this->configurationMock
            ->method('get')
            ->willReturnCallback(static fn(string $item) => match ($item) {
                'security.enableGoogleReCaptchaV2' => true,
                default => null,
            });
        $this->currentUserMock->method('isLoggedIn')->willReturn(true);

        $request = Request::create('/', 'POST', [], [], [], [], '{"g-recaptcha-response":"token"}');
        $request->headers->set('Content-Type', 'application/json');

        $this->assertTrue($this->abstractController->captchaCodeIsValidPublic($request));
    }

    public function testUserHasPermissionForCategoriesThrowsWhenNotLoggedIn(): void
    {
        $this->currentUserMock->method('isLoggedIn')->willReturn(false);

        $this->expectException(UnauthorizedHttpException::class);

        $this->abstractController->userHasPermissionForCategoriesPublic(PermissionType::FAQ_EDIT, [1]);
    }

    public function testUserHasPermissionForLanguageThrowsWhenNotLoggedIn(): void
    {
        $this->currentUserMock->method('isLoggedIn')->willReturn(false);

        $this->expectException(UnauthorizedHttpException::class);

        $this->abstractController->userHasPermissionForLanguagePublic(PermissionType::FAQ_EDIT, 'en');
    }

    public function testUserMayPublishInReturnsFalseWhenNotLoggedIn(): void
    {
        $this->currentUserMock->method('isLoggedIn')->willReturn(false);

        $this->assertFalse($this->abstractController->userMayPublishInPublic([1], 'en'));
    }

    public function testUserMayPublishInReturnsFalseWithoutLanguagePermission(): void
    {
        $this->currentUserMock->method('isLoggedIn')->willReturn(true);
        $this->currentUserMock->method('getUserId')->willReturn(1);
        $this->permissionMock->method('hasPermission')->willReturn(true);
        $this->permissionMock
            ->method('getScopeForRight')
            ->willReturn(RightScope::ofGrants(new RightGrant(languages: ['de'])));

        $this->assertFalse($this->abstractController->userMayPublishInPublic([1], 'en'));
    }

    public function testUserMayPublishInReturnsFalseWithoutCategoryPermission(): void
    {
        $this->currentUserMock->method('isLoggedIn')->willReturn(true);
        $this->currentUserMock->method('getUserId')->willReturn(1);
        $this->permissionMock->method('hasPermission')->willReturn(true);
        $this->permissionMock->method('getScopeForRight')->willReturn(RightScope::ofGrants(new RightGrant([1])));

        $this->assertFalse($this->abstractController->userMayPublishInPublic([1, 2], 'en'));
    }

    public function testUserMayPublishInReturnsTrueWithinScope(): void
    {
        $this->currentUserMock->method('isLoggedIn')->willReturn(true);
        $this->currentUserMock->method('getUserId')->willReturn(1);
        $this->permissionMock->method('hasPermission')->willReturn(true);
        $this->permissionMock->method('getScopeForRight')->willReturn(RightScope::ofGrants(new RightGrant([1, 2])));

        $this->assertTrue($this->abstractController->userMayPublishInPublic([1, 2], 'en'));
    }

    public function testUserMayPublishInDoesNotMergeRestrictionsOfSeparateGrants(): void
    {
        $this->actAsUserHoldingGermanEverywhereAndCategoryOne();
        $this->permissionMock->method('hasPermission')->willReturn(true);

        $this->assertFalse($this->abstractController->userMayPublishInPublic([2], 'en'));
        $this->assertFalse($this->abstractController->userMayPublishInPublic([], 'en'));
        $this->assertTrue($this->abstractController->userMayPublishInPublic([2], 'de'));
        $this->assertTrue($this->abstractController->userMayPublishInPublic([1], 'en'));
    }

    public function testCategoriesInLanguageRequiresAuthentication(): void
    {
        $this->currentUserMock->method('isLoggedIn')->willReturn(false);

        $this->expectException(UnauthorizedHttpException::class);

        $this->abstractController->userHasPermissionForCategoriesInLanguagePublic(PermissionType::FAQ_EDIT, [1], 'en');
    }

    public function testCategoriesInLanguageDeniesLanguageWithoutAnyGrant(): void
    {
        $this->currentUserMock->method('isLoggedIn')->willReturn(true);
        $this->currentUserMock->method('getUserId')->willReturn(1);
        $this->permissionMock
            ->method('getScopeForRight')
            ->willReturn(RightScope::ofGrants(new RightGrant(languages: ['de'])));

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_EDIT" permission for language "en".');

        $this->abstractController->userHasPermissionForCategoriesInLanguagePublic(PermissionType::FAQ_EDIT, [1], 'en');
    }

    public function testLanguageCheckDeniesUnsupportedLanguageCodeEvenForAnUnrestrictedRight(): void
    {
        $this->currentUserMock->method('isLoggedIn')->willReturn(true);
        $this->currentUserMock->method('getUserId')->willReturn(1);
        $this->currentUserMock->method('isSuperAdmin')->willReturn(false);
        $this->permissionMock->method('getScopeForRight')->willReturn(RightScope::unrestricted());

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_EDIT" permission for language "not-a-language".');

        $this->abstractController->userHasPermissionForLanguagePublic(PermissionType::FAQ_EDIT, 'not-a-language');
    }

    public function testLanguageCheckAllowsLanguageCoveredByAnyGrant(): void
    {
        $this->currentUserMock->method('isLoggedIn')->willReturn(true);
        $this->currentUserMock->method('getUserId')->willReturn(1);
        $this->permissionMock
            ->method('getScopeForRight')
            ->willReturn(RightScope::ofGrants(new RightGrant([1], ['de'])));

        $this->abstractController->userHasPermissionForLanguagePublic(PermissionType::FAQ_EDIT, 'de');

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_EDIT" permission for language "en".');

        $this->abstractController->userHasPermissionForLanguagePublic(PermissionType::FAQ_EDIT, 'en');
    }

    public function testCategoryCheckAllowsCategoryCoveredByAnyGrant(): void
    {
        $this->currentUserMock->method('isLoggedIn')->willReturn(true);
        $this->currentUserMock->method('getUserId')->willReturn(1);
        $this->permissionMock
            ->method('getScopeForRight')
            ->willReturn(RightScope::ofGrants(new RightGrant([1], ['de'])));

        $this->abstractController->userHasPermissionForCategoriesPublic(PermissionType::FAQ_EDIT, [1]);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_EDIT" permission for category 2.');

        $this->abstractController->userHasPermissionForCategoriesPublic(PermissionType::FAQ_EDIT, [1, 2]);
    }

    public function testCategoryCheckDeniesUncategorizedContentToCategoryRestrictedUser(): void
    {
        $this->currentUserMock->method('isLoggedIn')->willReturn(true);
        $this->currentUserMock->method('getUserId')->willReturn(1);
        $this->permissionMock->method('getScopeForRight')->willReturn(RightScope::ofGrants(new RightGrant([1])));

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_EDIT" permission for uncategorized content.');

        $this->abstractController->userHasPermissionForCategoriesPublic(PermissionType::FAQ_EDIT, []);
    }

    public function testCategoryCheckAllowsUncategorizedContentWithAGrantWithoutCategoryRestriction(): void
    {
        $this->currentUserMock->method('isLoggedIn')->willReturn(true);
        $this->currentUserMock->method('getUserId')->willReturn(1);
        $this->permissionMock
            ->method('getScopeForRight')
            ->willReturn(RightScope::ofGrants(new RightGrant(languages: ['de'])));

        $this->abstractController->userHasPermissionForCategoriesPublic(PermissionType::FAQ_EDIT, [0]);

        $this->addToAssertionCount(1);
    }

    /**
     * The advisory scenario: both per-dimension checks pass, each through another grant, but no
     * single grant covers category 2 in English.
     */
    public function testCategoriesInLanguageDoesNotMergeRestrictionsOfSeparateGrants(): void
    {
        $this->actAsUserHoldingGermanEverywhereAndCategoryOne();

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_EDIT" permission for category 2 in language "en".');

        $this->abstractController->userHasPermissionForCategoriesInLanguagePublic(PermissionType::FAQ_EDIT, [2], 'en');
    }

    public function testCategoriesInLanguageDeniesUncategorizedContentOutsideTheLanguageOfTheGlobalGrant(): void
    {
        $this->actAsUserHoldingGermanEverywhereAndCategoryOne();

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_EDIT" permission for uncategorized content in language "en".');

        $this->abstractController->userHasPermissionForCategoriesInLanguagePublic(PermissionType::FAQ_EDIT, [0], 'en');
    }

    public function testCategoriesInLanguageAllowsWhatASingleGrantCovers(): void
    {
        $this->actAsUserHoldingGermanEverywhereAndCategoryOne();

        $this->abstractController->userHasPermissionForCategoriesInLanguagePublic(PermissionType::FAQ_EDIT, [2], 'de');
        $this->abstractController->userHasPermissionForCategoriesInLanguagePublic(PermissionType::FAQ_EDIT, [], 'de');
        $this->abstractController->userHasPermissionForCategoriesInLanguagePublic(PermissionType::FAQ_EDIT, [1], 'en');

        $this->addToAssertionCount(1);
    }

    /**
     * German in every category through one grant, category 1 in every language through
     * another. Looked at one dimension at a time, every category and every language is covered.
     */
    private function actAsUserHoldingGermanEverywhereAndCategoryOne(): void
    {
        $this->currentUserMock->method('isLoggedIn')->willReturn(true);
        $this->currentUserMock->method('getUserId')->willReturn(1);
        $this->permissionMock
            ->method('getScopeForRight')
            ->willReturn(RightScope::ofGrants(new RightGrant(languages: ['de']), new RightGrant([1])));
    }

    public function testUserMayReturnsFalseWhenNotLoggedIn(): void
    {
        $this->currentUserMock->method('isLoggedIn')->willReturn(false);
        $this->permissionMock->expects($this->never())->method('hasPermission');

        $this->assertFalse($this->abstractController->userMayPublic(PermissionType::FAQ_EDIT));
    }

    public function testVerifySessionCsrfTokenRemovesAnExpiredToken(): void
    {
        $tokenSession = $this->createMock(\Symfony\Component\HttpFoundation\Session\SessionInterface::class);
        \phpMyFAQ\Session\Token::resetInstanceForTests();
        try {
            $storedToken = \phpMyFAQ\Session\Token::getInstance($tokenSession)
                ->setPage('faq')
                ->setExpiry(time() - 10)
                ->setSessionToken('stored');
        } finally {
            \phpMyFAQ\Session\Token::resetInstanceForTests();
        }

        $session = $this->createMock(FlashBagAwareSessionInterface::class);
        $session->expects($this->once())->method('get')->with('pmf-csrf-token.faq')->willReturn($storedToken);
        $session->expects($this->once())->method('remove')->with('pmf-csrf-token.faq');
        $this->abstractController->setSessionPublic($session);

        $this->assertFalse($this->abstractController->verifySessionCsrfTokenPublic('faq', 'stored'));
    }

    #[DataProvider('missingServiceProvider')]
    public function testSetContainerThrowsWhenAServiceHasTheWrongType(string $missingId, string $message): void
    {
        $controller = new class() extends AbstractController {
            public function __construct()
            {
            }
        };

        $services = [
            'phpmyfaq.configuration' => $this->configurationMock,
            'phpmyfaq.user.current_user' => $this->currentUserMock,
            'session' => $this->createMock(FlashBagAwareSessionInterface::class),
        ];
        $services[$missingId] = new \stdClass();

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(static fn(string $id) => $services[$id]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage($message);

        $controller->setContainer($container);
    }

    public static function missingServiceProvider(): array
    {
        return [
            'configuration' => ['phpmyfaq.configuration', 'Configuration service not found in container.'],
            'current user' => ['phpmyfaq.user.current_user', 'CurrentUser service not found in container.'],
            'session' => ['session', 'Session service not found in container.'],
        ];
    }

    public function testJsonWithComplexData(): void
    {
        $complexData = [
            'users' => [
                ['id' => 1, 'name' => 'John'],
                ['id' => 2, 'name' => 'Jane'],
            ],
            'metadata' => [
                'total' => 2,
                'page' => 1,
            ],
        ];

        $response = $this->abstractController->json($complexData, 201, ['Custom-Header' => 'value']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('value', $response->headers->get('Custom-Header'));

        $decodedContent = json_decode($response->getContent(), true);
        $this->assertEquals($complexData, $decodedContent);
    }

    /**
     * Test data provider for different permission scenarios
     */
    public static function permissionDataProvider(): array
    {
        return [
            'FAQ_ADD permission' => [PermissionType::FAQ_ADD],
            'FAQ_EDIT permission' => [PermissionType::FAQ_EDIT],
            'FAQ_DELETE permission' => [PermissionType::FAQ_DELETE],
            'USER_ADD permission' => [PermissionType::USER_ADD],
            'USER_EDIT permission' => [PermissionType::USER_EDIT],
            'USER_DELETE permission' => [PermissionType::USER_DELETE],
            'GROUP_EDIT permission' => [PermissionType::GROUP_EDIT],
        ];
    }

    #[DataProvider('permissionDataProvider')]
    public function testUserHasPermissionWithDifferentPermissionTypes(PermissionType $permissionType): void
    {
        // Mock that user is logged in
        $this->currentUserMock->expects($this->once())->method('isLoggedIn')->willReturn(true);

        $this->currentUserMock->expects($this->once())->method('getUserId')->willReturn(1);

        $this->permissionMock
            ->expects($this->once())
            ->method('hasPermission')
            ->with(1, $permissionType->value)
            ->willReturn(true);

        $this->abstractController->userHasPermissionPublic($permissionType);
        $this->assertTrue(true);
    }
}
