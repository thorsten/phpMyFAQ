<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Auth\OAuth2\Repository\ScopeRepository;
use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\Exception\ForbiddenException;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\TestDatabaseTrait;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(OAuth2ScopeController::class)]
#[UsesNamespace('phpMyFAQ')]
final class OAuth2ScopeControllerTest extends TestCase
{
    use TestDatabaseTrait;

    private Configuration $configuration;

    private Session $session;

    private AdminLog&MockObject $adminLog;

    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->configuration = $this->createTestConfiguration();
        $this->session = new Session(new MockArraySessionStorage());
        $this->adminLog = $this->createMock(AdminLog::class);

        $language = new Language($this->configuration, $this->session);
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);
    }

    protected function tearDown(): void
    {
        Token::resetInstanceForTests();

        parent::tearDown();
    }

    /**
     * @throws \Exception
     */
    public function testSaveRequiresTheConfigurationPermission(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->controller(mayEditConfiguration: false)->save($this->jsonRequest([]));
    }

    /**
     * @throws \Exception
     */
    public function testSaveRejectsAnInvalidCsrfToken(): void
    {
        $response = $this->controller()->save($this->jsonRequest([
            'csrf' => 'wrong',
            'scopes' => [['scope' => 'faq.read', 'description' => 'Read FAQs']],
        ]));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame([], new ScopeRepository($this->configuration)->all());
    }

    /**
     * @throws \Exception
     */
    public function testSaveReplacesTheRequestableScopesByTheSubmittedOnes(): void
    {
        $scopes = new ScopeRepository($this->configuration);
        $scopes->enable('faq.write', 'Write FAQs');
        $scopes->enable('faq.read', 'Old description');
        $this->adminLog->expects($this->once())->method('log');

        $response = $this->controller()->save($this->jsonRequest([
            'csrf' => $this->csrfToken('oauth2-scopes-save'),
            'scopes' => [
                ['scope' => 'faq.read', 'description' => 'Read FAQs'],
                ['scope' => 'category.read', 'description' => ''],
            ],
        ]));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('msgOAuth2ScopesSaved'), $this->payloadOf($response)['success']);
        self::assertSame(['category.read' => '', 'faq.read' => 'Read FAQs'], $scopes->all());
    }

    /**
     * @throws \Exception
     */
    public function testSaveRefusesAScopeTheApiDoesNotKnowAndWritesNothing(): void
    {
        $scopes = new ScopeRepository($this->configuration);
        $scopes->enable('faq.write', 'Write FAQs');
        $this->adminLog->expects($this->never())->method('log');

        $response = $this->controller()->save($this->jsonRequest([
            'csrf' => $this->csrfToken('oauth2-scopes-save'),
            'scopes' => [
                ['scope' => 'faq.read', 'description' => 'Read FAQs'],
                ['scope' => 'admin.everything', 'description' => 'Everything'],
            ],
        ]));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(
            sprintf(Translation::get('msgOAuth2ScopeUnknown'), 'admin.everything'),
            $this->payloadOf($response)['error'],
        );
        self::assertSame(['faq.write' => 'Write FAQs'], $scopes->all());
    }

    /**
     * A scope somebody created in the database before this page existed can be kept or removed.
     *
     * @throws \Exception
     */
    public function testSaveKeepsOrRemovesAStoredScopeTheApiDoesNotKnow(): void
    {
        $scopes = new ScopeRepository($this->configuration);
        $scopes->enable('legacy.scope', 'Created by hand');

        $response = $this->controller()->save($this->jsonRequest([
            'csrf' => $this->csrfToken('oauth2-scopes-save'),
            'scopes' => [['scope' => 'legacy.scope', 'description' => 'Still needed']],
        ]));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(['legacy.scope' => 'Still needed'], $scopes->all());

        $response = $this->controller()->save($this->jsonRequest([
            'csrf' => $this->csrfToken('oauth2-scopes-save'),
            'scopes' => [],
        ]));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame([], $scopes->all());
    }

    private function controller(bool $mayEditConfiguration = true): OAuth2ScopeController
    {
        $controller = new OAuth2ScopeController();
        $controller->setContainer($this->container($mayEditConfiguration));

        return $controller;
    }

    private function csrfToken(string $page): string
    {
        Token::resetInstanceForTests();

        return Token::getInstance($this->session)->getTokenString($page);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $attributes
     */
    private function jsonRequest(array $payload, array $attributes = []): Request
    {
        return new Request([], [], $attributes, [], [], [], json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadOf(JsonResponse $response): array
    {
        return json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function container(bool $mayEditConfiguration): ContainerInterface
    {
        $permission = $this->createStub(PermissionInterface::class);
        $permission->method('hasPermission')->willReturn($mayEditConfiguration);

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);

        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(fn(string $id): ?object => match ($id) {
                'phpmyfaq.configuration' => $this->configuration,
                'phpmyfaq.user.current_user' => $currentUser,
                'session' => $this->session,
                'phpmyfaq.admin.admin-log' => $this->adminLog,
                default => null,
            });

        return $container;
    }
}
