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
class StopWordControllerTest extends TestCase
{
    private function buildController(CurrentUser $actingUser): StopWordController
    {
        $controller = (new ReflectionClass(StopWordController::class))->newInstanceWithoutConstructor();

        $session = new Session(new MockArraySessionStorage());
        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->method('get')
            ->willReturnCallback(static fn(string $id) => $id === 'session' ? $session : null);

        $parent = (new ReflectionClass(StopWordController::class))->getParentClass();
        $parent->getProperty('container')->setValue($controller, $container);
        $parent->getProperty('currentUser')->setValue($controller, $actingUser);

        return $controller;
    }

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

    /**
     * The stopword list is configuration data; reading it must require the same
     * CONFIGURATION_EDIT permission as saving or deleting a stopword.
     */
    public function testListRequiresConfigurationEdit(): void
    {
        $controller = $this->buildController($this->userWithoutConfigurationEdit());

        $this->expectException(ForbiddenException::class);
        $controller->list(new Request());
    }
}
