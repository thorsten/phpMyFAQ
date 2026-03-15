<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Auth\OAuth2\AuthorizationServer as OAuth2AuthorizationServer;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(OAuth2Controller::class)]
#[UsesNamespace('phpMyFAQ')]
class OAuth2ControllerTest extends TestCase
{
    private Configuration $configuration;
    private Sqlite3 $dbHandle;
    private string $databasePath;
    private ?Configuration $previousConfiguration = null;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        Token::resetInstanceForTests();

        Strings::init();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();
        $configurationProperty->setValue(null, null);

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-api-oauth2-controller-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        $this->configuration = new Configuration($this->dbHandle);

        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $this->dbHandle);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'sqlite3');
        Database::setTablePrefix('');

        $language = new Language($this->configuration, new Session(new MockArraySessionStorage()));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);
    }

    protected function tearDown(): void
    {
        Token::resetInstanceForTests();
        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, $this->previousConfiguration);

        $this->dbHandle->close();
        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, null);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, '');
        @unlink($this->databasePath);
        parent::tearDown();
    }

    /**
     * @throws \Exception
     */
    public function testTokenReturnsServiceUnavailableWhenOAuth2NotConfigured(): void
    {
        $controller = new OAuth2Controller(new OAuth2AuthorizationServer($this->configuration));
        $response = $controller->token(new Request([], [], [], [], [], [], ''));

        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        $this->assertStringContainsString('oauth2_unavailable', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testTokenReturnsIssuerResponsePayload(): void
    {
        $controller = new OAuth2Controller(new OAuth2AuthorizationServer($this->configuration));
        $authorizationServer = new OAuth2AuthorizationServer($this->configuration);
        $authorizationServer->setTokenIssuer(static fn(): array => [
            'body' => ['access_token' => 'abc123', 'token_type' => 'Bearer'],
            'status' => Response::HTTP_OK,
            'headers' => ['Cache-Control' => 'no-store'],
        ]);
        $controller->setAuthorizationServer($authorizationServer);

        $response = $controller->token(new Request([], [], [], [], [], [], ''));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('abc123', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testAuthorizeRequiresAuthenticatedUser(): void
    {
        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn(false);

        $controller = new OAuth2Controller(new OAuth2AuthorizationServer($this->configuration));
        $reflection = new \ReflectionProperty($controller, 'currentUser');
        $reflection->setValue($controller, $currentUser);

        $response = $controller->authorize(new Request());

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertStringContainsString('access_denied', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testAuthorizeGetReturnsConsentRequiredForAuthenticatedUser(): void
    {
        $controller = new OAuth2Controller(new OAuth2AuthorizationServer($this->configuration));
        $this->injectAuthenticatedContext($controller);

        $response = $controller->authorize(Request::create('/oauth/authorize', 'GET'));

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString('consent_required', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testAuthorizePostReturnsForbiddenWhenCsrfValidationFails(): void
    {
        $controller = new OAuth2Controller(new OAuth2AuthorizationServer($this->configuration));
        $this->injectAuthenticatedContext($controller);

        $request = Request::create('/oauth/authorize', 'POST', [
            'csrf' => 'invalid',
            'approve' => 'true',
        ]);
        $response = $controller->authorize($request);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertStringContainsString('CSRF token validation failed', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testAuthorizePostReturnsBadRequestWhenApproveIsMissing(): void
    {
        $controller = new OAuth2Controller(new OAuth2AuthorizationServer($this->configuration));
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('oauth2-authorize');
        $this->injectAuthenticatedContext($controller, $session);

        $request = Request::create('/oauth/authorize', 'POST', ['csrf' => $csrfToken]);
        $response = $controller->authorize($request);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString('Missing required approve parameter', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testAuthorizePostReturnsRedirectWhenLocationHeaderExists(): void
    {
        $controller = new OAuth2Controller(new OAuth2AuthorizationServer($this->configuration));
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('oauth2-authorize');
        $this->injectAuthenticatedContext($controller, $session);

        $authorizationServer = new OAuth2AuthorizationServer($this->configuration);
        $authorizationServer->setAuthorizationCompleter(static fn(): array => [
            'body' => '',
            'status' => Response::HTTP_FOUND,
            'headers' => ['Location' => 'https://client.example/callback?code=abc'],
        ]);
        $controller->setAuthorizationServer($authorizationServer);

        $request = Request::create('/oauth/authorize', 'POST', [
            'csrf' => $csrfToken,
            'approve' => 'true',
        ]);

        $response = $controller->authorize($request);

        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertSame('https://client.example/callback?code=abc', $response->headers->get('Location'));
    }

    /**
     * @throws \Exception
     */
    public function testAuthorizePostReturnsJsonWhenAuthorizationServerReturnsJsonContent(): void
    {
        $controller = new OAuth2Controller(new OAuth2AuthorizationServer($this->configuration));
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('oauth2-authorize');
        $this->injectAuthenticatedContext($controller, $session);

        $authorizationServer = new OAuth2AuthorizationServer($this->configuration);
        $authorizationServer->setAuthorizationCompleter(static fn(): array => [
            'body' => json_encode(['authorized' => true], JSON_THROW_ON_ERROR),
            'status' => Response::HTTP_OK,
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $controller->setAuthorizationServer($authorizationServer);

        $request = Request::create('/oauth/authorize', 'POST', [
            'csrf' => $csrfToken,
            'approve' => 'true',
        ]);

        $response = $controller->authorize($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('authorized', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testAuthorizePostReturnsRawBodyWhenAuthorizationServerReturnsNonJsonContent(): void
    {
        $controller = new OAuth2Controller(new OAuth2AuthorizationServer($this->configuration));
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('oauth2-authorize');
        $this->injectAuthenticatedContext($controller, $session);

        $authorizationServer = new OAuth2AuthorizationServer($this->configuration);
        $authorizationServer->setAuthorizationCompleter(static fn(): array => [
            'body' => 'approved',
            'status' => Response::HTTP_ACCEPTED,
            'headers' => ['Content-Type' => 'text/plain'],
        ]);
        $controller->setAuthorizationServer($authorizationServer);

        $request = Request::create('/oauth/authorize', 'POST', [
            'csrf' => $csrfToken,
            'approve' => 'false',
        ]);

        $response = $controller->authorize($request);

        $this->assertSame(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $this->assertSame('approved', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testAuthorizePostReturnsClientErrorPayloadForRuntimeException(): void
    {
        $controller = new OAuth2Controller(new OAuth2AuthorizationServer($this->configuration));
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('oauth2-authorize');
        $this->injectAuthenticatedContext($controller, $session);

        $authorizationServer = new OAuth2AuthorizationServer($this->configuration);
        $authorizationServer->setAuthorizationCompleter(static function (): array {
            throw new \RuntimeException('Consent denied', 400);
        });
        $controller->setAuthorizationServer($authorizationServer);

        $request = Request::create('/oauth/authorize', 'POST', [
            'csrf' => $csrfToken,
            'approve' => 'false',
        ]);

        $response = $controller->authorize($request);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString('Consent denied', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testAuthorizePostReturnsInternalServerErrorPayloadForRuntimeException(): void
    {
        $controller = new OAuth2Controller(new OAuth2AuthorizationServer($this->configuration));
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('oauth2-authorize');
        $this->injectAuthenticatedContext($controller, $session);

        $authorizationServer = new OAuth2AuthorizationServer($this->configuration);
        $authorizationServer->setAuthorizationCompleter(static function (): array {
            throw new \RuntimeException('provider offline', 503);
        });
        $controller->setAuthorizationServer($authorizationServer);

        $request = Request::create('/oauth/authorize', 'POST', [
            'csrf' => $csrfToken,
            'approve' => 'true',
        ]);

        $response = $controller->authorize($request);

        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        $this->assertStringContainsString('Internal server error', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testTokenReturnsRuntimeStatusCodeForClientErrors(): void
    {
        $controller = new OAuth2Controller(new OAuth2AuthorizationServer($this->configuration));
        $authorizationServer = new OAuth2AuthorizationServer($this->configuration);
        $authorizationServer->setTokenIssuer(static function (): array {
            throw new \RuntimeException('Too many requests', 429);
        });
        $controller->setAuthorizationServer($authorizationServer);

        $response = $controller->token(new Request());

        $this->assertSame(429, $response->getStatusCode());
        $this->assertStringContainsString('Too many requests', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testTokenNormalizesUnexpectedRuntimeCodeToServiceUnavailable(): void
    {
        $controller = new OAuth2Controller(new OAuth2AuthorizationServer($this->configuration));
        $authorizationServer = new OAuth2AuthorizationServer($this->configuration);
        $authorizationServer->setTokenIssuer(static function (): array {
            throw new \RuntimeException('Broken', 1);
        });
        $controller->setAuthorizationServer($authorizationServer);

        $response = $controller->token(new Request());

        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        $this->assertStringContainsString('Internal server error', (string) $response->getContent());
    }

    private function injectAuthenticatedContext(OAuth2Controller $controller, ?Session $session = null): void
    {
        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);

        $reflectionCurrentUser = new \ReflectionProperty($controller, 'currentUser');
        $reflectionCurrentUser->setValue($controller, $currentUser);

        $reflectionSession = new \ReflectionProperty($controller, 'session');
        $reflectionSession->setValue($controller, $session ?? new Session(new MockArraySessionStorage()));
    }
}
