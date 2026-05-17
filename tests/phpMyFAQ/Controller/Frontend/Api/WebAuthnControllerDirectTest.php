<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Auth\AuthWebAuthn;
use phpMyFAQ\Auth\WebAuthn\WebAuthnUser;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\AuthenticationSourceType;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(WebAuthnController::class)]
#[UsesNamespace('phpMyFAQ')]
final class WebAuthnControllerDirectTest extends ApiControllerTestCase
{
    public function testPrepareReturnsForbiddenForUnknownUserWhenRegistrationIsDisabled(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'security.enableWebAuthnSupport' => '1',
            'security.enableRegistration' => '0',
        ]);

        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getUserByLogin')->with('new@example.com', false)->willReturn(false);

        $controller = new WebAuthnController($this->createStub(AuthWebAuthn::class), $user);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $request = Request::create('/api/webauthn/prepare', 'POST', content: json_encode([
            'username' => 'new@example.com',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->prepare($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    public function testPrepareReturnsChallengeAfterCreatingUnknownUser(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'security.enableWebAuthnSupport' => '1',
            'security.enableRegistration' => '1',
        ]);

        $session = $this->createSession();
        $csrfToken = Token::getInstance($session)->getTokenString('webauthn');
        $_COOKIE[sprintf('%s-%s', Token::PMF_SESSION_NAME, substr(md5('webauthn'), 0, 10))] = $csrfToken;

        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getUserByLogin')->with('new@example.com', false)->willReturn(false);
        $user->expects($this->once())->method('createUser')->with('new@example.com')->willReturn(true);
        $user->expects($this->once())->method('setStatus')->with('active')->willReturn(true);
        $user
            ->expects($this->once())
            ->method('setAuthSource')
            ->with(AuthenticationSourceType::AUTH_WEB_AUTHN->value)
            ->willReturn(true);
        $user
            ->expects($this->once())
            ->method('setUserData')
            ->with([
                'display_name' => 'new@example.com',
                'email' => 'new@example.com',
            ])
            ->willReturn(true);
        $user->expects($this->exactly(2))->method('getUserId')->willReturn(42);

        $authWebAuthn = $this->createMock(AuthWebAuthn::class);
        $authWebAuthn
            ->expects($this->once())
            ->method('storeUserInSession')
            ->with($this->callback(static function (WebAuthnUser $webAuthnUser): bool {
                return (
                    $webAuthnUser->getName() === 'new@example.com'
                    && $webAuthnUser->getId() === '42'
                    && $webAuthnUser->getWebAuthnKeys() === ''
                );
            }));
        $authWebAuthn
            ->expects($this->once())
            ->method('prepareChallengeForRegistration')
            ->with('new@example.com', '42')
            ->willReturn(['publicKey' => ['challenge' => 'abc'], 'b64challenge' => 'def']);

        $controller = new WebAuthnController($authWebAuthn, $user);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $request = Request::create('/api/webauthn/prepare', 'POST', content: json_encode([
            'username' => 'new@example.com',
            'pmf-csrf-token' => $csrfToken,
            'captcha' => 'ignored-for-logged-in-user',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->prepare($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('def', $payload['challenge']['b64challenge']);
    }

    public function testPrepareLoginReturnsChallengeForExistingUser(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['security.enableWebAuthnSupport' => '1']);

        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getUserByLogin')->with('alice')->willReturn(true);
        $user->expects($this->once())->method('getWebAuthnKeys')->willReturn('stored-keys');

        $authWebAuthn = $this->createMock(AuthWebAuthn::class);
        $authWebAuthn
            ->expects($this->once())
            ->method('prepareForLogin')
            ->with('stored-keys')
            ->willReturn((object) ['challenge' => 'challenge-data']);

        $controller = new WebAuthnController($authWebAuthn, $user);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $request = Request::create('/api/webauthn/prepare-login', 'POST', content: json_encode([
            'username' => 'alice',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->prepareLogin($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('challenge-data', $payload['challenge']);
    }

    public function testPrepareLoginReturnsBadRequestForUnknownUser(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['security.enableWebAuthnSupport' => '1']);

        $user = $this->createMock(User::class);
        $user
            ->expects($this->once())
            ->method('getUserByLogin')
            ->with('alice')
            ->willThrowException(new Exception('not found'));
        $user->expects($this->never())->method('getWebAuthnKeys');

        $controller = new WebAuthnController($this->createStub(AuthWebAuthn::class), $user);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $request = Request::create('/api/webauthn/prepare-login', 'POST', content: json_encode([
            'username' => 'alice',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->prepareLogin($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('ad_auth_fail'), $payload['error']);
    }

    public function testRegisterReturnsSuccessWhenKeysAreStored(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['security.enableWebAuthnSupport' => '1']);

        $webAuthnUser = new WebAuthnUser()
            ->setName('alice')
            ->setId('1')
            ->setWebAuthnKeys('existing-keys');

        $authWebAuthn = $this->createMock(AuthWebAuthn::class);
        $authWebAuthn->expects($this->once())->method('getUserFromSession')->willReturn($webAuthnUser);
        $authWebAuthn
            ->expects($this->once())
            ->method('register')
            ->with('register-payload', 'existing-keys')
            ->willReturn('new-keys');

        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getUserByLogin')->with('alice')->willReturn(true);
        $user->expects($this->once())->method('setWebAuthnKeys')->with('new-keys')->willReturn(true);

        $controller = new WebAuthnController($authWebAuthn, $user);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $request = Request::create('/api/webauthn/register', 'POST', content: json_encode([
            'register' => 'register-payload',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->register($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('ok', $payload['success']);
        self::assertSame(Translation::get('msgPasskeyRegistrationSuccess'), $payload['message']);
    }

    public function testRegisterReturnsBadRequestWhenKeysCannotBeStored(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['security.enableWebAuthnSupport' => '1']);

        $webAuthnUser = new WebAuthnUser()
            ->setName('alice')
            ->setId('1')
            ->setWebAuthnKeys('existing-keys');

        $authWebAuthn = $this->createMock(AuthWebAuthn::class);
        $authWebAuthn->expects($this->once())->method('getUserFromSession')->willReturn($webAuthnUser);
        $authWebAuthn
            ->expects($this->once())
            ->method('register')
            ->with('register-payload', 'existing-keys')
            ->willReturn('new-keys');

        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getUserByLogin')->with('alice')->willReturn(true);
        $user->expects($this->once())->method('setWebAuthnKeys')->with('new-keys')->willReturn(false);

        $controller = new WebAuthnController($authWebAuthn, $user);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $request = Request::create('/api/webauthn/register', 'POST', content: json_encode([
            'register' => 'register-payload',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->register($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('Cannot set WebAuthn keys', $payload['error']);
    }

    public function testLoginReturnsUnauthorizedWhenAuthenticationFails(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['security.enableWebAuthnSupport' => '1']);

        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getUserByLogin')->with('alice')->willReturn(true);
        $user->expects($this->once())->method('getWebAuthnKeys')->willReturn('stored-keys');

        $authWebAuthn = $this->createMock(AuthWebAuthn::class);
        $authWebAuthn
            ->expects($this->once())
            ->method('authenticate')
            ->with((object) ['assertion' => 'payload'], 'stored-keys')
            ->willReturn(false);

        $loginUser = $this->createMock(CurrentUser::class);
        $loginUser->expects($this->never())->method('getUserByLogin');

        $controller = new WebAuthnController($authWebAuthn, $user, $loginUser);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $request = Request::create('/api/webauthn/login', 'POST', content: json_encode([
            'username' => 'alice',
            'login' => ['assertion' => 'payload'],
        ], JSON_THROW_ON_ERROR));

        $response = $controller->login($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('ad_auth_fail'), $payload['error']);
    }

    public function testLoginReturnsUnauthorizedWhenResolvedUserIsBlocked(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['security.enableWebAuthnSupport' => '1']);

        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getUserByLogin')->with('alice')->willReturn(true);
        $user->expects($this->once())->method('getWebAuthnKeys')->willReturn('stored-keys');

        $authWebAuthn = $this->createMock(AuthWebAuthn::class);
        $authWebAuthn
            ->expects($this->once())
            ->method('authenticate')
            ->with((object) ['assertion' => 'payload'], 'stored-keys')
            ->willReturn(true);

        $loginUser = $this->createMock(CurrentUser::class);
        $loginUser->expects($this->once())->method('getUserByLogin')->with('alice')->willReturn(true);
        $loginUser->expects($this->once())->method('isBlocked')->willReturn(true);
        $loginUser->expects($this->never())->method('setLoggedIn');

        $controller = new WebAuthnController($authWebAuthn, $user, $loginUser);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $request = Request::create('/api/webauthn/login', 'POST', content: json_encode([
            'username' => 'alice',
            'login' => ['assertion' => 'payload'],
        ], JSON_THROW_ON_ERROR));

        $response = $controller->login($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('ad_auth_fail'), $payload['error']);
    }

    public function testLoginReturnsRedirectWhenAuthenticationSucceeds(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'security.enableWebAuthnSupport' => '1',
            'main.referenceURL' => 'https://localhost/',
        ]);

        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getUserByLogin')->with('alice')->willReturn(true);
        $user->expects($this->once())->method('getWebAuthnKeys')->willReturn('stored-keys');

        $authWebAuthn = $this->createMock(AuthWebAuthn::class);
        $authWebAuthn
            ->expects($this->once())
            ->method('authenticate')
            ->with((object) ['assertion' => 'payload'], 'stored-keys')
            ->willReturn(true);

        $loginUser = $this->createMock(CurrentUser::class);
        $loginUser->expects($this->once())->method('getUserByLogin')->with('alice')->willReturn(true);
        $loginUser->expects($this->once())->method('isBlocked')->willReturn(false);
        $loginUser->expects($this->once())->method('setLoggedIn')->with(true);
        $loginUser->expects($this->once())->method('setSuccess')->with(true)->willReturn(true);
        $loginUser->expects($this->once())->method('updateSessionId')->with(true)->willReturn(true);
        $loginUser->expects($this->once())->method('saveToSession');

        $controller = new WebAuthnController($authWebAuthn, $user, $loginUser);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $request = Request::create('/api/webauthn/login', 'POST', content: json_encode([
            'username' => 'alice',
            'login' => ['assertion' => 'payload'],
        ], JSON_THROW_ON_ERROR));

        $response = $controller->login($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('ok', $payload['success']);
        self::assertSame('https://localhost/', $payload['redirect']);
    }

    /**
     * Regression test: an unauthenticated attacker must not be able to start a passkey
     * registration for an account that already exists (pre-auth account takeover).
     */
    public function testPrepareRejectsExistingUserForUnauthenticatedRequest(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'security.enableWebAuthnSupport' => '1',
            'security.enableRegistration' => '1',
        ]);

        $session = $this->createSession();
        $csrfToken = Token::getInstance($session)->getTokenString('webauthn');
        $_COOKIE[sprintf('%s-%s', Token::PMF_SESSION_NAME, substr(md5('webauthn'), 0, 10))] = $csrfToken;

        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getUserByLogin')->with('admin', false)->willReturn(true);
        $user->method('getUserId')->willReturn(1);
        $user->expects($this->never())->method('createUser');

        $authWebAuthn = $this->createMock(AuthWebAuthn::class);
        $authWebAuthn->expects($this->never())->method('storeUserInSession');

        $unauthenticatedUser = $this->createStub(CurrentUser::class);
        $unauthenticatedUser->method('isLoggedIn')->willReturn(false);
        $unauthenticatedUser->method('getUserId')->willReturn(-1);

        $controller = new WebAuthnController($authWebAuthn, $user);
        $this->injectControllerState($controller, $unauthenticatedUser, $session);

        $request = Request::create('/api/webauthn/prepare', 'POST', content: json_encode([
            'username' => 'admin',
            'pmf-csrf-token' => $csrfToken,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->prepare($request);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * Regression test: a logged-in user calling prepare for a different existing account
     * must be rejected.
     */
    public function testPrepareRejectsExistingUserForNonOwner(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'security.enableWebAuthnSupport' => '1',
            'security.enableRegistration' => '1',
        ]);

        $session = $this->createSession();
        $csrfToken = Token::getInstance($session)->getTokenString('webauthn');
        $_COOKIE[sprintf('%s-%s', Token::PMF_SESSION_NAME, substr(md5('webauthn'), 0, 10))] = $csrfToken;

        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getUserByLogin')->with('admin', false)->willReturn(true);
        $user->method('getUserId')->willReturn(1);

        $authWebAuthn = $this->createMock(AuthWebAuthn::class);
        $authWebAuthn->expects($this->never())->method('storeUserInSession');

        $controller = new WebAuthnController($authWebAuthn, $user);
        // Authenticated as user id 99, attempting to act on account id 1.
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(99), $session);

        $request = Request::create('/api/webauthn/prepare', 'POST', content: json_encode([
            'username' => 'admin',
            'pmf-csrf-token' => $csrfToken,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->prepare($request);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * The authenticated owner of an existing account may (re-)register a passkey.
     */
    public function testPrepareAllowsExistingUserForAuthenticatedOwner(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'security.enableWebAuthnSupport' => '1',
            'security.enableRegistration' => '1',
        ]);

        $session = $this->createSession();
        $csrfToken = Token::getInstance($session)->getTokenString('webauthn');
        $_COOKIE[sprintf('%s-%s', Token::PMF_SESSION_NAME, substr(md5('webauthn'), 0, 10))] = $csrfToken;

        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getUserByLogin')->with('owner@example.com', false)->willReturn(true);
        $user->method('getUserId')->willReturn(42);
        $user->expects($this->never())->method('createUser');

        $authWebAuthn = $this->createMock(AuthWebAuthn::class);
        $authWebAuthn->expects($this->once())->method('storeUserInSession');
        $authWebAuthn
            ->expects($this->once())
            ->method('prepareChallengeForRegistration')
            ->with('owner@example.com', '42')
            ->willReturn(['publicKey' => ['challenge' => 'abc'], 'b64challenge' => 'def']);

        $controller = new WebAuthnController($authWebAuthn, $user);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(42), $session);

        $request = Request::create('/api/webauthn/prepare', 'POST', content: json_encode([
            'username' => 'owner@example.com',
            'pmf-csrf-token' => $csrfToken,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->prepare($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('def', $payload['challenge']['b64challenge']);
    }

    /**
     * Regression test: a request for an existing account without a valid CSRF token is rejected.
     */
    public function testPrepareRejectsExistingUserWithoutCsrfToken(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'security.enableWebAuthnSupport' => '1',
            'security.enableRegistration' => '1',
        ]);

        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getUserByLogin')->with('admin', false)->willReturn(true);

        $authWebAuthn = $this->createMock(AuthWebAuthn::class);
        $authWebAuthn->expects($this->never())->method('storeUserInSession');

        $controller = new WebAuthnController($authWebAuthn, $user);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $request = Request::create('/api/webauthn/prepare', 'POST', content: json_encode([
            'username' => 'admin',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->prepare($request);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }
}
