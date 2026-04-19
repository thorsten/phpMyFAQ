<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use OpenSSLAsymmetricKey;
use phpMyFAQ\Auth\Keycloak\KeycloakProviderConfigFactory;
use phpMyFAQ\Auth\Oidc\OidcClient;
use phpMyFAQ\Auth\Oidc\OidcDiscoveryService;
use phpMyFAQ\Auth\Oidc\OidcIdTokenValidator;
use phpMyFAQ\Auth\Oidc\OidcPkceGenerator;
use phpMyFAQ\Auth\Oidc\OidcSession;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception as CoreException;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(KeycloakAuthenticationController::class)]
#[UsesClass(OidcIdTokenValidator::class)]
#[UsesNamespace('phpMyFAQ')]
final class KeycloakAuthenticationControllerTest extends TestCase
{
    private Configuration $configuration;
    private OpenSSLAsymmetricKey $privateKey;

    /** @var array<string, mixed> */
    private array $jwk;

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

        set_error_handler(static fn(): bool => true);
        try {
            $privateKey = openssl_pkey_new([
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'private_key_bits' => 2048,
            ]);
        } finally {
            restore_error_handler();
        }

        self::assertInstanceOf(OpenSSLAsymmetricKey::class, $privateKey);
        $details = openssl_pkey_get_details($privateKey);
        self::assertIsArray($details);
        self::assertIsArray($details['rsa'] ?? null);

