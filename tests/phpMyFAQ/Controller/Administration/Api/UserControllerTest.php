<?php

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Session\Token;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

#[AllowMockObjectsWithoutExpectations]
class UserControllerTest extends TestCase
{
    protected function setUp(): void
    {
        $instance = new ReflectionProperty(Token::class, 'instance');
        $instance->setValue(null, null);
        $_COOKIE = [];
    }

    protected function tearDown(): void
    {
        $instance = new ReflectionProperty(Token::class, 'instance');
        $instance->setValue(null, null);
        $_COOKIE = [];
    }

    private function buildController(Session $session, CurrentUser $actingUser): UserController
    {
        $controller = (new ReflectionClass(UserController::class))->newInstanceWithoutConstructor();

        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->method('get')
            ->willReturnCallback(static function (string $id) use ($session) {
                return $id === 'session' ? $session : null;
            });

        $parent = (new ReflectionClass(UserController::class))->getParentClass();
        $parent->getProperty('container')->setValue($controller, $container);
        $parent->getProperty('currentUser')->setValue($controller, $actingUser);

        return $controller;
    }

    /**
     * Builds a CurrentUser mock with USER_ADD/USER_EDIT/USER_DELETE granted, and
     * the supplied userId / SuperAdmin flag.
     */
    private function buildActingUser(int $userId, bool $isSuperAdmin): CurrentUser
    {
        $perm = $this->createMock(PermissionInterface::class);
        $perm->method('hasPermission')->willReturn(true);

        $user = $this->createMock(CurrentUser::class);
        $user->perm = $perm;
        $user->method('isLoggedIn')->willReturn(true);
        $user->method('getUserId')->willReturn($userId);
        $user->method('isSuperAdmin')->willReturn($isSuperAdmin);

        return $user;
    }

    /**
     * Primes the CSRF token in session and $_COOKIE so that verifyToken() returns true.
     */
    private function primeCsrf(Session $session, string $page): string
    {
        $tokenValue = 'unit-test-token-' . bin2hex(random_bytes(8));
        $cookieName = 'pmf-csrf-token-' . substr(md5($page), 0, 10);

        $reflection = new ReflectionClass(Token::class);
        $token = $reflection->newInstanceWithoutConstructor();
        $reflection->getProperty('session')->setValue($token, $session);
        $token->setPage($page);
        $token->setExpiry(time() + 3600);
        $token->setSessionToken($tokenValue);
        $token->setCookieToken($tokenValue);

        $session->set('pmf-csrf-token.' . $page, $token);
        $_COOKIE[$cookieName] = $tokenValue;

        return $tokenValue;
    }

    private function jsonRequest(array $payload): Request
    {
        return new Request([], [], [], [], [], [], json_encode($payload));
    }

