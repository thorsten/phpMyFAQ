<?php

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Controller\Exception\ForbiddenException;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

#[AllowMockObjectsWithoutExpectations]
class UpdateControllerTest extends TestCase
{
    private function buildController(Session $session, CurrentUser $actingUser): UpdateController
    {
        $controller = (new ReflectionClass(UpdateController::class))->newInstanceWithoutConstructor();

        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->method('get')
            ->willReturnCallback(static function (string $id) use ($session) {
                return $id === 'session' ? $session : null;
            });

        $reflection = new ReflectionClass(UpdateController::class);
        $parent = $reflection->getParentClass();
        while ($parent !== false && !$parent->hasProperty('currentUser')) {
            $parent = $parent->getParentClass();
        }

        $parent->getProperty('container')->setValue($controller, $container);
        $parent->getProperty('currentUser')->setValue($controller, $actingUser);

        return $controller;
    }

    /**
     * An unauthenticated admin must be sent to the login form
     * (UnauthorizedHttpException is translated to a /admin/login redirect by
     * Application::run()), not receive a bare 403, so the in-admin updater stays
     * reachable as a recovery target during a pending update.
     */
    public function testIndexRedirectsUnauthenticatedUserToLogin(): void
    {
        $session = new Session(new MockArraySessionStorage());

        $actingUser = $this->createMock(CurrentUser::class);
        $actingUser->method('isLoggedIn')->willReturn(false);

        $controller = $this->buildController($session, $actingUser);

        $this->expectException(UnauthorizedHttpException::class);
        $controller->index(new Request());
    }

    /**
     * A logged-in user without the CONFIGURATION_EDIT permission is still
     * rejected: the authentication gate passes, the permission gate throws.
     */
    public function testIndexForbidsAuthenticatedUserWithoutPermission(): void
    {
        $session = new Session(new MockArraySessionStorage());

        $perm = $this->createMock(PermissionInterface::class);
        $perm->method('hasPermission')->willReturn(false);

        $actingUser = $this->createMock(CurrentUser::class);
        $actingUser->perm = $perm;
        $actingUser->method('isLoggedIn')->willReturn(true);
        $actingUser->method('getUserId')->willReturn(5);

        $controller = $this->buildController($session, $actingUser);

        $this->expectException(ForbiddenException::class);
        $controller->index(new Request());
    }
}
