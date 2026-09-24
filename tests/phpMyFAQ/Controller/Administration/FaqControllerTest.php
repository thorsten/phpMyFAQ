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

    /**
     * @param PermissionType[] $granted
     */
    private function userHolding(array $granted): CurrentUser
    {
        $grantedValues = array_map(static fn(PermissionType $type): string => $type->value, $granted);

        $perm = $this->createMock(PermissionInterface::class);
        $perm->method('hasPermission')->willReturnCallback(static fn(int $userId, mixed $right): bool => in_array(
            $right,
            $grantedValues,
            strict: true,
        ));

        $actingUser = $this->createMock(CurrentUser::class);
        $actingUser->perm = $perm;
        $actingUser->method('isLoggedIn')->willReturn(true);
        $actingUser->method('getUserId')->willReturn(87);

        return $actingUser;
    }

    /**
     * The FAQ overview is gated on FAQ_EDIT only: its menu entry, its data endpoint (admin.api.faqs)
     * and the edit links all require that right, while approving, deleting and adding are enforced
     * per action by the API. Requiring all four rights just to open the list made the menu entry and
     * the dashboard shortcut lead to a 403 for users holding FAQ_EDIT but e.g. not FAQ_APPROVE
     * (see GitHub issue #4691).
     */
    public function testIndexRequiresOnlyFaqEditPermission(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $controller = $this->buildController(
            $session,
            $this->userHolding([PermissionType::FAQ_EDIT, PermissionType::FAQ_DELETE]),
        );

        try {
            $controller->index(new Request());
        } catch (ForbiddenException $forbiddenException) {
            $this->fail('A user holding FAQ_EDIT must pass the permission gate of the FAQ overview.');
        } catch (\Throwable) {
            // Only the permission gate is under test; the controller's rendering dependencies
            // (category tree, Twig environment) are intentionally not wired up in this unit test.
        }

        $this->addToAssertionCount(1);
    }

    public function testIndexRejectsUserWithoutFaqEditPermission(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $controller = $this->buildController(
            $session,
            $this->userHolding([PermissionType::FAQ_ADD, PermissionType::FAQ_APPROVE, PermissionType::FAQ_DELETE]),
        );

        $this->expectException(ForbiddenException::class);
        $controller->index(new Request());
    }
}
