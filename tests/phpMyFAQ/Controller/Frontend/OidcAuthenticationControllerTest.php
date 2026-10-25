<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use OpenSSLAsymmetricKey;
use phpMyFAQ\Auth\Oidc\GenericOidcProviderConfigFactory;
use phpMyFAQ\Auth\Oidc\OidcClient;
use phpMyFAQ\Auth\Oidc\OidcDiscoveryService;
use phpMyFAQ\Auth\Oidc\OidcIdTokenValidator;
use phpMyFAQ\Auth\Oidc\OidcPkceGenerator;
use phpMyFAQ\Auth\Oidc\OidcSession;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception as CoreException;
use phpMyFAQ\Seo;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\TestDatabaseTrait;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(OidcAuthenticationController::class)]
#[UsesClass(OidcIdTokenValidator::class)]
#[UsesNamespace('phpMyFAQ')]
final class OidcAuthenticationControllerTest extends TestCase
{
    use TestDatabaseTrait;

    private const string ISSUER = 'https://auth.example.test';

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

        $this->configuration = $this->createTestConfiguration();

        $reflection = new \ReflectionClass(Configuration::class);
        $property = $reflection->getProperty('config');
        /** @var array<string, mixed> $config */
        $config = $property->getValue($this->configuration);
        $config['oidc.enable'] = 'true';
        $config['oidc.discoveryUrl'] = 'https://auth.example.test/.well-known/openid-configuration';
        $config['oidc.clientId'] = 'phpmyfaq';
        $config['oidc.clientSecret'] = 'secret';
        $config['oidc.redirectUri'] = 'https://faq.example.test/auth/oidc/callback';
        $config['oidc.scopes'] = 'openid profile email';
        $config['oidc.autoProvision'] = 'true';
        $config['oidc.groupsClaim'] = 'groups';
        $config['oidc.logoutRedirectUrl'] = 'https://faq.example.test/';
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
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session, 'oidc');

        $controller = $this->createController([
            new MockResponse(
                '{"issuer":"https://auth.example.test","authorization_endpoint":"https://auth.example.test/auth","token_endpoint":"https://auth.example.test/token","userinfo_endpoint":"https://auth.example.test/userinfo","jwks_uri":"https://auth.example.test/jwks","end_session_endpoint":"https://auth.example.test/logout"}',
            ),
        ], $oidcSession);

        $response = $controller->authorize();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $location = (string) $response->headers->get('Location');
        $this->assertStringStartsWith('https://auth.example.test/auth?', $location);
        $this->assertStringContainsString('client_id=phpmyfaq', $location);
        $this->assertStringContainsString(
            'redirect_uri=' . urlencode('https://faq.example.test/auth/oidc/callback'),
            $location,
        );
        $this->assertStringContainsString('code_challenge_method=S256', $location);
        $this->assertStringContainsString('scope=' . urlencode('openid profile email'), $location);

        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertNotEmpty($query['state'] ?? '');
        $this->assertNotEmpty($query['nonce'] ?? '');

        $authorizationState = $oidcSession->getAuthorizationState();
        $this->assertNotSame('', $authorizationState['state']);
        $this->assertNotSame('', $authorizationState['nonce']);
        $this->assertNotSame('', $authorizationState['verifier']);
    }

    public function testLogoutReturnsProviderLogoutResponse(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session, 'oidc');
        $oidcSession->setIdToken('session-id-token');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->expects($this->never())->method('getUserData');
        $currentUser->expects($this->once())->method('deleteFromSession');

        $controller = $this->createController(
            [
                new MockResponse(
                    '{"issuer":"https://auth.example.test","authorization_endpoint":"https://auth.example.test/auth","token_endpoint":"https://auth.example.test/token","userinfo_endpoint":"https://auth.example.test/userinfo","jwks_uri":"https://auth.example.test/jwks","end_session_endpoint":"https://auth.example.test/logout"}',
                ),
            ],
            $oidcSession,
            static fn(): CurrentUser => $currentUser,
        );

        $response = $controller->logout();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(
            'https://auth.example.test/logout?client_id=phpmyfaq&post_logout_redirect_uri=https%3A%2F%2Ffaq.example.test%2F&id_token_hint=session-id-token',
            $response->headers->get('Location'),
        );
        $this->assertSame('', $oidcSession->getIdToken());
    }

    public function testLogoutClearsLocalSessionWhenProviderIsDisabled(): void
    {
        $reflection = new \ReflectionClass(Configuration::class);
        $property = $reflection->getProperty('config');
        /** @var array<string, mixed> $config */
        $config = $property->getValue($this->configuration);
        $previousEnable = $config['oidc.enable'] ?? null;
        $config['oidc.enable'] = 'false';
        $property->setValue($this->configuration, $config);

        try {
            $session = new Session(new MockArraySessionStorage());
            $session->start();
            $oidcSession = new OidcSession($session, 'oidc');
            $oidcSession->setIdToken('session-id-token');

            $currentUser = $this->createMock(CurrentUser::class);
            $currentUser->expects($this->once())->method('deleteFromSession');

            $controller = $this->createController([], $oidcSession, static fn(): CurrentUser => $currentUser);

            $response = $controller->logout();

            $this->assertInstanceOf(RedirectResponse::class, $response);
            $this->assertSame($this->configuration->getDefaultUrl(), $response->headers->get('Location'));
            $this->assertSame('', $oidcSession->getIdToken());
        } finally {
            /** @var array<string, mixed> $restoredConfig */
            $restoredConfig = $property->getValue($this->configuration);
            if ($previousEnable === null) {
                unset($restoredConfig['oidc.enable']);
            } else {
                $restoredConfig['oidc.enable'] = $previousEnable;
            }
            $property->setValue($this->configuration, $restoredConfig);
        }
    }

    public function testLogoutFallsBackToPersistedJwtIdToken(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session, 'oidc');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser
            ->expects($this->once())
            ->method('getUserData')
            ->with('jwt')
            ->willReturn('{"id_token":"persisted-id-token","userinfo":{"sub":"123"}}');
        $currentUser->expects($this->once())->method('deleteFromSession');

        $controller = $this->createController(
            [
                new MockResponse(
                    '{"issuer":"https://auth.example.test","authorization_endpoint":"https://auth.example.test/auth","token_endpoint":"https://auth.example.test/token","userinfo_endpoint":"https://auth.example.test/userinfo","jwks_uri":"https://auth.example.test/jwks","end_session_endpoint":"https://auth.example.test/logout"}',
                ),
            ],
            $oidcSession,
            static fn(): CurrentUser => $currentUser,
        );

        $response = $controller->logout();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(
            'https://auth.example.test/logout?client_id=phpmyfaq&post_logout_redirect_uri=https%3A%2F%2Ffaq.example.test%2F&id_token_hint=persisted-id-token',
            $response->headers->get('Location'),
        );
    }

    public function testCallbackRedirectsHomeWhenEnabledProviderHasNoDiscoveryUrl(): void
    {
        $property = new \ReflectionProperty(Configuration::class, 'config');
        /** @var array<string, mixed> $original */
        $original = $property->getValue($this->configuration);
        $config = $original;
        $config['oidc.enable'] = 'true';
        $config['oidc.discoveryUrl'] = '';
        $property->setValue($this->configuration, $config);

        try {
            $response = $this->createController()->callback(new Request(['code' => 'x', 'state' => 'y']));
        } finally {
            $property->setValue($this->configuration, $original);
        }

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($this->configuration->getDefaultUrl(), $response->headers->get('Location'));
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

    public function testCallbackProvisionsNewUserAndStoresSessionDataOnSuccessfulLogin(): void
    {
        $idToken = $this->signToken([
            'iss' => 'https://auth.example.test',
            'sub' => '123',
            'aud' => ['phpmyfaq'],
            'azp' => 'phpmyfaq',
            'nonce' => 'nonce-456',
            'iat' => time(),
            'exp' => time() + 300,
        ]);

        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session, 'oidc');
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->expects($this->once())->method('getUserByLogin')->with('john')->willReturn(true);
        $currentUser
            ->expects($this->exactly(3))
            ->method('getUserData')
            ->willReturnMap([['oidc_sub', '123'], ['oidc_iss', self::ISSUER], ['twofactor_enabled', 0]]);
        $currentUser->expects($this->never())->method('setUserData');
        $currentUser->expects($this->once())->method('setLoggedIn')->with(true);
        $currentUser->expects($this->once())->method('setAuthSource')->with('oidc');
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
        $authUser->expects($this->once())->method('getUserIdByOidcIdentity')->with(self::ISSUER, '123')->willReturn(0);
        $authUser->expects($this->exactly(2))->method('getUserByLogin')->with('john', false)->willReturn(false);
        $authUser->expects($this->once())->method('getUserIdByEmail')->with('john@example.com')->willReturn(0);
        $authUser->expects($this->once())->method('createUser')->with('john', '', '')->willReturn(true);
        $authUser
            ->expects($this->once())
            ->method('setUserData')
            ->with([
                'display_name' => 'John Doe',
                'email' => 'john@example.com',
                'oidc_sub' => '123',
                'oidc_iss' => self::ISSUER,
            ])
            ->willReturn(true);

        $controller = $this->createController(
            [
                new MockResponse(
                    '{"issuer":"https://auth.example.test","authorization_endpoint":"https://auth.example.test/auth","token_endpoint":"https://auth.example.test/token","userinfo_endpoint":"https://auth.example.test/userinfo","jwks_uri":"https://auth.example.test/jwks","end_session_endpoint":"https://auth.example.test/logout"}',
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

    public function testCallbackResolvesLocalLoginFromStoredOidcSubject(): void
    {
        $idToken = $this->signToken([
            'iss' => 'https://auth.example.test',
            'sub' => 'subject-123',
            'aud' => ['phpmyfaq'],
            'azp' => 'phpmyfaq',
            'nonce' => 'nonce-456',
            'iat' => time(),
            'exp' => time() + 300,
        ]);

        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session, 'oidc');
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $resolverUser = $this->createMock(User::class);
        $resolverUser->expects($this->once())->method('getUserIdByOidcIdentity')->with(self::ISSUER, 'subject-123')->willReturn(55);
        $resolverUser->expects($this->once())->method('getUserById')->with(55)->willReturn(true);
        $resolverUser->expects($this->once())->method('getLogin')->willReturn('linked-user');
        $resolverUser->expects($this->once())->method('getUserByLogin')->with('linked-user', false)->willReturn(true);
        $resolverUser
            ->expects($this->exactly(2))
            ->method('getUserData')
            ->willReturnMap([['oidc_sub', 'subject-123'], ['oidc_iss', self::ISSUER]]);

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->expects($this->once())->method('getUserByLogin')->with('linked-user')->willReturn(true);
        $currentUser
            ->expects($this->exactly(3))
            ->method('getUserData')
            ->willReturnMap([['oidc_sub', 'subject-123'], ['oidc_iss', self::ISSUER], ['twofactor_enabled', 0]]);
        $currentUser->expects($this->once())->method('setLoggedIn')->with(true);
        $currentUser->expects($this->once())->method('setAuthSource')->with('oidc');
        $currentUser->expects($this->once())->method('updateSessionId')->with(true);
        $currentUser->expects($this->once())->method('saveToSession');
        $currentUser->expects($this->never())->method('setUserData');
        $currentUser->expects($this->once())->method('setTokenData');
        $currentUser->expects($this->once())->method('setSuccess')->with(true);

        $controller = $this->createController(
            [
                new MockResponse(
                    '{"issuer":"https://auth.example.test","authorization_endpoint":"https://auth.example.test/auth","token_endpoint":"https://auth.example.test/token","userinfo_endpoint":"https://auth.example.test/userinfo","jwks_uri":"https://auth.example.test/jwks","end_session_endpoint":"https://auth.example.test/logout"}',
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

    /**
     * A OpenID Connect identity is the first factor only: an account with TOTP two-factor
     * authentication enabled must be sent through the token step instead of receiving
     * a fully authenticated session from the callback.
     */
    public function testCallbackDefersToTokenStepWhenTwoFactorIsEnabled(): void
    {
        $idToken = $this->signToken([
            'iss' => 'https://auth.example.test',
            'sub' => 'subject-123',
            'aud' => ['phpmyfaq'],
            'azp' => 'phpmyfaq',
            'nonce' => 'nonce-456',
            'iat' => time(),
            'exp' => time() + 300,
        ]);

        $oidcSession = new OidcSession($this->createStartedSession(), 'oidc');
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('getUserId')->willReturn(55);
        $currentUser->expects($this->once())->method('getUserByLogin')->with('linked-user')->willReturn(true);
        $currentUser
            ->expects($this->exactly(3))
            ->method('getUserData')
            ->willReturnMap([['oidc_sub', 'subject-123'], ['oidc_iss', self::ISSUER], ['twofactor_enabled', 1]]);
        $currentUser->expects($this->once())->method('isTwoFactorLockedOut')->willReturn(false);
        $currentUser->expects($this->once())->method('setAuthSource')->with('oidc');
        $currentUser->expects($this->once())->method('setTokenData');
        // No session is granted before the second factor has been verified.
        $currentUser->expects($this->never())->method('setLoggedIn');
        $currentUser->expects($this->never())->method('updateSessionId');
        $currentUser->expects($this->never())->method('saveToSession');
        // setSuccess() clears the failure budget, so it must not run before the token step.
        $currentUser->expects($this->never())->method('setSuccess');

        $resolverUser = $this->createLinkedResolverUser();
        $controllerSession = $this->createStartedSession();
        $controller = $this->createController(
            $this->createSuccessfulProviderResponses(
                $idToken,
                '{"sub":"subject-123","preferred_username":"john","email":"john@example.com","name":"John Doe"}',
            ),
            $oidcSession,
            static fn(): CurrentUser => $currentUser,
            static fn(): User => $resolverUser,
        );
        $controller->setContainer($this->createControllerContainer($controllerSession, $currentUser));

        $response = $controller->callback(new Request([
            'code' => 'test-code',
            'state' => 'state-123',
        ]));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(
            $this->configuration->getDefaultUrl() . 'token?user-id=55',
            $response->headers->get('Location'),
        );
        // The 2FA step is bound to the account the OpenID Connect subject resolved to.
        $this->assertSame(55, $controllerSession->get('2fa_pending_user_id'));
        // Single sign-on has no remember-me option.
        $this->assertFalse($controllerSession->get('2fa_pending_remember_me'));
    }

    public function testCallbackRefusesLoginWhenSecondFactorIsLockedOut(): void
    {
        $idToken = $this->signToken([
            'iss' => 'https://auth.example.test',
            'sub' => 'subject-123',
            'aud' => ['phpmyfaq'],
            'azp' => 'phpmyfaq',
            'nonce' => 'nonce-456',
            'iat' => time(),
            'exp' => time() + 300,
        ]);

        $oidcSession = new OidcSession($this->createStartedSession(), 'oidc');
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('getUserId')->willReturn(55);
        $currentUser->expects($this->once())->method('getUserByLogin')->with('linked-user')->willReturn(true);
        $currentUser
            ->expects($this->exactly(3))
            ->method('getUserData')
            ->willReturnMap([['oidc_sub', 'subject-123'], ['oidc_iss', self::ISSUER], ['twofactor_enabled', 1]]);
        $currentUser->expects($this->once())->method('isTwoFactorLockedOut')->willReturn(true);
        $currentUser->expects($this->never())->method('setLoggedIn');
        $currentUser->expects($this->never())->method('saveToSession');
        $currentUser->expects($this->never())->method('setSuccess');

        $resolverUser = $this->createLinkedResolverUser();
        $controllerSession = $this->createStartedSession();
        $controller = $this->createController(
            $this->createSuccessfulProviderResponses(
                $idToken,
                '{"sub":"subject-123","preferred_username":"john","email":"john@example.com","name":"John Doe"}',
            ),
            $oidcSession,
            static fn(): CurrentUser => $currentUser,
            static fn(): User => $resolverUser,
        );
        $controller->setContainer($this->createControllerContainer($controllerSession, $currentUser));

        $response = $controller->callback(new Request([
            'code' => 'test-code',
            'state' => 'state-123',
        ]));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($this->configuration->getDefaultUrl(), $response->headers->get('Location'));
        $this->assertNull($controllerSession->get('2fa_pending_user_id'));
    }

    public function testCallbackReturnsFailureWhenStoredOidcSubjectDoesNotMatch(): void
    {
        $idToken = $this->signToken([
            'iss' => 'https://auth.example.test',
            'sub' => 'subject-123',
            'aud' => ['phpmyfaq'],
            'azp' => 'phpmyfaq',
            'nonce' => 'nonce-456',
            'iat' => time(),
            'exp' => time() + 300,
        ]);

        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session, 'oidc');
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->expects($this->once())->method('getUserByLogin')->with('john')->willReturn(true);
        $currentUser
            ->expects($this->exactly(2))
            ->method('getUserData')
            ->willReturnMap([['oidc_sub', 'different-subject'], ['oidc_iss', self::ISSUER]]);
        $this->expectNoLogin($currentUser);

        $authUser = $this->createMock(User::class);
        $authUser->expects($this->once())->method('getUserIdByOidcIdentity')->with(self::ISSUER, 'subject-123')->willReturn(55);
        $authUser->expects($this->once())->method('getUserById')->with(55)->willReturn(true);
        $authUser->expects($this->once())->method('getLogin')->willReturn('john');
        $authUser->expects($this->once())->method('getUserByLogin')->with('john', false)->willReturn(true);
        $authUser
            ->expects($this->exactly(2))
            ->method('getUserData')
            ->willReturnMap([['oidc_sub', 'subject-123'], ['oidc_iss', self::ISSUER]]);

        $controller = $this->createController(
            $this->createSuccessfulProviderResponses(
                $idToken,
                '{"sub":"subject-123","preferred_username":"john","email":"john@example.com","name":"John Doe"}',
            ),
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
        $this->assertSame('', $oidcSession->getIdToken());
    }

    /**
     * A subject is only unique per issuer: an account linked through another provider's
     * issuer must never be handed to an identity that merely shares its subject.
     */
    public function testCallbackRefusesLoginWhenStoredIssuerDiffers(): void
    {
        $idToken = $this->signToken([
            'iss' => 'https://auth.example.test',
            'sub' => 'subject-123',
            'aud' => ['phpmyfaq'],
            'azp' => 'phpmyfaq',
            'nonce' => 'nonce-456',
            'iat' => time(),
            'exp' => time() + 300,
        ]);

        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session, 'oidc');
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->expects($this->never())->method('getUserByLogin');
        $this->expectNoLogin($currentUser);

        $authUser = $this->createMock(User::class);
        $authUser->expects($this->once())->method('getUserIdByOidcIdentity')->with(self::ISSUER, 'subject-123')->willReturn(55);
        $authUser->expects($this->once())->method('getUserById')->with(55)->willReturn(true);
        $authUser->expects($this->once())->method('getLogin')->willReturn('john');
        $authUser->expects($this->once())->method('getUserByLogin')->with('john', false)->willReturn(true);
        $authUser
            ->expects($this->exactly(2))
            ->method('getUserData')
            ->willReturnMap([['oidc_sub', 'subject-123'], ['oidc_iss', 'https://old-provider.example.test']]);
        $authUser->expects($this->never())->method('createUser');

        $controller = $this->createController(
            $this->createSuccessfulProviderResponses(
                $idToken,
                '{"sub":"subject-123","preferred_username":"john","email":"john@example.com","name":"John Doe"}',
            ),
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
        $this->assertSame('', $oidcSession->getIdToken());
    }

    public function testCallbackRejectsUnlinkedLocalAccountMatchedByPreferredUsername(): void
    {
        $idToken = $this->signToken([
            'iss' => 'https://auth.example.test',
            'sub' => 'attacker-subject',
            'aud' => ['phpmyfaq'],
            'azp' => 'phpmyfaq',
            'nonce' => 'nonce-456',
            'iat' => time(),
            'exp' => time() + 300,
        ]);

        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session, 'oidc');
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->expects($this->never())->method('getUserByLogin');
        $this->expectNoLogin($currentUser);

        $authUser = $this->createMock(User::class);
        $authUser->expects($this->once())->method('getUserIdByOidcIdentity')->with(self::ISSUER, 'attacker-subject')->willReturn(0);
        $authUser->expects($this->never())->method('getUserIdByEmail');
        $authUser->expects($this->once())->method('getUserByLogin')->with('admin', false)->willReturn(true);
        $authUser->expects($this->never())->method('createUser');
        $authUser->expects($this->never())->method('setUserData');

        $controller = $this->createController(
            $this->createSuccessfulProviderResponses(
                $idToken,
                '{"sub":"attacker-subject","preferred_username":"admin","email":"attacker@example.com","name":"Attacker"}',
            ),
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
        $this->assertSame('', $oidcSession->getIdToken());
    }

    public function testCallbackRejectsUnlinkedLocalAccountMatchedByEmail(): void
    {
        $idToken = $this->signToken([
            'iss' => 'https://auth.example.test',
            'sub' => 'attacker-subject',
            'aud' => ['phpmyfaq'],
            'azp' => 'phpmyfaq',
            'nonce' => 'nonce-456',
            'iat' => time(),
            'exp' => time() + 300,
        ]);

        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session, 'oidc');
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->expects($this->never())->method('getUserByLogin');
        $this->expectNoLogin($currentUser);

        $authUser = $this->createMock(User::class);
        $authUser->expects($this->once())->method('getUserIdByOidcIdentity')->with(self::ISSUER, 'attacker-subject')->willReturn(0);
        $authUser->expects($this->once())->method('getUserByLogin')->with('attacker', false)->willReturn(false);
        $authUser->expects($this->once())->method('getUserIdByEmail')->with('admin@example.com')->willReturn(1);
        $authUser->expects($this->never())->method('getUserById');
        $authUser->expects($this->never())->method('createUser');
        $authUser->expects($this->never())->method('setUserData');

        $controller = $this->createController(
            $this->createSuccessfulProviderResponses(
                $idToken,
                '{"sub":"attacker-subject","preferred_username":"attacker","email":"admin@example.com","name":"Attacker"}',
            ),
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
        $this->assertSame('', $oidcSession->getIdToken());
    }

    public function testCallbackReturnsFailureWhenIdTokenSubjectDoesNotMatchUserInfoSubject(): void
    {
        $idToken = $this->signToken([
            'iss' => 'https://auth.example.test',
            'sub' => 'id-token-subject',
            'aud' => ['phpmyfaq'],
            'azp' => 'phpmyfaq',
            'nonce' => 'nonce-456',
            'iat' => time(),
            'exp' => time() + 300,
        ]);

        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session, 'oidc');
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $controller = $this->createController([
            new MockResponse(
                '{"issuer":"https://auth.example.test","authorization_endpoint":"https://auth.example.test/auth","token_endpoint":"https://auth.example.test/token","userinfo_endpoint":"https://auth.example.test/userinfo","jwks_uri":"https://auth.example.test/jwks","end_session_endpoint":"https://auth.example.test/logout"}',
            ),
            new MockResponse('{"access_token":"access","refresh_token":"refresh","id_token":"' . $idToken . '"}'),
            new MockResponse(json_encode(['keys' => [$this->jwk]], JSON_THROW_ON_ERROR)),
            new MockResponse(
                '{"sub":"userinfo-subject","preferred_username":"john","email":"john@example.com","name":"John Doe"}',
            ),
        ], $oidcSession);

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
        $oidcSession = new OidcSession($session, 'oidc');
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $controller = $this->createController([
            new MockResponse(
                '{"issuer":"https://auth.example.test","authorization_endpoint":"https://auth.example.test/auth","token_endpoint":"https://auth.example.test/token","userinfo_endpoint":"https://auth.example.test/userinfo","jwks_uri":"https://auth.example.test/jwks","end_session_endpoint":"https://auth.example.test/logout"}',
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
        $oidcSession = new OidcSession($session, 'oidc');
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $controller = $this->createController([
            new MockResponse(
                '{"issuer":"https://auth.example.test","authorization_endpoint":"https://auth.example.test/auth","token_endpoint":"https://auth.example.test/token","userinfo_endpoint":"https://auth.example.test/userinfo","jwks_uri":"https://auth.example.test/jwks","end_session_endpoint":"https://auth.example.test/logout"}',
            ),
            new MockResponse(
                '{"access_token":"access","refresh_token":"refresh","id_token":"'
                . $this->signToken([
                    'iss' => 'https://auth.example.test',
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
        $oidcSession = new OidcSession($session, 'oidc');
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $controller = $this->createController([
            new MockResponse(
                '{"issuer":"https://auth.example.test","authorization_endpoint":"https://auth.example.test/auth","token_endpoint":"https://auth.example.test/token","userinfo_endpoint":"https://auth.example.test/userinfo","jwks_uri":"https://auth.example.test/jwks","end_session_endpoint":"https://auth.example.test/logout"}',
            ),
            new MockResponse(
                '{"access_token":"access","refresh_token":"refresh","id_token":"'
                . $this->signToken([
                    'iss' => 'https://auth.example.test',
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
        $oidcSession = new OidcSession($session, 'oidc');
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $controller = $this->createController([
            new MockResponse(
                '{"issuer":"https://auth.example.test","authorization_endpoint":"https://auth.example.test/auth","token_endpoint":"https://auth.example.test/token","userinfo_endpoint":"https://auth.example.test/userinfo","jwks_uri":"https://auth.example.test/jwks","end_session_endpoint":"https://auth.example.test/logout"}',
            ),
            new MockResponse(
                '{"access_token":"access","refresh_token":"refresh","id_token":"'
                . $this->signToken([
                    'iss' => 'https://auth.example.test',
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
        $oidcSession = new OidcSession($session, 'oidc');
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $controller = $this->createController([
            new MockResponse(
                '{"issuer":"https://auth.example.test","authorization_endpoint":"https://auth.example.test/auth","token_endpoint":"https://auth.example.test/token","userinfo_endpoint":"https://auth.example.test/userinfo","jwks_uri":"https://auth.example.test/jwks","end_session_endpoint":"https://auth.example.test/logout"}',
            ),
            new MockResponse(
                '{"access_token":"access","refresh_token":"refresh","id_token":"'
                . $this->signToken([
                    'iss' => 'https://auth.example.test',
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
        $oidcSession = new OidcSession($session, 'oidc');
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

    private function expectNoLogin(CurrentUser&MockObject $currentUser): void
    {
        $currentUser->expects($this->never())->method('setUserData');
        $currentUser->expects($this->never())->method('setLoggedIn');
        $currentUser->expects($this->never())->method('setAuthSource');
        $currentUser->expects($this->never())->method('updateSessionId');
        $currentUser->expects($this->never())->method('saveToSession');
        $currentUser->expects($this->never())->method('setTokenData');
        $currentUser->expects($this->never())->method('setSuccess');
    }

    /**
     * @return array<MockResponse>
     */
    private function createSuccessfulProviderResponses(string $idToken, string $userInfo): array
    {
        return [
            new MockResponse(
                '{"issuer":"https://auth.example.test","authorization_endpoint":"https://auth.example.test/auth","token_endpoint":"https://auth.example.test/token","userinfo_endpoint":"https://auth.example.test/userinfo","jwks_uri":"https://auth.example.test/jwks","end_session_endpoint":"https://auth.example.test/logout"}',
            ),
            new MockResponse('{"access_token":"access","refresh_token":"refresh","id_token":"' . $idToken . '"}'),
            new MockResponse(json_encode(['keys' => [$this->jwk]], JSON_THROW_ON_ERROR)),
            new MockResponse($userInfo),
        ];
    }

    private function createStartedSession(): Session
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        return $session;
    }

    /**
     * Resolves login "linked-user" from the stored OpenID Connect subject "subject-123".
     */
    private function createLinkedResolverUser(): User&MockObject
    {
        $resolverUser = $this->createMock(User::class);
        $resolverUser->expects($this->once())->method('getUserIdByOidcIdentity')->with(self::ISSUER, 'subject-123')->willReturn(55);
        $resolverUser->expects($this->once())->method('getUserById')->with(55)->willReturn(true);
        $resolverUser->expects($this->once())->method('getLogin')->willReturn('linked-user');
        // AuthOidc::isValidLogin()/checkCredentials() re-resolve the account and its subject.
        $resolverUser
            ->expects($this->once())
            ->method('getUserByLogin')
            ->with('linked-user', false)
            ->willReturn(true);
        $resolverUser
            ->expects($this->exactly(2))
            ->method('getUserData')
            ->willReturnMap([['oidc_sub', 'subject-123'], ['oidc_iss', self::ISSUER]]);

        return $resolverUser;
    }

    private function createControllerContainer(Session $session, CurrentUser $currentUser): ContainerInterface
    {
        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(fn(string $id): mixed => match ($id) {
                'phpmyfaq.configuration' => $this->configuration,
                'phpmyfaq.user.current_user' => $currentUser,
                'session' => $session,
                'phpmyfaq.system' => new System(),
                'phpmyfaq.seo' => new Seo($this->configuration),
                default => null,
            });

        return $container;
    }

    /**
     * @param list<MockResponse> $responses
     */
    private function createController(
        array $responses = [],
        ?OidcSession $oidcSession = null,
        ?\Closure $currentUserFactory = null,
        ?\Closure $userFactory = null,
    ): OidcAuthenticationController {
        $httpClient = new MockHttpClient($responses);
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $controller = new OidcAuthenticationController(
            new GenericOidcProviderConfigFactory($this->configuration),
            new OidcDiscoveryService($httpClient),
            new OidcPkceGenerator(),
            $oidcSession ?? new OidcSession($session, 'oidc'),
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

    /**
     * The configuration instance is private to this test, so the override needs no restore.
     */
    private function disableProvider(): void
    {
        $property = new \ReflectionProperty(Configuration::class, 'config');
        /** @var array<string, mixed> $config */
        $config = $property->getValue($this->configuration);
        $config['oidc.enable'] = 'false';
        $property->setValue($this->configuration, $config);
    }

    public function testAuthorizeRedirectsHomeWhenTheProviderIsDisabled(): void
    {
        $this->disableProvider();
        $controller = $this->createController([
            new MockResponse('', ['http_code' => 500]),
        ]);

        $response = $controller->authorize();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($this->configuration->getDefaultUrl(), $response->headers->get('Location'));
    }

    public function testAuthorizeRedirectsHomeWhenDiscoveryFails(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session, 'oidc');
        $controller = $this->createController([new MockResponse('unavailable', ['http_code' => 503])], $oidcSession);

        $response = $controller->authorize();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($this->configuration->getDefaultUrl(), $response->headers->get('Location'));
        $this->assertNull($session->get('pmf-oidc-oidc-state'), 'No authorization state is stored when discovery fails.');
    }

    public function testLogoutClearsTheLocalSessionWhenDiscoveryFails(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session, 'oidc');
        $oidcSession->setIdToken('session-id-token');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->expects($this->atLeastOnce())->method('deleteFromSession');

        $controller = $this->createController(
            [new MockResponse('unavailable', ['http_code' => 503])],
            $oidcSession,
            static fn(): CurrentUser => $currentUser,
        );

        $response = $controller->logout();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($this->configuration->getDefaultUrl(), $response->headers->get('Location'));
        $this->assertSame('', $oidcSession->getIdToken());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unusableJwtProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'not json' => ['not json'];
        yield 'scalar json' => ['"just-a-string"'];
        yield 'no id token' => ['{"userinfo":{"sub":"123"}}'];
    }

    #[DataProvider('unusableJwtProvider')]
    public function testLogoutWithoutAUsableIdTokenStillEndsTheProviderSession(string $jwt): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session, 'oidc');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->expects($this->once())->method('getUserData')->with('jwt')->willReturn($jwt);
        $currentUser->expects($this->once())->method('deleteFromSession');

        $controller = $this->createController(
            [
                new MockResponse(
                    '{"issuer":"https://auth.example.test","authorization_endpoint":"https://auth.example.test/auth","token_endpoint":"https://auth.example.test/token","userinfo_endpoint":"https://auth.example.test/userinfo","jwks_uri":"https://auth.example.test/jwks","end_session_endpoint":"https://auth.example.test/logout"}',
                ),
            ],
            $oidcSession,
            static fn(): CurrentUser => $currentUser,
        );

        $response = $controller->logout();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $location = (string) $response->headers->get('Location');
        $this->assertStringStartsWith('https://auth.example.test/logout?client_id=phpmyfaq', $location);
        $this->assertStringNotContainsString('id_token_hint', $location);
    }

    public function testCallbackRefusesToProvisionAnIdentityWithoutUsernameAndEmail(): void
    {
        $idToken = $this->signToken([
            'iss' => 'https://auth.example.test',
            'sub' => '123',
            'aud' => ['phpmyfaq'],
            'azp' => 'phpmyfaq',
            'nonce' => 'nonce-456',
            'iat' => time(),
            'exp' => time() + 300,
        ]);

        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session, 'oidc');
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $currentUser = $this->createMock(CurrentUser::class);
        $this->expectNoLogin($currentUser);

        $authUser = $this->createMock(User::class);
        $authUser->expects($this->once())->method('getUserIdByOidcIdentity')->with(self::ISSUER, '123')->willReturn(0);
        $authUser->expects($this->never())->method('createUser');

        $controller = $this->createController(
            [
                new MockResponse(
                    '{"issuer":"https://auth.example.test","authorization_endpoint":"https://auth.example.test/auth","token_endpoint":"https://auth.example.test/token","userinfo_endpoint":"https://auth.example.test/userinfo","jwks_uri":"https://auth.example.test/jwks","end_session_endpoint":"https://auth.example.test/logout"}',
                ),
                new MockResponse('{"access_token":"access","refresh_token":"refresh","id_token":"' . $idToken . '"}'),
                new MockResponse(json_encode(['keys' => [$this->jwk]], JSON_THROW_ON_ERROR)),
                new MockResponse('{"sub":"123","name":"Nameless Subject"}'),
            ],
            $oidcSession,
            static fn(): CurrentUser => $currentUser,
            static fn(): User => $authUser,
        );

        $response = $controller->callback(new Request(['code' => 'test-code', 'state' => 'state-123']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($this->configuration->getDefaultUrl(), $response->headers->get('Location'));
        $this->assertSame('', $oidcSession->getAuthorizationState()['state']);
    }

    public function testLogoutFallsBackToDefaultUrlWithoutEndSessionEndpoint(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session, 'oidc');
        $oidcSession->setIdToken('session-id-token');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->expects($this->once())->method('deleteFromSession');

        $controller = $this->createController(
            [
                new MockResponse(
                    '{"issuer":"https://auth.example.test","authorization_endpoint":"https://auth.example.test/auth","token_endpoint":"https://auth.example.test/token","userinfo_endpoint":"https://auth.example.test/userinfo","jwks_uri":"https://auth.example.test/jwks"}',
                ),
            ],
            $oidcSession,
            static fn(): CurrentUser => $currentUser,
        );

        $response = $controller->logout();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($this->configuration->getDefaultUrl(), $response->headers->get('Location'));
        $this->assertSame('', $oidcSession->getIdToken());
    }

    public function testCallbackRedirectsHomeWhenTheProviderIsDisabled(): void
    {
        $this->disableProvider();

        $response = $this->createController()->callback(new Request(['code' => 'x', 'state' => 'y']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($this->configuration->getDefaultUrl(), $response->headers->get('Location'));
    }

    /**
     * The linked account is resolved by its stored subject, but the credential check re-reads
     * the subject and finds a different one. A blank login is masked as "<empty>" in the log.
     */
    public function testCallbackRefusesLoginWhenTheCredentialCheckFindsAnUnlinkedAccount(): void
    {
        $idToken = $this->signToken([
            'iss' => 'https://auth.example.test',
            'sub' => 'subject-123',
            'aud' => ['phpmyfaq'],
            'azp' => 'phpmyfaq',
            'nonce' => 'nonce-456',
            'iat' => time(),
            'exp' => time() + 300,
        ]);

        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session, 'oidc');
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->expects($this->never())->method('getUserByLogin');
        $this->expectNoLogin($currentUser);

        $authUser = $this->createMock(User::class);
        $authUser->expects($this->once())->method('getUserIdByOidcIdentity')->with(self::ISSUER, 'subject-123')->willReturn(55);
        $authUser->expects($this->once())->method('getUserById')->with(55)->willReturn(true);
        $authUser->expects($this->once())->method('getLogin')->willReturn(' ');
        $authUser->expects($this->once())->method('getUserByLogin')->with(' ', false)->willReturn(true);
        $authUser->expects($this->once())->method('getUserData')->with('oidc_sub')->willReturn('different-subject');
        $authUser->expects($this->never())->method('createUser');

        $controller = $this->createController(
            $this->createSuccessfulProviderResponses(
                $idToken,
                '{"sub":"subject-123","preferred_username":"john","email":"john@example.com","name":"John Doe"}',
            ),
            $oidcSession,
            static fn(): CurrentUser => $currentUser,
            static fn(): User => $authUser,
        );

        $response = $controller->callback(new Request(['code' => 'test-code', 'state' => 'state-123']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($this->configuration->getDefaultUrl(), $response->headers->get('Location'));
        $this->assertSame('', $oidcSession->getAuthorizationState()['state']);
        $this->assertSame('', $oidcSession->getIdToken());
    }

    public function testCallbackRefusesLoginWhenTheProvisionedUserCannotBeLoaded(): void
    {
        $idToken = $this->signToken([
            'iss' => 'https://auth.example.test',
            'sub' => '123',
            'aud' => ['phpmyfaq'],
            'azp' => 'phpmyfaq',
            'nonce' => 'nonce-456',
            'iat' => time(),
            'exp' => time() + 300,
        ]);

        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session, 'oidc');
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->expects($this->once())->method('getUserByLogin')->with('john')->willReturn(false);
        $this->expectNoLogin($currentUser);

        $authUser = $this->createMock(User::class);
        $authUser->expects($this->once())->method('getUserIdByOidcIdentity')->with(self::ISSUER, '123')->willReturn(0);
        $authUser->expects($this->exactly(2))->method('getUserByLogin')->with('john', false)->willReturn(false);
        $authUser->expects($this->once())->method('getUserIdByEmail')->with('john@example.com')->willReturn(0);
        $authUser->expects($this->once())->method('createUser')->with('john', '', '')->willReturn(true);
        $authUser->expects($this->once())->method('setUserData')->willReturn(true);

        $controller = $this->createController(
            $this->createSuccessfulProviderResponses(
                $idToken,
                '{"sub":"123","preferred_username":"john","email":"john@example.com","name":"John Doe"}',
            ),
            $oidcSession,
            static fn(): CurrentUser => $currentUser,
            static fn(): User => $authUser,
        );

        $response = $controller->callback(new Request(['code' => 'test-code', 'state' => 'state-123']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($this->configuration->getDefaultUrl(), $response->headers->get('Location'));
        $this->assertSame('', $oidcSession->getAuthorizationState()['state']);
        $this->assertSame('', $oidcSession->getIdToken());
    }

    public function testLogoutUsesTheContainerUserWhenNoFactoryIsInjected(): void
    {
        $this->disableProvider();

        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session, 'oidc');
        $oidcSession->setIdToken('session-id-token');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->expects($this->once())->method('deleteFromSession');

        $controller = $this->createController([], $oidcSession);
        $controller->setContainer($this->createControllerContainer($session, $currentUser));

        $response = $controller->logout();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($this->configuration->getDefaultUrl(), $response->headers->get('Location'));
        $this->assertSame('', $oidcSession->getIdToken());
    }

    /**
     * Without a user factory the controller looks the subject up in the real user table of the
     * (private) test database; the unknown identity carries no login and is not provisioned.
     */
    public function testCallbackLooksUpTheSubjectInTheDatabaseWhenNoUserFactoryIsInjected(): void
    {
        $idToken = $this->signToken([
            'iss' => 'https://auth.example.test',
            'sub' => 'unknown-subject',
            'aud' => ['phpmyfaq'],
            'azp' => 'phpmyfaq',
            'nonce' => 'nonce-456',
            'iat' => time(),
            'exp' => time() + 300,
        ]);

        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $oidcSession = new OidcSession($session, 'oidc');
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $currentUser = $this->createMock(CurrentUser::class);
        $this->expectNoLogin($currentUser);

        $controller = $this->createController(
            $this->createSuccessfulProviderResponses($idToken, '{"sub":"unknown-subject","name":"Nameless Subject"}'),
            $oidcSession,
            static fn(): CurrentUser => $currentUser,
        );

        $response = $controller->callback(new Request(['code' => 'test-code', 'state' => 'state-123']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($this->configuration->getDefaultUrl(), $response->headers->get('Location'));
        $this->assertSame('', $oidcSession->getAuthorizationState()['state']);
    }
}
