<?php

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Controller\Exception\ForbiddenException;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

#[AllowMockObjectsWithoutExpectations]
class GroupControllerTest extends TestCase
{
    private function buildController(Session $session, CurrentUser $actingUser): GroupController
    {
        $controller = (new ReflectionClass(GroupController::class))->newInstanceWithoutConstructor();

        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->method('get')
            ->willReturnCallback(static function (string $id) use ($session) {
                return $id === 'session' ? $session : null;
            });

        $parent = (new ReflectionClass(GroupController::class))->getParentClass();
        $parent->getProperty('container')->setValue($controller, $container);
        $parent->getProperty('currentUser')->setValue($controller, $actingUser);

        return $controller;
    }

    /**
     * A logged-out caller must trigger UnauthorizedHttpException (translated to a
     * login redirect / 401 by Application::run()), not a bare 403, because the
     * group permission gate now authenticates first.
     */
    public function testListGroupsRejectsUnauthenticatedUser(): void
    {
        $session = new Session(new MockArraySessionStorage());

        $user = $this->createMock(CurrentUser::class);
        $user->method('isLoggedIn')->willReturn(false);

        $controller = $this->buildController($session, $user);

        $this->expectException(UnauthorizedHttpException::class);
        $controller->listGroups();
    }

    /**
     * A logged-in caller without the group permissions is still rejected with
     * ForbiddenException (403): authentication passes, the permission gate fails.
     */
    public function testListGroupsForbidsAuthenticatedUserWithoutPermission(): void
    {
        $session = new Session(new MockArraySessionStorage());

        $perm = $this->createMock(PermissionInterface::class);
        $perm->method('hasPermission')->willReturn(false);

        $user = $this->createMock(CurrentUser::class);
        $user->perm = $perm;
        $user->method('isLoggedIn')->willReturn(true);
        $user->method('getUserId')->willReturn(5);

        $controller = $this->buildController($session, $user);

        $this->expectException(ForbiddenException::class);
        $controller->listGroups();
    }
}
