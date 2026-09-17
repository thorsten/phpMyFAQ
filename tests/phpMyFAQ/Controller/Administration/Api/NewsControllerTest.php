<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\Exception\ForbiddenException;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use SensitiveParameter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
class NewsControllerTest extends TestCase
{
    protected function setUp(): void
    {
        $instance = new ReflectionProperty(Token::class, 'instance');
        $instance->setValue(null, null);
        $_COOKIE = [];

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();
    }

    protected function tearDown(): void
    {
        $instance = new ReflectionProperty(Token::class, 'instance');
        $instance->setValue(null, null);
        $_COOKIE = [];
    }

    private function buildController(CurrentUser $actingUser, ?Session $session = null): NewsController
    {
        $controller = (new ReflectionClass(NewsController::class))->newInstanceWithoutConstructor();

        $session ??= new Session(new MockArraySessionStorage());
        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->method('get')
            ->willReturnCallback(static fn(string $id) => match ($id) {
                'session' => $session,
                default => null,
            });

        $parent = (new ReflectionClass(NewsController::class))->getParentClass();
        $parent->getProperty('container')->setValue($controller, $container);
        $parent->getProperty('currentUser')->setValue($controller, $actingUser);
        $parent->getProperty('configuration')->setValue($controller, $this->createMock(Configuration::class));

        return $controller;
    }

    /**
     * @param PermissionType[] $granted
     */
    private function userWithPermissions(array $granted): CurrentUser
    {
        $grantedValues = array_map(static fn(PermissionType $type): string => $type->value, $granted);

        $perm = $this->createMock(PermissionInterface::class);
        $perm->method('hasPermission')->willReturnCallback(static fn(int $userId, string $permission): bool => in_array(
            $permission,
            $grantedValues,
            strict: true,
        ));

        $user = $this->createMock(CurrentUser::class);
        $user->perm = $perm;
        $user->method('isLoggedIn')->willReturn(true);
        $user->method('getUserId')->willReturn(5);

        return $user;
    }

    private function jsonRequest(array $payload): Request
    {
        return new Request([], [], [], [], [], [], json_encode($payload));
    }

    private function withCsrfToken(array $payload, #[SensitiveParameter] string $token): array
    {
        $payload['csrfToken'] = $token;

        return $payload;
    }

    private function updatePayload(): array
    {
        return [
            'id' => 1,
            'newsHeader' => 'Rewritten header',
            'news' => 'Rewritten body',
            'authorName' => 'attacker',
            'authorEmail' => 'attacker@example.test',
            'active' => 'y',
        ];
    }

    /**
     * Regression: a delegated admin holding addnews and delnews but not editnews
     * must not be able to rewrite an existing news item.
     */
    public function testUpdateRequiresNewsEditEvenWhenUserHoldsAddAndDelete(): void
    {
        $controller = $this->buildController($this->userWithPermissions([
            PermissionType::NEWS_ADD,
            PermissionType::NEWS_DELETE,
        ]));

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "NEWS_EDIT" permission.');

        $controller->update($this->jsonRequest($this->withCsrfToken($this->updatePayload(), 'irrelevant')));
    }

    public function testUpdateRequiresNewsEditWhenUserHoldsOnlyDelete(): void
    {
        $controller = $this->buildController($this->userWithPermissions([PermissionType::NEWS_DELETE]));

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "NEWS_EDIT" permission.');

        $controller->update($this->jsonRequest($this->withCsrfToken($this->updatePayload(), 'irrelevant')));
    }

    /**
     * A user holding only editnews passes the permission guard and reaches the CSRF check.
     */
    public function testUpdatePassesPermissionGateWithNewsEditOnly(): void
    {
        $controller = $this->buildController($this->userWithPermissions([PermissionType::NEWS_EDIT]));

        $response = $controller->update($this->jsonRequest($this->withCsrfToken(
            $this->updatePayload(),
            'not-the-session-token',
        )));

        static::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        static::assertSame(
            ['error' => Translation::get('msgNoPermission')],
            json_decode($response->getContent(), associative: true),
        );
    }

    public function testDeleteStillRequiresNewsDelete(): void
    {
        $controller = $this->buildController($this->userWithPermissions([
            PermissionType::NEWS_ADD,
            PermissionType::NEWS_EDIT,
        ]));

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "NEWS_DELETE" permission.');

        $controller->delete($this->jsonRequest($this->withCsrfToken(['id' => 1], 'irrelevant')));
    }

    public function testActivateStillRequiresNewsEdit(): void
    {
        $controller = $this->buildController($this->userWithPermissions([
            PermissionType::NEWS_ADD,
            PermissionType::NEWS_DELETE,
        ]));

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "NEWS_EDIT" permission.');

        $controller->activate($this->jsonRequest($this->withCsrfToken(['id' => 1, 'status' => 'y'], 'irrelevant')));
    }
}
