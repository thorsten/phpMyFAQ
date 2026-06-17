<?php

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Session\Token;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

#[AllowMockObjectsWithoutExpectations]
class GroupControllerTest extends TestCase
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

    private function buildController(Session $session, CurrentUser $actingUser): GroupController
    {
        $controller = (new ReflectionClass(GroupController::class))->newInstanceWithoutConstructor();

        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->method('get')
            ->willReturnCallback(static function (string $id) use ($session) {
                return $id === 'session' ? $session : null;
            });

        $reflection = new ReflectionClass(GroupController::class);
        $parent = $reflection->getParentClass();
        while ($parent !== false && !$parent->hasProperty('currentUser')) {
            $parent = $parent->getParentClass();
        }

        $parent->getProperty('container')->setValue($controller, $container);
        $parent->getProperty('currentUser')->setValue($controller, $actingUser);

        return $controller;
    }

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

    public function testUpdatePermissionsNonSuperAdminCannotGrantRightTheyDoNotHold(): void
    {
        $session = new Session(new MockArraySessionStorage());

        // Acting user holds the permission gates (string-keyed rights such as 'editgroup')
        // but does NOT hold right id 42, which it tries to grant to the group.
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
        $csrf = $this->primeCsrf($session, 'update-group-permissions');

        $request = new Request(
            request: [
                'pmf-csrf-token' => $csrf,
                'group_id' => '2',
                'group_rights' => ['42'], // an int right the acting user does not hold
            ],
        );

        $this->expectException(UnauthorizedHttpException::class);
        $controller->updatePermissions($request);
    }
}
