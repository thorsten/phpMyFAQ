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
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
class OpenSearchControllerTest extends TestCase
{
    private function buildController(CurrentUser $actingUser): OpenSearchController
    {
        $controller = (new ReflectionClass(OpenSearchController::class))->newInstanceWithoutConstructor();

        $session = new Session(new MockArraySessionStorage());
        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->method('get')
            ->willReturnCallback(static fn(string $id) => $id === 'session' ? $session : null);

        $parent = (new ReflectionClass(OpenSearchController::class))->getParentClass();
        $parent->getProperty('container')->setValue($controller, $container);
        $parent->getProperty('currentUser')->setValue($controller, $actingUser);

        return $controller;
    }

    /**
     * A logged-in user who holds every permission except CONFIGURATION_EDIT, so a
     * read endpoint that requires it must still be refused. This pins the read
     * endpoints to the same permission as the write methods in the same controller.
     */
    private function userWithoutConfigurationEdit(): CurrentUser
    {
        $perm = $this->createMock(PermissionInterface::class);
        $perm->method('hasPermission')->willReturnCallback(
            static fn(int $userId, string $permission): bool
                => $permission !== PermissionType::CONFIGURATION_EDIT->value,
        );

        $user = $this->createMock(CurrentUser::class);
        $user->perm = $perm;
        $user->method('isLoggedIn')->willReturn(true);
        $user->method('getUserId')->willReturn(5);

        return $user;
    }

    public function testStatisticsRequiresConfigurationEdit(): void
    {
        $controller = $this->buildController($this->userWithoutConfigurationEdit());

        $this->expectException(ForbiddenException::class);
        $controller->statistics();
    }

    public function testHealthcheckRequiresConfigurationEdit(): void
    {
        $controller = $this->buildController($this->userWithoutConfigurationEdit());

        $this->expectException(ForbiddenException::class);
        $controller->healthcheck();
    }
}
