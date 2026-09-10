<?php

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Controller\Exception\ForbiddenException;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Faq;
use phpMyFAQ\Instance\Elasticsearch;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
class ElasticsearchControllerTest extends TestCase
{
    protected function setUp(): void
    {
        // The Elasticsearch instance class resolves this constant in a property default,
        // so it must exist before a mock of the class can be instantiated.
        if (!defined('PMF_ELASTICSEARCH_TOKENIZER')) {
            define('PMF_ELASTICSEARCH_TOKENIZER', 'standard');
        }

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
        ?Elasticsearch $searchInstance = null,
        ?Faq $faq = null,
    ): ElasticsearchController {
        $controller = (new ReflectionClass(ElasticsearchController::class))->newInstanceWithoutConstructor();

        $session ??= new Session(new MockArraySessionStorage());
        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->method('get')
            ->willReturnCallback(static fn(string $id) => match ($id) {
                'session' => $session,
                'phpmyfaq.instance.elasticsearch' => $searchInstance,
                'phpmyfaq.faq' => $faq,
                default => null,
            });

        $parent = (new ReflectionClass(ElasticsearchController::class))->getParentClass();
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

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function stateChangingActions(): array
    {
        return [
            'create' => ['create', 'createIndex'],
            'drop' => ['drop', 'dropIndex'],
            'import' => ['import', 'bulkIndex'],
        ];
    }

    #[DataProvider('stateChangingActions')]
    public function testStateChangingActionRequiresConfigurationEdit(string $action, string $serviceMethod): void
    {
        $searchInstance = $this->createMock(Elasticsearch::class);
        $searchInstance->expects($this->never())->method($serviceMethod);

        $controller = $this->buildController($this->userWithoutConfigurationEdit(), null, $searchInstance);

        $this->expectException(ForbiddenException::class);
        $controller->{$action}($this->jsonRequest([]));
    }

    #[DataProvider('stateChangingActions')]
    public function testStateChangingActionRejectsRequestWithoutCsrfToken(string $action, string $serviceMethod): void
    {
        $searchInstance = $this->createMock(Elasticsearch::class);
        $searchInstance->expects($this->never())->method($serviceMethod);

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->never())->method('getAllFaqs');

        $controller = $this->buildController($this->userWithConfigurationEdit(), null, $searchInstance, $faq);

        $response = $controller->{$action}($this->jsonRequest([]));

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertSame(
            ['error' => Translation::get('msgNoPermission')],
            json_decode($response->getContent(), true),
        );
    }

    #[DataProvider('stateChangingActions')]
    public function testStateChangingActionRejectsRequestWithWrongCsrfToken(string $action, string $serviceMethod): void
    {
        $session = new Session(new MockArraySessionStorage());
        $this->primeCsrf($session, 'elasticsearch');

        $searchInstance = $this->createMock(Elasticsearch::class);
        $searchInstance->expects($this->never())->method($serviceMethod);

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->never())->method('getAllFaqs');

        $controller = $this->buildController($this->userWithConfigurationEdit(), $session, $searchInstance, $faq);

        $response = $controller->{$action}($this->jsonRequest(['csrf' => 'wrong-token']));

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    #[DataProvider('stateChangingActions')]
    public function testStateChangingActionRejectsTokenScopedToAnotherPage(string $action, string $serviceMethod): void
    {
        $session = new Session(new MockArraySessionStorage());
        $otherPageToken = $this->primeCsrf($session, 'update-package');

        $searchInstance = $this->createMock(Elasticsearch::class);
        $searchInstance->expects($this->never())->method($serviceMethod);

        $controller = $this->buildController($this->userWithConfigurationEdit(), $session, $searchInstance);

        $response = $controller->{$action}($this->jsonRequest(['csrf' => $otherPageToken]));

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testCreateRunsWithValidCsrfToken(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrf = $this->primeCsrf($session, 'elasticsearch');

        $searchInstance = $this->createMock(Elasticsearch::class);
        $searchInstance->expects($this->once())->method('createIndex')->willReturn(true);

        $controller = $this->buildController($this->userWithConfigurationEdit(), $session, $searchInstance);

        $response = $controller->create($this->jsonRequest(['csrf' => $csrf]));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(
            ['success' => Translation::get('msgAdminElasticsearchCreateIndex_success')],
            json_decode($response->getContent(), true),
        );
    }

    public function testDropRunsWithValidCsrfToken(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrf = $this->primeCsrf($session, 'elasticsearch');

        $searchInstance = $this->createMock(Elasticsearch::class);
        $searchInstance->expects($this->once())->method('dropIndex');

        $controller = $this->buildController($this->userWithConfigurationEdit(), $session, $searchInstance);

        $response = $controller->drop($this->jsonRequest(['csrf' => $csrf]));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(
            ['success' => Translation::get('msgAdminElasticsearchDropIndex_success')],
            json_decode($response->getContent(), true),
        );
    }

    public function testImportRunsWithValidCsrfToken(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrf = $this->primeCsrf($session, 'elasticsearch');

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())->method('getAllFaqs');
        $faq->faqRecords = [];

        $searchInstance = $this->createMock(Elasticsearch::class);
        $searchInstance->expects($this->once())->method('bulkIndex')->with([])->willReturn(['success' => true]);

        $controller = $this->buildController($this->userWithConfigurationEdit(), $session, $searchInstance, $faq);

        $response = $controller->import($this->jsonRequest(['csrf' => $csrf]));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(
            ['success' => Translation::get('ad_es_create_import_success')],
            json_decode($response->getContent(), true),
        );
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
