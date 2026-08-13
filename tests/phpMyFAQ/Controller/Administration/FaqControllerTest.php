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
class FaqControllerTest extends TestCase
{
    private function buildController(Session $session, CurrentUser $actingUser): FaqController
    {
        $controller = (new ReflectionClass(FaqController::class))->newInstanceWithoutConstructor();

        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->method('get')
            ->willReturnCallback(static function (string $id) use ($session) {
                return $id === 'session' ? $session : null;
            });

        $reflection = new ReflectionClass(FaqController::class);
        $parent = $reflection->getParentClass();
        while ($parent !== false && !$parent->hasProperty('currentUser')) {
            $parent = $parent->getParentClass();
        }

        $parent->getProperty('container')->setValue($controller, $container);
        $parent->getProperty('currentUser')->setValue($controller, $actingUser);

        return $controller;
    }

    /**
     * Opening the translation editor must require the dedicated FAQ_TRANSLATE right, not FAQ_ADD:
     * a user holding only "add FAQ" must not be able to read the content of existing (possibly
     * inactive) FAQ records via the translate page.
     */
    public function testTranslateRequiresTranslatePermissionAndRejectsAddOnlyUser(): void
    {
        $session = new Session(new MockArraySessionStorage());

        // The acting user holds 'add_faq' but NOT 'translate_faq'.
        $perm = $this->createMock(PermissionInterface::class);
        $perm->method('hasPermission')->willReturnCallback(
            static fn(int $userId, mixed $right): bool => $right === PermissionType::FAQ_ADD->value,
        );

        $actingUser = $this->createMock(CurrentUser::class);
        $actingUser->perm = $perm;
        $actingUser->method('isLoggedIn')->willReturn(true);
        $actingUser->method('getUserId')->willReturn(5);

        $controller = $this->buildController($session, $actingUser);

        $request = new Request();
        $request->attributes->set('faqId', '1');
        $request->attributes->set('faqLanguage', 'en');

        $this->expectException(ForbiddenException::class);
        $controller->translate($request);
    }
}
