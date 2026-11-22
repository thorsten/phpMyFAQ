<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Auth\OAuth2\Entity\ClientEntity;
use phpMyFAQ\Auth\OAuth2\Repository\ClientRepository;
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
#[CoversClass(OAuth2ClientController::class)]
#[UsesNamespace('phpMyFAQ')]
final class OAuth2ClientControllerTest extends TestCase
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
    public function testCreateRequiresTheConfigurationPermission(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->controller(mayEditConfiguration: false)->create($this->jsonRequest([]));
    }

    /**
     * @throws \Exception
     */
    public function testCreateRejectsAnInvalidCsrfToken(): void
    {
        $response = $this->controller()->create($this->jsonRequest(['csrf' => 'wrong', 'name' => 'Intranet']));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame([], new ClientRepository($this->configuration)->all());
    }

    /**
     * @throws \Exception
     */
    public function testCreateRegistersAConfidentialClientAndReturnsItsSecretOnce(): void
    {
        $this->adminLog->expects($this->once())->method('log');

        $response = $this->controller()->create($this->jsonRequest([
            'csrf' => $this->csrfToken('oauth2-client-save'),
            'name' => 'Reporting',
            'redirectUri' => '',
            'grants' => ['client_credentials'],
            'confidential' => true,
        ]));
        $payload = $this->payloadOf($response);

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertSame('Reporting', $payload['name']);
        self::assertMatchesRegularExpression('/^pmf_[a-f0-9]{32}$/', $payload['clientId']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $payload['clientSecret']);

        // Only the hash is stored
        $clients = new ClientRepository($this->configuration);
        $client = $clients->getClientEntity($payload['clientId']);
        self::assertInstanceOf(ClientEntity::class, $client);
        self::assertNotSame($payload['clientSecret'], $client->secret);
        self::assertTrue($clients->validateClient($payload['clientId'], $payload['clientSecret'], 'client_credentials'));
    }

    /**
     * @throws \Exception
     */
    public function testCreateRegistersAPublicClientWithoutSecret(): void
    {
        $response = $this->controller()->create($this->jsonRequest([
            'csrf' => $this->csrfToken('oauth2-client-save'),
            'name' => 'Mobile app',
            'redirectUri' => 'org.example.app://callback',
            'grants' => ['authorization_code'],
            'confidential' => false,
        ]));
        $payload = $this->payloadOf($response);

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertNull($payload['clientSecret']);

        $client = new ClientRepository($this->configuration)->getClientEntity($payload['clientId']);
        self::assertInstanceOf(ClientEntity::class, $client);
        self::assertFalse($client->isConfidential());
        self::assertNull($client->secret);
    }

    /**
     * @param array<string, mixed> $registration
     * @throws \Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidRegistrations')]
    public function testCreateExplainsWhyARegistrationIsRefused(array $registration, string $translationKey): void
    {
        $this->adminLog->expects($this->never())->method('log');

        $response = $this->controller()->create($this->jsonRequest(
            ['csrf' => $this->csrfToken('oauth2-client-save')] + $registration,
        ));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get($translationKey), $this->payloadOf($response)['error']);
        self::assertSame([], new ClientRepository($this->configuration)->all());
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidRegistrations(): array
    {
        return [
            'no name' => [['grants' => ['client_credentials']], 'msgOAuth2ClientNameInvalid'],
            'no grant' => [['name' => 'Intranet'], 'msgOAuth2ClientGrantRequired'],
            'grant the server does not issue' => [
                ['name' => 'Intranet', 'grants' => ['password']],
                'msgOAuth2ClientGrantUnknown',
            ],
            'authorization code without redirect URI' => [
                ['name' => 'Intranet', 'grants' => ['authorization_code']],
                'msgOAuth2ClientRedirectUriRequired',
            ],
            'public client with client credentials' => [
                ['name' => 'Intranet', 'grants' => ['client_credentials'], 'confidential' => false],
                'msgOAuth2ClientCredentialsNeedSecret',
            ],
        ];
    }

    /**
     * @throws \Exception
     */
    public function testUpdateChangesTheClientButKeepsItsType(): void
    {
        $clientId = $this->registerConfidentialClient();
        $this->adminLog->expects($this->once())->method('log');

        $response = $this->controller()->update($this->jsonRequest([
            'csrf' => $this->csrfToken('oauth2-client-save'),
            'name' => 'Reporting v2',
            'redirectUri' => 'https://reporting.example.org/callback',
            'grants' => ['authorization_code', 'client_credentials'],
            'confidential' => false,
        ], ['clientId' => $clientId]));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('msgOAuth2ClientSaved'), $this->payloadOf($response)['success']);

        $client = new ClientRepository($this->configuration)->getClientEntity($clientId);
        self::assertInstanceOf(ClientEntity::class, $client);
        self::assertSame('Reporting v2', $client->getName());
        self::assertSame(['authorization_code', 'client_credentials'], $client->allowedGrants);
        self::assertTrue($client->isConfidential());
    }

    /**
     * @throws \Exception
     */
    public function testUpdateRefusesAnInvalidRegistration(): void
    {
        $clientId = $this->registerConfidentialClient();

        $response = $this->controller()->update($this->jsonRequest([
            'csrf' => $this->csrfToken('oauth2-client-save'),
            'name' => 'Reporting',
            'grants' => [],
        ], ['clientId' => $clientId]));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('msgOAuth2ClientGrantRequired'), $this->payloadOf($response)['error']);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateOfAnUnknownClientIsNotFound(): void
    {
        $response = $this->controller()->update($this->jsonRequest([
            'csrf' => $this->csrfToken('oauth2-client-save'),
            'name' => 'Reporting',
            'grants' => ['client_credentials'],
        ], ['clientId' => 'pmf_unknown']));

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        self::assertSame(Translation::get('msgOAuth2ClientNotFound'), $this->payloadOf($response)['error']);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteRemovesTheClient(): void
    {
        $clientId = $this->registerConfidentialClient();
        $this->adminLog->expects($this->once())->method('log');

        $response = $this->controller()->delete($this->jsonRequest(
            ['csrf' => $this->csrfToken('oauth2-client-delete')],
            ['clientId' => $clientId],
        ));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertNull(new ClientRepository($this->configuration)->getClientEntity($clientId));
    }

    /**
     * @throws \Exception
     */
    public function testDeleteRejectsTheCsrfTokenOfAnotherAction(): void
    {
        $clientId = $this->registerConfidentialClient();

        $response = $this->controller()->delete($this->jsonRequest(
            ['csrf' => $this->csrfToken('oauth2-client-save')],
            ['clientId' => $clientId],
        ));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertInstanceOf(
            ClientEntity::class,
            new ClientRepository($this->configuration)->getClientEntity($clientId),
        );
    }

    /**
     * @throws \Exception
     */
    public function testDeleteOfAnUnknownClientIsNotFound(): void
    {
        $response = $this->controller()->delete($this->jsonRequest(
            ['csrf' => $this->csrfToken('oauth2-client-delete')],
            ['clientId' => 'pmf_unknown'],
        ));

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    private function registerConfidentialClient(): string
    {
        $response = $this->controller()->create($this->jsonRequest([
            'csrf' => $this->csrfToken('oauth2-client-save'),
            'name' => 'Reporting',
            'grants' => ['client_credentials'],
        ]));

        return (string) $this->payloadOf($response)['clientId'];
    }

    private function controller(bool $mayEditConfiguration = true): OAuth2ClientController
    {
        $controller = new OAuth2ClientController();
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
