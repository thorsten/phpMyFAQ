<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Auth\Keycloak\KeycloakProviderConfigFactory;
use phpMyFAQ\Auth\Oidc\OidcClient;
use phpMyFAQ\Auth\Oidc\OidcDiscoveryService;
use phpMyFAQ\Auth\Oidc\OidcPkceGenerator;
use phpMyFAQ\Auth\Oidc\OidcSession;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception as CoreException;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(KeycloakAuthenticationController::class)]
#[UsesNamespace('phpMyFAQ')]
final class KeycloakAuthenticationControllerTest extends TestCase
{
    private Configuration $configuration;

    /**
     * @throws CoreException
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init('en');
        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        try {
            $this->configuration = Configuration::getConfigurationInstance();
        } catch (\TypeError) {
            $dbHandle = new Sqlite3();
            $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
            $this->configuration = new Configuration($dbHandle);
        }

        $reflection = new \ReflectionClass(Configuration::class);
        $property = $reflection->getProperty('config');
        /** @var array<string, mixed> $config */
        $config = $property->getValue($this->configuration);
        $config['keycloak.enable'] = 'true';
        $config['keycloak.baseUrl'] = 'https://sso.example.test';
        $config['keycloak.realm'] = 'phpmyfaq';
        $config['keycloak.clientId'] = 'phpmyfaq';
        $config['keycloak.clientSecret'] = 'secret';
        $config['keycloak.redirectUri'] = 'https://faq.example.test/auth/keycloak/callback';
        $config['keycloak.scopes'] = 'openid profile email';
        $config['keycloak.autoProvision'] = 'true';
        $config['keycloak.logoutRedirectUrl'] = 'https://faq.example.test/';
        $property->setValue($this->configuration, $config);
    }

    public function testAuthorizeReturnsRedirectResponse(): void
    {
        $controller = $this->createController([
            new MockResponse(
                '{"issuer":"https://sso.example.test/realms/phpmyfaq","authorization_endpoint":"https://sso.example.test/auth","token_endpoint":"https://sso.example.test/token","userinfo_endpoint":"https://sso.example.test/userinfo","jwks_uri":"https://sso.example.test/jwks","end_session_endpoint":"https://sso.example.test/logout"}',
            ),
        ]);

        $response = $controller->authorize();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringStartsWith('https://sso.example.test/auth?', (string) $response->headers->get('Location'));
        $this->assertStringContainsString('client_id=phpmyfaq', (string) $response->headers->get('Location'));
    }

    public function testLogoutReturnsProviderLogoutResponse(): void
    {
        $controller = $this->createController([
            new MockResponse(
                '{"issuer":"https://sso.example.test/realms/phpmyfaq","authorization_endpoint":"https://sso.example.test/auth","token_endpoint":"https://sso.example.test/token","userinfo_endpoint":"https://sso.example.test/userinfo","jwks_uri":"https://sso.example.test/jwks","end_session_endpoint":"https://sso.example.test/logout"}',
            ),
        ]);

        $response = $controller->logout();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(
            'https://sso.example.test/logout?client_id=phpmyfaq&post_logout_redirect_uri=https%3A%2F%2Ffaq.example.test%2F',
            $response->headers->get('Location'),
        );
    }

    public function testCallbackReturnsErrorResponseWhenProviderErrorIsSet(): void
    {
        $controller = $this->createController();

        $response = $controller->callback(new Request(['error_description' => 'Denied by provider']));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('Denied by provider', $response->getContent());
    }

    public function testCallbackStoresUserSessionDataOnSuccessfulLogin(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session);
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->expects($this->once())->method('getUserByLogin')->with('john')->willReturn(true);
        $currentUser->expects($this->once())->method('setLoggedIn')->with(true);
        $currentUser->expects($this->once())->method('setAuthSource')->with('keycloak');
        $currentUser->expects($this->once())->method('updateSessionId')->with(true);
        $currentUser->expects($this->once())->method('saveToSession');
        $currentUser
            ->expects($this->once())
            ->method('setTokenData')
            ->with([
                'refresh_token' => 'refresh',
                'access_token' => 'access',
                'code_verifier' => 'verifier-789',
                'jwt' => [
                    'id_token' => 'header.payload.signature',
                    'userinfo' => [
                        'sub' => '123',
                        'preferred_username' => 'john',
                        'email' => 'john@example.com',
                        'name' => 'John Doe',
                    ],
                ],
            ]);
        $currentUser->expects($this->once())->method('setSuccess')->with(true);

        $controller = $this->createController(
            [
                new MockResponse(
                    '{"issuer":"https://sso.example.test/realms/phpmyfaq","authorization_endpoint":"https://sso.example.test/auth","token_endpoint":"https://sso.example.test/token","userinfo_endpoint":"https://sso.example.test/userinfo","jwks_uri":"https://sso.example.test/jwks","end_session_endpoint":"https://sso.example.test/logout"}',
                ),
                new MockResponse(
                    '{"access_token":"access","refresh_token":"refresh","id_token":"header.payload.signature"}',
                ),
                new MockResponse(
                    '{"sub":"123","preferred_username":"john","email":"john@example.com","name":"John Doe"}',
                ),
            ],
            $oidcSession,
            static fn(): CurrentUser => $currentUser,
        );

        $response = $controller->callback(new Request([
            'code' => 'test-code',
            'state' => 'state-123',
        ]));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($this->configuration->getDefaultUrl(), $response->headers->get('Location'));
        $this->assertSame('', $oidcSession->getAuthorizationState()['state']);
    }

    public function testCallbackReturnsDefaultRedirectWhenStateDoesNotMatch(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session);
        $oidcSession->setAuthorizationState('expected-state', 'nonce-456', 'verifier-789');

        $controller = $this->createController([], $oidcSession);
        $response = $controller->callback(new Request([
            'code' => 'test-code',
            'state' => 'different-state',
        ]));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($this->configuration->getDefaultUrl(), $response->headers->get('Location'));
        $this->assertSame('', $oidcSession->getAuthorizationState()['state']);
    }

    /**
     * @param list<MockResponse> $responses
     */
    private function createController(
        array $responses = [],
        ?OidcSession $oidcSession = null,
        ?\Closure $currentUserFactory = null,
    ): KeycloakAuthenticationController {
        $httpClient = new MockHttpClient($responses);
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        return new KeycloakAuthenticationController(
            new KeycloakProviderConfigFactory($this->configuration),
            new OidcDiscoveryService($httpClient),
            new OidcPkceGenerator(),
            $oidcSession ?? new OidcSession($session),
            new OidcClient($httpClient),
            $currentUserFactory,
        );
    }
}
