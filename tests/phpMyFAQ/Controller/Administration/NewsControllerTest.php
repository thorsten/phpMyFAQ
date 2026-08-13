<?php

namespace phpMyFAQ\Controller\Administration;

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
class NewsControllerTest extends TestCase
{
    private function buildController(Session $session, CurrentUser $actingUser): NewsController
    {
        $controller = (new ReflectionClass(NewsController::class))->newInstanceWithoutConstructor();

        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->method('get')
            ->willReturnCallback(static function (string $id) use ($session) {
                return $id === 'session' ? $session : null;
            });

        $reflection = new ReflectionClass(NewsController::class);
        $parent = $reflection->getParentClass();
        while ($parent !== false && !$parent->hasProperty('currentUser')) {
            $parent = $parent->getParentClass();
        }

        $parent->getProperty('container')->setValue($controller, $container);
        $parent->getProperty('currentUser')->setValue($controller, $actingUser);

        return $controller;
    }

    /**
     * Reading the news editor must require NEWS_EDIT, not merely NEWS_ADD: a user holding only the
     * "add news" right must not be able to read the body of existing (possibly inactive) news items.
     */
    public function testEditRequiresNewsEditPermissionAndRejectsAddOnlyUser(): void
    {
        $session = new Session(new MockArraySessionStorage());

        // The acting user holds 'addnews' but NOT 'editnews'.
        $perm = $this->createMock(PermissionInterface::class);
        $perm->method('hasPermission')->willReturnCallback(
            static fn(int $userId, mixed $right): bool => $right === PermissionType::NEWS_ADD->value,
        );

        $actingUser = $this->createMock(CurrentUser::class);
        $actingUser->perm = $perm;
        $actingUser->method('isLoggedIn')->willReturn(true);
        $actingUser->method('getUserId')->willReturn(5);

        $controller = $this->buildController($session, $actingUser);

        $request = new Request();
        $request->attributes->set('newsId', '1');

        $this->expectException(ForbiddenException::class);
        $controller->edit($request);
    }
}