        $this->privateKey = $privateKey;
        $this->jwk = [
            'kty' => 'RSA',
            'kid' => 'test-key',
            'alg' => 'RS256',
            'use' => 'sig',
            'n' => $this->base64UrlEncode($details['rsa']['n']),
            'e' => $this->base64UrlEncode($details['rsa']['e']),
        ];
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
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session);
        $oidcSession->setIdToken('session-id-token');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->expects($this->never())->method('getUserData');
        $currentUser->expects($this->once())->method('deleteFromSession');

        $controller = $this->createController([
            new MockResponse(
                '{"issuer":"https://sso.example.test/realms/phpmyfaq","authorization_endpoint":"https://sso.example.test/auth","token_endpoint":"https://sso.example.test/token","userinfo_endpoint":"https://sso.example.test/userinfo","jwks_uri":"https://sso.example.test/jwks","end_session_endpoint":"https://sso.example.test/logout"}',
            ),
        ], $oidcSession, static fn(): CurrentUser => $currentUser);

        $response = $controller->logout();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(
            'https://sso.example.test/logout?client_id=phpmyfaq&post_logout_redirect_uri=https%3A%2F%2Ffaq.example.test%2F&id_token_hint=session-id-token',
            $response->headers->get('Location'),
        );
        $this->assertSame('', $oidcSession->getIdToken());
    }

    public function testLogoutFallsBackToPersistedJwtIdToken(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session);

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->expects($this->once())->method('getUserData')->with('jwt')->willReturn(
            '{"id_token":"persisted-id-token","userinfo":{"sub":"123"}}',
        );
        $currentUser->expects($this->once())->method('deleteFromSession');

        $controller = $this->createController([
            new MockResponse(
                '{"issuer":"https://sso.example.test/realms/phpmyfaq","authorization_endpoint":"https://sso.example.test/auth","token_endpoint":"https://sso.example.test/token","userinfo_endpoint":"https://sso.example.test/userinfo","jwks_uri":"https://sso.example.test/jwks","end_session_endpoint":"https://sso.example.test/logout"}',
            ),
        ], $oidcSession, static fn(): CurrentUser => $currentUser);

        $response = $controller->logout();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(
            'https://sso.example.test/logout?client_id=phpmyfaq&post_logout_redirect_uri=https%3A%2F%2Ffaq.example.test%2F&id_token_hint=persisted-id-token',
            $response->headers->get('Location'),
        );
    }

    /**
     * @throws \Exception
     */
    public function testCallbackReturnsErrorResponseWhenProviderErrorIsSet(): void
    {
        $controller = $this->createController();

        $response = $controller->callback(new Request(['error_description' => 'Denied by provider']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($this->configuration->getDefaultUrl(), $response->headers->get('Location'));
    }

    public function testCallbackStoresUserSessionDataOnSuccessfulLogin(): void
    {
        $idToken = $this->signToken([
            'iss' => 'https://sso.example.test/realms/phpmyfaq',
            'aud' => ['phpmyfaq'],
            'azp' => 'phpmyfaq',
            'nonce' => 'nonce-456',
            'exp' => time() + 300,
        ]);

        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session);
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->expects($this->once())->method('getUserByLogin')->with('john')->willReturn(true);
        $currentUser->expects($this->once())->method('getUserData')->with('keycloak_sub')->willReturn('');
        $currentUser->expects($this->once())->method('setUserData')->with(['keycloak_sub' => '123'])->willReturn(true);
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
                    'id_token' => $idToken,
                    'userinfo' => [
                        'sub' => '123',
                        'preferred_username' => 'john',
                        'email' => 'john@example.com',
                        'name' => 'John Doe',
                    ],
                ],
            ]);
        $currentUser->expects($this->once())->method('setSuccess')->with(true);

        $authUser = $this->createMock(User::class);
        $authUser->expects($this->exactly(2))->method('getUserByLogin')->with('john', false)->willReturn(true);

        $controller = $this->createController(
            [
                new MockResponse(
                    '{"issuer":"https://sso.example.test/realms/phpmyfaq","authorization_endpoint":"https://sso.example.test/auth","token_endpoint":"https://sso.example.test/token","userinfo_endpoint":"https://sso.example.test/userinfo","jwks_uri":"https://sso.example.test/jwks","end_session_endpoint":"https://sso.example.test/logout"}',
                ),
                new MockResponse('{"access_token":"access","refresh_token":"refresh","id_token":"' . $idToken . '"}'),
                new MockResponse(json_encode(['keys' => [$this->jwk]], JSON_THROW_ON_ERROR)),
                new MockResponse(
                    '{"sub":"123","preferred_username":"john","email":"john@example.com","name":"John Doe"}',
                ),
            ],
            $oidcSession,
            static fn(): CurrentUser => $currentUser,
            static fn(): User => $authUser,
        );

        $response = $controller->callback(new Request([
            'code' => 'test-code',
            'state' => 'state-123',
        ]));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($this->configuration->getDefaultUrl(), $response->headers->get('Location'));
        $this->assertSame('', $oidcSession->getAuthorizationState()['state']);
        $this->assertSame($idToken, $oidcSession->getIdToken());
    }

    public function testCallbackResolvesLocalLoginFromStoredKeycloakSubject(): void
    {
        $idToken = $this->signToken([
            'iss' => 'https://sso.example.test/realms/phpmyfaq',
            'aud' => ['phpmyfaq'],
            'azp' => 'phpmyfaq',
            'nonce' => 'nonce-456',
            'exp' => time() + 300,
        ]);

        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session);
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $resolverUser = $this->createMock(User::class);
        $resolverUser->expects($this->once())->method('getUserIdByKeycloakSub')->with('subject-123')->willReturn(55);
        $resolverUser->expects($this->once())->method('getUserById')->with(55)->willReturn(true);
        $resolverUser->expects($this->once())->method('getLogin')->willReturn('linked-user');
        $resolverUser->expects($this->once())->method('getUserByLogin')->with('linked-user', false)->willReturn(true);

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->expects($this->once())->method('getUserByLogin')->with('linked-user')->willReturn(true);
        $currentUser->expects($this->once())->method('getUserData')->with('keycloak_sub')->willReturn('subject-123');
        $currentUser->expects($this->once())->method('setLoggedIn')->with(true);
        $currentUser->expects($this->once())->method('setAuthSource')->with('keycloak');
        $currentUser->expects($this->once())->method('updateSessionId')->with(true);
        $currentUser->expects($this->once())->method('saveToSession');
        $currentUser->expects($this->never())->method('setUserData');
        $currentUser->expects($this->once())->method('setTokenData');
        $currentUser->expects($this->once())->method('setSuccess')->with(true);

        $controller = $this->createController(
            [
                new MockResponse(
                    '{"issuer":"https://sso.example.test/realms/phpmyfaq","authorization_endpoint":"https://sso.example.test/auth","token_endpoint":"https://sso.example.test/token","userinfo_endpoint":"https://sso.example.test/userinfo","jwks_uri":"https://sso.example.test/jwks","end_session_endpoint":"https://sso.example.test/logout"}',
                ),
                new MockResponse('{"access_token":"access","refresh_token":"refresh","id_token":"' . $idToken . '"}'),
                new MockResponse(json_encode(['keys' => [$this->jwk]], JSON_THROW_ON_ERROR)),
                new MockResponse(
                    '{"sub":"subject-123","preferred_username":"john","email":"john@example.com","name":"John Doe"}',
                ),
            ],
            $oidcSession,
            static fn(): CurrentUser => $currentUser,
            static fn(): User => $resolverUser,
        );

        $response = $controller->callback(new Request([
            'code' => 'test-code',
            'state' => 'state-123',
        ]));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($this->configuration->getDefaultUrl(), $response->headers->get('Location'));
    }

    public function testCallbackReturnsFailureWhenStoredKeycloakSubjectDoesNotMatch(): void
    {
        $idToken = $this->signToken([
            'iss' => 'https://sso.example.test/realms/phpmyfaq',
            'aud' => ['phpmyfaq'],
            'azp' => 'phpmyfaq',
            'nonce' => 'nonce-456',
            'exp' => time() + 300,
        ]);

        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session);
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->expects($this->once())->method('getUserByLogin')->with('john')->willReturn(true);
        $currentUser
            ->expects($this->once())
            ->method('getUserData')
            ->with('keycloak_sub')
            ->willReturn('different-subject');
        $currentUser->expects($this->never())->method('setUserData');
        $currentUser->expects($this->never())->method('setLoggedIn');
        $currentUser->expects($this->never())->method('setAuthSource');
        $currentUser->expects($this->never())->method('updateSessionId');
        $currentUser->expects($this->never())->method('saveToSession');
        $currentUser->expects($this->never())->method('setTokenData');
        $currentUser->expects($this->never())->method('setSuccess');

        $authUser = $this->createMock(User::class);
        $authUser->expects($this->exactly(2))->method('getUserByLogin')->with('john', false)->willReturn(true);

        $controller = $this->createController(
            [
                new MockResponse(
                    '{"issuer":"https://sso.example.test/realms/phpmyfaq","authorization_endpoint":"https://sso.example.test/auth","token_endpoint":"https://sso.example.test/token","userinfo_endpoint":"https://sso.example.test/userinfo","jwks_uri":"https://sso.example.test/jwks","end_session_endpoint":"https://sso.example.test/logout"}',
                ),
                new MockResponse('{"access_token":"access","refresh_token":"refresh","id_token":"' . $idToken . '"}'),
                new MockResponse(json_encode(['keys' => [$this->jwk]], JSON_THROW_ON_ERROR)),
                new MockResponse(
                    '{"sub":"subject-123","preferred_username":"john","email":"john@example.com","name":"John Doe"}',
                ),
            ],
            $oidcSession,
            static fn(): CurrentUser => $currentUser,
            static fn(): User => $authUser,
        );

        $response = $controller->callback(new Request([
            'code' => 'test-code',
            'state' => 'state-123',
        ]));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($this->configuration->getDefaultUrl(), $response->headers->get('Location'));
        $this->assertSame('', $oidcSession->getAuthorizationState()['state']);
    }

    public function testCallbackReturnsFailureWhenIdTokenIssuerDoesNotMatch(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session);
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $controller = $this->createController([
            new MockResponse(
                '{"issuer":"https://sso.example.test/realms/phpmyfaq","authorization_endpoint":"https://sso.example.test/auth","token_endpoint":"https://sso.example.test/token","userinfo_endpoint":"https://sso.example.test/userinfo","jwks_uri":"https://sso.example.test/jwks","end_session_endpoint":"https://sso.example.test/logout"}',
            ),
            new MockResponse(
                '{"access_token":"access","refresh_token":"refresh","id_token":"'
                . $this->signToken([
                    'iss' => 'https://issuer.invalid/realms/phpmyfaq',
                    'aud' => ['phpmyfaq'],
                    'azp' => 'phpmyfaq',
                    'nonce' => 'nonce-456',
                    'exp' => time() + 300,
                ])
                . '"}',
            ),
            new MockResponse(json_encode(['keys' => [$this->jwk]], JSON_THROW_ON_ERROR)),
        ], $oidcSession);

        $response = $controller->callback(new Request([
            'code' => 'test-code',
            'state' => 'state-123',
        ]));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($this->configuration->getDefaultUrl(), $response->headers->get('Location'));
        $this->assertSame('', $oidcSession->getAuthorizationState()['state']);
    }

    public function testCallbackReturnsFailureWhenIdTokenAudienceDoesNotMatch(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session);
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $controller = $this->createController([
            new MockResponse(
                '{"issuer":"https://sso.example.test/realms/phpmyfaq","authorization_endpoint":"https://sso.example.test/auth","token_endpoint":"https://sso.example.test/token","userinfo_endpoint":"https://sso.example.test/userinfo","jwks_uri":"https://sso.example.test/jwks","end_session_endpoint":"https://sso.example.test/logout"}',
            ),
            new MockResponse(
                '{"access_token":"access","refresh_token":"refresh","id_token":"'
                . $this->signToken([
                    'iss' => 'https://sso.example.test/realms/phpmyfaq',
                    'aud' => ['different-client'],
                    'azp' => 'different-client',
                    'nonce' => 'nonce-456',
                    'exp' => time() + 300,
                ])
                . '"}',
            ),
            new MockResponse(json_encode(['keys' => [$this->jwk]], JSON_THROW_ON_ERROR)),
        ], $oidcSession);

        $response = $controller->callback(new Request([
            'code' => 'test-code',
            'state' => 'state-123',
        ]));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($this->configuration->getDefaultUrl(), $response->headers->get('Location'));
        $this->assertSame('', $oidcSession->getAuthorizationState()['state']);
    }

    public function testCallbackReturnsFailureWhenIdTokenNonceDoesNotMatch(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session);
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $controller = $this->createController([
            new MockResponse(
                '{"issuer":"https://sso.example.test/realms/phpmyfaq","authorization_endpoint":"https://sso.example.test/auth","token_endpoint":"https://sso.example.test/token","userinfo_endpoint":"https://sso.example.test/userinfo","jwks_uri":"https://sso.example.test/jwks","end_session_endpoint":"https://sso.example.test/logout"}',
            ),
            new MockResponse(
                '{"access_token":"access","refresh_token":"refresh","id_token":"'
                . $this->signToken([
                    'iss' => 'https://sso.example.test/realms/phpmyfaq',
                    'aud' => ['phpmyfaq'],
                    'azp' => 'phpmyfaq',
                    'nonce' => 'unexpected-nonce',
                    'exp' => time() + 300,
                ])
                . '"}',
            ),
            new MockResponse(json_encode(['keys' => [$this->jwk]], JSON_THROW_ON_ERROR)),
        ], $oidcSession);

        $response = $controller->callback(new Request([
            'code' => 'test-code',
            'state' => 'state-123',
        ]));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($this->configuration->getDefaultUrl(), $response->headers->get('Location'));
        $this->assertSame('', $oidcSession->getAuthorizationState()['state']);
    }

    public function testCallbackReturnsFailureWhenIdTokenAuthorizedPartyDoesNotMatch(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session);
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $controller = $this->createController([
            new MockResponse(
                '{"issuer":"https://sso.example.test/realms/phpmyfaq","authorization_endpoint":"https://sso.example.test/auth","token_endpoint":"https://sso.example.test/token","userinfo_endpoint":"https://sso.example.test/userinfo","jwks_uri":"https://sso.example.test/jwks","end_session_endpoint":"https://sso.example.test/logout"}',
            ),
            new MockResponse(
                '{"access_token":"access","refresh_token":"refresh","id_token":"'
                . $this->signToken([
                    'iss' => 'https://sso.example.test/realms/phpmyfaq',
                    'aud' => ['phpmyfaq', 'account'],
                    'azp' => 'different-client',
                    'nonce' => 'nonce-456',
                    'exp' => time() + 300,
                ])
                . '"}',
            ),
            new MockResponse(json_encode(['keys' => [$this->jwk]], JSON_THROW_ON_ERROR)),
        ], $oidcSession);

        $response = $controller->callback(new Request([
            'code' => 'test-code',
            'state' => 'state-123',
        ]));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($this->configuration->getDefaultUrl(), $response->headers->get('Location'));
        $this->assertSame('', $oidcSession->getAuthorizationState()['state']);
    }

    public function testCallbackReturnsFailureWhenIdTokenAuthorizedPartyIsMissingForMultipleAudiences(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session);
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $controller = $this->createController([
            new MockResponse(
                '{"issuer":"https://sso.example.test/realms/phpmyfaq","authorization_endpoint":"https://sso.example.test/auth","token_endpoint":"https://sso.example.test/token","userinfo_endpoint":"https://sso.example.test/userinfo","jwks_uri":"https://sso.example.test/jwks","end_session_endpoint":"https://sso.example.test/logout"}',
            ),
            new MockResponse(
                '{"access_token":"access","refresh_token":"refresh","id_token":"'
                . $this->signToken([
                    'iss' => 'https://sso.example.test/realms/phpmyfaq',
                    'aud' => ['phpmyfaq', 'account'],
                    'nonce' => 'nonce-456',
                    'exp' => time() + 300,
                ])
                . '"}',
            ),
            new MockResponse(json_encode(['keys' => [$this->jwk]], JSON_THROW_ON_ERROR)),
        ], $oidcSession);

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
        ?\Closure $userFactory = null,
    ): KeycloakAuthenticationController {
        $httpClient = new MockHttpClient($responses);
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $controller = new KeycloakAuthenticationController(
            new KeycloakProviderConfigFactory($this->configuration),
            new OidcDiscoveryService($httpClient),
            new OidcPkceGenerator(),
            $oidcSession ?? new OidcSession($session),
            new OidcClient($httpClient),
            new OidcIdTokenValidator($httpClient),
        );

        $controller->setCurrentUserFactory($currentUserFactory);
        $controller->setUserFactory($userFactory);

        return $controller;
    }

    /**
     * @param array<string, mixed> $claims
     */
    private function signToken(array $claims): string
    {
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
            'kid' => 'test-key',
        ];

        $encodedHeader = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR));
        $encodedPayload = $this->base64UrlEncode(json_encode($claims, JSON_THROW_ON_ERROR));
        $signingInput = $encodedHeader . '.' . $encodedPayload;

        $signature = '';
        openssl_sign($signingInput, $signature, $this->privateKey, OPENSSL_ALGO_SHA256);

        return $signingInput . '.' . $this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
