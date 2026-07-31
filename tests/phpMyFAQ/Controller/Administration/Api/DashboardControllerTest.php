<?php

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Controller\Exception\ForbiddenException;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
class DashboardControllerTest extends TestCase
{
    private function buildController(CurrentUser $actingUser): DashboardController
    {
        $controller = (new ReflectionClass(DashboardController::class))->newInstanceWithoutConstructor();

        $session = new Session(new MockArraySessionStorage());
        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->method('get')
            ->willReturnCallback(static fn(string $id) => $id === 'session' ? $session : null);

        $parent = (new ReflectionClass(DashboardController::class))->getParentClass();
        $parent->getProperty('container')->setValue($controller, $container);
        $parent->getProperty('currentUser')->setValue($controller, $actingUser);

        return $controller;
    }

    /**
     * A logged-in user who holds every permission except $withheld. Calling an
     * endpoint that requires $withheld must therefore fail, which pins the exact
     * permission each endpoint is gated on and catches drift to a wrong one.
     */
    private function userMissing(PermissionType $withheld): CurrentUser
    {
        $perm = $this->createMock(PermissionInterface::class);
        $perm->method('hasPermission')->willReturnCallback(
            static fn(int $userId, string $permission): bool => $permission !== $withheld->value,
        );

        $user = $this->createMock(CurrentUser::class);
        $user->perm = $perm;
        $user->method('isLoggedIn')->willReturn(true);
        $user->method('getUserId')->willReturn(5);

        return $user;
    }

    public function testVerifyRequiresConfigurationEdit(): void
    {
        $controller = $this->buildController($this->userMissing(PermissionType::CONFIGURATION_EDIT));

        $this->expectException(ForbiddenException::class);
        $controller->verify(new Request());
    }

    public function testVersionsRequiresConfigurationEdit(): void
    {
        $controller = $this->buildController($this->userMissing(PermissionType::CONFIGURATION_EDIT));

        $this->expectException(ForbiddenException::class);
        $controller->versions();
    }

    public function testVisitsRequiresStatisticsViewLogs(): void
    {
        $controller = $this->buildController($this->userMissing(PermissionType::STATISTICS_VIEWLOGS));

        $this->expectException(ForbiddenException::class);
        $controller->visits(new Request());
    }

    public function testTopTenRequiresStatisticsViewLogs(): void
    {
        $controller = $this->buildController($this->userMissing(PermissionType::STATISTICS_VIEWLOGS));

        $this->expectException(ForbiddenException::class);
        $controller->topTen();
    }
}
