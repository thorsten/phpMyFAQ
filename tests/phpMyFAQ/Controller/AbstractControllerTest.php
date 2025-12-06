<?php

namespace phpMyFAQ\Controller;

use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Permission\BasicPermission;
use phpMyFAQ\Twig\TwigWrapper;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Twig\Extension\ExtensionInterface;
use Twig\TwigFilter;

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
        $this->abstractController = new class ($this->configurationMock, $this->currentUserMock) extends AbstractController {
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

            protected function createContainer(): ContainerBuilder
            {
                return new ContainerBuilder();
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

            public function createContainerPublic(): ContainerBuilder
            {
                return $this->createContainer();
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
        $this->configurationMock
            ->expects($this->once())
            ->method('get')
            ->with('api.enableAccess')
            ->willReturn('1');

        $result = $this->abstractController->isApiEnabled();

        $this->assertTrue($result);
    }

    public function testIsApiEnabledReturnsFalseWhenDisabled(): void
    {
        $this->configurationMock
            ->expects($this->once())
            ->method('get')
            ->with('api.enableAccess')
            ->willReturn('0');

        $result = $this->abstractController->isApiEnabled();

        $this->assertFalse($result);
    }

    public function testIsApiEnabledWithNullValue(): void
    {
        $this->configurationMock
            ->expects($this->once())
            ->method('get')
            ->with('api.enableAccess')
            ->willReturn(null);

        $result = $this->abstractController->isApiEnabled();

        $this->assertFalse($result);
    }

    public function testIsApiEnabledWithNumericValues(): void
    {
        $this->configurationMock
            ->expects($this->once())
            ->method('get')
            ->with('api.enableAccess')
            ->willReturn(1);

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
        $this->currentUserMock
            ->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(false);

        $this->expectException(UnauthorizedHttpException::class);

        $this->abstractController->userIsAuthenticatedPublic();
    }

    public function testUserIsAuthenticatedSucceedsWhenLoggedIn(): void
    {
        $this->currentUserMock
            ->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->abstractController->userIsAuthenticatedPublic();
        $this->assertTrue(true);
    }

    public function testUserIsSuperAdminThrowsExceptionWhenNotSuperAdmin(): void
    {
        $this->currentUserMock
            ->expects($this->once())
            ->method('isSuperAdmin')
            ->willReturn(false);

        $this->expectException(UnauthorizedHttpException::class);

        $this->abstractController->userIsSuperAdminPublic();
    }

    public function testUserIsSuperAdminSucceedsWhenSuperAdmin(): void
    {
        $this->currentUserMock
            ->expects($this->once())
            ->method('isSuperAdmin')
            ->willReturn(true);

        $this->abstractController->userIsSuperAdminPublic();
        $this->assertTrue(true);
    }

    public function testUserHasGroupPermissionThrowsExceptionWhenMissingPermissions(): void
    {
        $this->currentUserMock
            ->expects($this->once()) // Only called once since exception is thrown on first check
            ->method('getUserId')
            ->willReturn(1);

        // Mock that user lacks the first required permission (USER_ADD)
        $this->permissionMock
            ->expects($this->once())
            ->method('hasPermission')
            ->with(1, PermissionType::USER_ADD->value)
            ->willReturn(false);

        $this->expectException(UnauthorizedHttpException::class);

        $this->abstractController->userHasGroupPermissionPublic();
    }

    public function testUserHasGroupPermissionSucceedsWhenAllPermissionsPresent(): void
    {
        $this->currentUserMock
            ->expects($this->exactly(4))
            ->method('getUserId')
            ->willReturn(1);

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
                    PermissionType::GROUP_EDIT->value
                ]);
                return true;
            });

        $this->abstractController->userHasGroupPermissionPublic();
        $this->assertTrue(true);
    }

    public function testUserHasUserPermissionThrowsExceptionWhenMissingPermissions(): void
    {
        $this->currentUserMock
            ->expects($this->once()) // Only called once since exception is thrown on first check
            ->method('getUserId')
            ->willReturn(1);

        // Mock that user lacks the first required permission (USER_ADD)
        $this->permissionMock
            ->expects($this->once())
            ->method('hasPermission')
            ->with(1, PermissionType::USER_ADD->value)
            ->willReturn(false);

        $this->expectException(UnauthorizedHttpException::class);

        $this->abstractController->userHasUserPermissionPublic();
    }

    public function testUserHasUserPermissionSucceedsWhenAllPermissionsPresent(): void
    {
        $this->currentUserMock
            ->expects($this->exactly(3))
            ->method('getUserId')
            ->willReturn(1);

        // Mock that user has all required permissions
        $this->permissionMock
            ->expects($this->exactly(3))
            ->method('hasPermission')
            ->willReturnCallback(function ($userId, $permission) {
                $this->assertEquals(1, $userId);
                $this->assertContains($permission, [
                    PermissionType::USER_ADD->value,
                    PermissionType::USER_EDIT->value,
                    PermissionType::USER_DELETE->value
                ]);
                return true;
            });

        $this->abstractController->userHasUserPermissionPublic();
        $this->assertTrue(true);
    }

    public function testUserHasPermissionThrowsExceptionWhenMissingPermission(): void
    {
        $permissionType = PermissionType::FAQ_ADD;
        $this->currentUserMock
            ->expects($this->once())
            ->method('getUserId')
            ->willReturn(1);

        $this->permissionMock
            ->expects($this->once())
            ->method('hasPermission')
            ->with(1, $permissionType->value)
            ->willReturn(false);

        $this->expectException(UnauthorizedHttpException::class);

        $this->abstractController->userHasPermissionPublic($permissionType);
    }

    public function testUserHasPermissionSucceedsWhenPermissionPresent(): void
    {
        $permissionType = PermissionType::FAQ_ADD;
        $this->currentUserMock
            ->expects($this->once())
            ->method('getUserId')
            ->willReturn(1);

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
        $this->currentUserMock
            ->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(false);

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
        $this->currentUserMock
            ->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->abstractController->isSecuredPublic();
        $this->assertTrue(true);
    }

    public function testIsSecuredSucceedsWhenLoginNotRequired(): void
    {
        $this->currentUserMock
            ->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(false);

        $this->configurationMock
            ->expects($this->once())
            ->method('get')
            ->with('security.enableLoginOnly')
            ->willReturn(false);

        $this->abstractController->isSecuredPublic();
        $this->assertTrue(true);
    }

    public function testCreateContainerReturnsContainerBuilder(): void
    {
        $container = $this->abstractController->createContainerPublic();

        $this->assertInstanceOf(ContainerBuilder::class, $container);
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

    public function testJsonWithComplexData(): void
    {
        $complexData = [
            'users' => [
                ['id' => 1, 'name' => 'John'],
                ['id' => 2, 'name' => 'Jane']
            ],
            'metadata' => [
                'total' => 2,
                'page' => 1
            ]
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
        $this->currentUserMock
            ->expects($this->once())
            ->method('getUserId')
            ->willReturn(1);

        $this->permissionMock
            ->expects($this->once())
            ->method('hasPermission')
            ->with(1, $permissionType->value)
            ->willReturn(true);

        $this->abstractController->userHasPermissionPublic($permissionType);
        $this->assertTrue(true);
    }
}
