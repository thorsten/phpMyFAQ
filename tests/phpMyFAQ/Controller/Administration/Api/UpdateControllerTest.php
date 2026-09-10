<?php

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\Exception\ForbiddenException;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Setup\Update;
use phpMyFAQ\Setup\Upgrade;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
class UpdateControllerTest extends TestCase
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

    private function buildController(
        CurrentUser $actingUser,
        ?Session $session = null,
        ?Update $update = null,
        ?Configuration $configuration = null,
        ?Upgrade $upgrade = null,
    ): UpdateController {
        $controller = (new ReflectionClass(UpdateController::class))->newInstanceWithoutConstructor();

        $session ??= new Session(new MockArraySessionStorage());
        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->method('get')
            ->willReturnCallback(static fn(string $id) => match ($id) {
                'session' => $session,
                'phpmyfaq.setup.update' => $update,
                'phpmyfaq.setup.upgrade' => $upgrade,
                default => null,
            });

        $parent = (new ReflectionClass(UpdateController::class))->getParentClass();
        $parent->getProperty('container')->setValue($controller, $container);
        $parent->getProperty('currentUser')->setValue($controller, $actingUser);
        $parent->getProperty('configuration')->setValue($controller, $configuration);

        return $controller;
    }

    private function userWithConfigurationEdit(): CurrentUser
    {
        $perm = $this->createMock(PermissionInterface::class);
        $perm->method('hasPermission')->willReturn(true);

        $user = $this->createMock(CurrentUser::class);
        $user->perm = $perm;
        $user->method('isLoggedIn')->willReturn(true);
        $user->method('getUserId')->willReturn(5);

        return $user;
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

    private function jsonRequest(array $payload): Request
    {
        return new Request([], [], [], [], [], [], json_encode($payload));
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

    public function testHealthCheckRequiresConfigurationEdit(): void
    {
        $controller = $this->buildController($this->userWithoutConfigurationEdit());

        $this->expectException(ForbiddenException::class);
        $controller->healthCheck();
    }

    public function testVersionsRequiresConfigurationEdit(): void
    {
        $controller = $this->buildController($this->userWithoutConfigurationEdit());

        $this->expectException(ForbiddenException::class);
        $controller->versions();
    }

    public function testUpdateCheckRequiresConfigurationEdit(): void
    {
        $controller = $this->buildController($this->userWithoutConfigurationEdit());

        $this->expectException(ForbiddenException::class);
        $controller->updateCheck();
    }

    public function testUpdateDatabaseRequiresConfigurationEdit(): void
    {
        $controller = $this->buildController($this->userWithoutConfigurationEdit());

        $this->expectException(ForbiddenException::class);
        $controller->updateDatabase($this->jsonRequest([]));
    }

    public function testUpdateDatabaseRejectsRequestWithoutCsrfToken(): void
    {
        $update = $this->createMock(Update::class);
        $update->expects($this->never())->method('applyUpdates');

        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->never())->method('set');

        $controller = $this->buildController(
            $this->userWithConfigurationEdit(),
            new Session(new MockArraySessionStorage()),
            $update,
            $configuration,
        );

        $response = $controller->updateDatabase($this->jsonRequest([]));

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertSame(
            ['error' => Translation::get('msgNoPermission')],
            json_decode($response->getContent(), true),
        );
    }

    public function testUpdateDatabaseRejectsRequestWithWrongCsrfToken(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $this->primeCsrf($session, 'update-package');

        $update = $this->createMock(Update::class);
        $update->expects($this->never())->method('applyUpdates');

        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->never())->method('set');

        $controller = $this->buildController($this->userWithConfigurationEdit(), $session, $update, $configuration);

        $response = $controller->updateDatabase($this->jsonRequest(['csrf' => 'wrong-token']));

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testUpdateDatabaseAppliesUpdatesWithValidCsrfToken(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrf = $this->primeCsrf($session, 'update-package');

        $update = $this->createMock(Update::class);
        $update->expects($this->once())->method('setVersion')->with('4.1.8');
        $update->expects($this->once())->method('applyUpdates')->willReturn(true);

        $configuration = $this->createMock(Configuration::class);
        $configuration->method('get')->with('main.currentVersion')->willReturn('4.1.8');
        $configuration->expects($this->once())->method('set')->with('main.maintenanceMode', 'false');

        $controller = $this->buildController($this->userWithConfigurationEdit(), $session, $update, $configuration);

        $response = $controller->updateDatabase($this->jsonRequest(['csrf' => $csrf]));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(
            ['success' => 'Database successfully updated.'],
            json_decode($response->getContent(), true),
        );
    }

    public function testCleanUpRequiresConfigurationEdit(): void
    {
        $controller = $this->buildController($this->userWithoutConfigurationEdit());

        $this->expectException(ForbiddenException::class);
        $controller->cleanUp($this->jsonRequest([]));
    }

    public function testCleanUpRejectsRequestWithoutCsrfToken(): void
    {
        $upgrade = $this->createMock(Upgrade::class);
        $upgrade->expects($this->never())->method('cleanUp');

        $controller = $this->buildController(
            $this->userWithConfigurationEdit(),
            new Session(new MockArraySessionStorage()),
            null,
            null,
            $upgrade,
        );

        $response = $controller->cleanUp($this->jsonRequest([]));

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertSame(
            ['error' => Translation::get('msgNoPermission')],
            json_decode($response->getContent(), true),
        );
    }

    public function testCleanUpRunsWithValidCsrfToken(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrf = $this->primeCsrf($session, 'update-package');

        $upgrade = $this->createMock(Upgrade::class);
        $upgrade->expects($this->once())->method('cleanUp');

        $controller = $this->buildController($this->userWithConfigurationEdit(), $session, null, null, $upgrade);

        $response = $controller->cleanUp($this->jsonRequest(['csrf' => $csrf]));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(['message' => 'Cleanup successful.'], json_decode($response->getContent(), true));
    }
}