    public function testRejectsRequestWithBadCsrfToken(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $actingUser = $this->buildActingUser(userId: 5, isSuperAdmin: false);
        $controller = $this->buildController($session, $actingUser);

        $request = $this->jsonRequest([
            'userId' => 5,
            'csrf' => 'nope',
            'newPassword' => 'longenoughpw',
            'passwordRepeat' => 'longenoughpw',
        ]);

        $response = $controller->overwritePassword($request);

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testRejectsZeroUserId(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $actingUser = $this->buildActingUser(userId: 5, isSuperAdmin: false);
        $controller = $this->buildController($session, $actingUser);
        $csrf = $this->primeCsrf($session, 'overwrite-password');

        $request = $this->jsonRequest([
            'userId' => 0,
            'csrf' => $csrf,
            'newPassword' => 'longenoughpw',
            'passwordRepeat' => 'longenoughpw',
        ]);

        $response = $controller->overwritePassword($request);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testRejectsShortPassword(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $actingUser = $this->buildActingUser(userId: 5, isSuperAdmin: false);
        $controller = $this->buildController($session, $actingUser);
        $csrf = $this->primeCsrf($session, 'overwrite-password');

        $request = $this->jsonRequest([
            'userId' => 5,
            'csrf' => $csrf,
            'newPassword' => 'short',
            'passwordRepeat' => 'short',
        ]);

        $response = $controller->overwritePassword($request);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testNonSuperAdminCannotChangeAnotherUsersPassword(): void
    {
        $session = new Session(new MockArraySessionStorage());
        // Acting user has USER_EDIT but is NOT a SuperAdmin.
        $actingUser = $this->buildActingUser(userId: 5, isSuperAdmin: false);
        $controller = $this->buildController($session, $actingUser);
        $csrf = $this->primeCsrf($session, 'overwrite-password');

        $request = $this->jsonRequest([
            'userId' => 1, // SuperAdmin target — the IDOR escalation case
            'csrf' => $csrf,
            'newPassword' => 'NewSuperAdminP@ss123!',
            'passwordRepeat' => 'NewSuperAdminP@ss123!',
        ]);

        $response = $controller->overwritePassword($request);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertStringContainsString('error', (string) $response->getContent());
    }

    public function testNonSuperAdminCannotChangeArbitraryOtherUsersPassword(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $actingUser = $this->buildActingUser(userId: 5, isSuperAdmin: false);
        $controller = $this->buildController($session, $actingUser);
        $csrf = $this->primeCsrf($session, 'overwrite-password');

        $request = $this->jsonRequest([
            'userId' => 42,
            'csrf' => $csrf,
            'newPassword' => 'longenoughpw',
            'passwordRepeat' => 'longenoughpw',
        ]);

        $response = $controller->overwritePassword($request);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testEditUserNonSuperAdminCannotGrantSuperAdminFlag(): void
    {
        $session = new Session(new MockArraySessionStorage());
        // Acting user holds USER_EDIT but is NOT a SuperAdmin.
        $actingUser = $this->buildActingUser(userId: 5, isSuperAdmin: false);
        $controller = $this->buildController($session, $actingUser);
        $csrf = $this->primeCsrf($session, 'update-user-data');

        $request = $this->jsonRequest([
            'userId' => 5, // even self-service must not be able to escalate
            'csrfToken' => $csrf,
            'display_name' => 'Editor',
            'email' => 'editor@example.com',
            'last_modified' => '',
            'user_status' => 'active',
            'is_superadmin' => 'on', // privilege escalation attempt
            'overwrite_twofactor' => '',
        ]);

        $response = $controller->editUser($request);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testUpdateRightsNonSuperAdminCannotGrantRightTheyDoNotHold(): void
    {
        $session = new Session(new MockArraySessionStorage());

        // Acting user holds the permission gates (string-keyed rights such as 'edit_user')
        // but does NOT hold right id 42, which it tries to grant.
        $perm = $this->createMock(PermissionInterface::class);
        $perm->method('hasPermission')->willReturnCallback(
            static fn(int $userId, mixed $right): bool => is_string($right),
        );

        $actingUser = $this->createMock(CurrentUser::class);
        $actingUser->perm = $perm;
        $actingUser->method('isLoggedIn')->willReturn(true);
        $actingUser->method('getUserId')->willReturn(5);
        $actingUser->method('isSuperAdmin')->willReturn(false);

        $controller = $this->buildController($session, $actingUser);
        $csrf = $this->primeCsrf($session, 'update-user-rights');

        $request = $this->jsonRequest([
            'userId' => 7,
            'csrfToken' => $csrf,
            'userRights' => [42], // a right the acting user does not hold
        ]);

        $response = $controller->updateUserRights($request);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testAddUserNonSuperAdminCannotGrantSuperAdminFlag(): void
    {
        $session = new Session(new MockArraySessionStorage());
        // Acting user holds USER_ADD/EDIT/DELETE but is NOT a SuperAdmin.
        $actingUser = $this->buildActingUser(userId: 5, isSuperAdmin: false);
        $controller = $this->buildController($session, $actingUser);
        $csrf = $this->primeCsrf($session, 'add-user');

        $request = $this->jsonRequest([
            'csrf' => $csrf,
            'userName' => 'evil_superadmin',
            'realName' => 'Evil SA',
            'email' => 'evil@example.test',
            'automaticPassword' => false,
            'password' => 'Sup3rSecret!42',
            'passwordConfirm' => 'Sup3rSecret!42',
            'isSuperAdmin' => true, // privilege escalation attempt
        ]);

        $response = $controller->addUser($request);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    /**
     * A logged-out caller must trigger UnauthorizedHttpException (translated to a
     * login redirect / 401 by Application::run()), not a bare 403, because the
     * user permission gate now authenticates first.
     */
    public function testListRejectsUnauthenticatedUser(): void
    {
        $session = new Session(new MockArraySessionStorage());

        $user = $this->createMock(CurrentUser::class);
        $user->method('isLoggedIn')->willReturn(false);

        $controller = $this->buildController($session, $user);

        $this->expectException(UnauthorizedHttpException::class);
        $controller->list(new Request());
    }
}
