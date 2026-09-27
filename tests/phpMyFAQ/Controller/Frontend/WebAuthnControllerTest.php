<?php

/**
 * Frontend WebAuthnController Test — security guards.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Controller\Frontend
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-02-22
 */

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Auth\AuthWebAuthn;
use phpMyFAQ\Configuration;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
class WebAuthnControllerTest extends TestCase
{
    private WebAuthnController $controller;
    private Configuration $configurationMock;
    private ContainerBuilder $containerMock;

    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->configurationMock = $this->createMock(Configuration::class);
        $this->containerMock = $this->createMock(ContainerBuilder::class);

        // Create the controller without invoking the constructor (which requires DB, etc.)
        $reflection = new ReflectionClass(WebAuthnController::class);
        $this->controller = $reflection->newInstanceWithoutConstructor();

        // Inject mocked dependencies via reflection
        $configProp = $reflection->getParentClass()->getProperty('configuration');
        $configProp->setValue($this->controller, $this->configurationMock);

        $containerProp = $reflection->getParentClass()->getProperty('container');
        $containerProp->setValue($this->controller, $this->containerMock);
    }

    public function testPrepareReturns403WhenWebAuthnDisabled(): void
    {
        $this->configurationMock
            ->method('get')
            ->willReturnCallback(fn(string $item) => match ($item) {
                'security.enableWebAuthnSupport' => false,
                default => null,
            });

        $request = Request::create('/api/webauthn/prepare', 'POST', [], [], [], [], json_encode([
            'username' => 'attacker',
        ]));

        $response = $this->controller->prepare($request);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertStringContainsString('WebAuthn support is disabled', $response->getContent());
    }

    public function testPrepareReturns403WhenRegistrationDisabled(): void
    {
        $this->configurationMock
            ->method('get')
            ->willReturnCallback(fn(string $item) => match ($item) {
                'security.enableWebAuthnSupport' => true,
                'security.enableRegistration' => false,
                default => null,
            });

        $request = Request::create('/api/webauthn/prepare', 'POST', [], [], [], [], json_encode([
            'username' => 'attacker',
        ]));

        $response = $this->controller->prepare($request);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertStringContainsString('User registration is disabled', $response->getContent());
    }

    public function testPrepareReturns401WithMissingCsrfToken(): void
    {
        $this->configurationMock
            ->method('get')
            ->willReturnCallback(fn(string $item) => match ($item) {
                'security.enableWebAuthnSupport' => true,
                'security.enableRegistration' => true,
                default => null,
            });

        $sessionMock = $this->createMock(\Symfony\Component\HttpFoundation\Session\SessionInterface::class);
        $this->containerMock->method('get')->with('session')->willReturn($sessionMock);

        $request = Request::create('/api/webauthn/prepare', 'POST', [], [], [], [], json_encode([
            'username' => 'attacker',
        ]));

        $response = $this->controller->prepare($request);

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testRegisterReturns403WhenWebAuthnDisabled(): void
    {
        $this->configurationMock
            ->method('get')
            ->willReturnCallback(fn(string $item) => match ($item) {
                'security.enableWebAuthnSupport' => false,
                default => null,
            });

        $request = Request::create('/api/webauthn/register', 'POST', [], [], [], [], json_encode([
            'register' => 'some-data',
        ]));

        $response = $this->controller->register($request);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertStringContainsString('WebAuthn support is disabled', $response->getContent());
    }

    public function testRegisterReturns401WithMissingCsrfToken(): void
    {
        $this->configurationMock
            ->method('get')
            ->willReturnCallback(fn(string $item) => match ($item) {
                'security.enableWebAuthnSupport' => true,
                default => null,
            });

        $sessionMock = $this->createMock(\Symfony\Component\HttpFoundation\Session\SessionInterface::class);
        $this->containerMock->method('get')->with('session')->willReturn($sessionMock);

        $request = Request::create('/api/webauthn/register', 'POST', [], [], [], [], json_encode([
            'register' => 'some-data',
        ]));

        $response = $this->controller->register($request);

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testPrepareLoginReturns403WhenWebAuthnDisabled(): void
    {
        $this->configurationMock
            ->method('get')
            ->willReturnCallback(fn(string $item) => match ($item) {
                'security.enableWebAuthnSupport' => false,
                default => null,
            });

        $request = Request::create('/api/webauthn/prepare-login', 'POST', [], [], [], [], json_encode([
            'username' => 'someone',
        ]));

        $response = $this->controller->prepareLogin($request);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertStringContainsString('WebAuthn support is disabled', $response->getContent());
    }

    public function testLoginReturns403WhenWebAuthnDisabled(): void
    {
        $this->configurationMock
            ->method('get')
            ->willReturnCallback(fn(string $item) => match ($item) {
                'security.enableWebAuthnSupport' => false,
                default => null,
            });

        $request = Request::create('/api/webauthn/login', 'POST', [], [], [], [], json_encode([
            'username' => 'someone',
            'login' => 'data',
        ]));

        $response = $this->controller->login($request);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertStringContainsString('WebAuthn support is disabled', $response->getContent());
    }

    public function testPrepareRejectsExistingAccountForAnonymousCaller(): void
    {
        // Core takeover guard: an unauthenticated caller must not be able to stage an existing
        // account (e.g. admin) and attach a passkey to it, even with a valid CSRF token.
        $this->enableWebAuthnAndRegistration();
        $this->primeCsrfToken('webauthn-prepare', 'valid-token');

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByLogin')->with('admin', false)->willReturn(true);
        $userMock->method('getUserId')->willReturn(1);
        $this->injectControllerProperty('user', $userMock);

        $currentUserMock = $this->createMock(CurrentUser::class);
        $currentUserMock->method('isLoggedIn')->willReturn(false);
        $this->injectCurrentUser($currentUserMock);

        $request = Request::create('/api/webauthn/prepare', 'POST', [], [], [], [], json_encode([
            'username' => 'admin',
            'csrfToken' => 'valid-token',
        ]));

        $response = $this->controller->prepare($request);

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testPrepareRejectsExistingAccountForNonOwner(): void
    {
        // A logged-in user must not be able to attach a passkey to a different existing account.
        $this->enableWebAuthnAndRegistration();
        $this->primeCsrfToken('webauthn-prepare', 'valid-token');

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByLogin')->with('admin', false)->willReturn(true);
        $userMock->method('getUserId')->willReturn(1);
        $this->injectControllerProperty('user', $userMock);

        $currentUserMock = $this->createMock(CurrentUser::class);
        $currentUserMock->method('isLoggedIn')->willReturn(true);
        $currentUserMock->method('getUserId')->willReturn(2);
        $this->injectCurrentUser($currentUserMock);

        $request = Request::create('/api/webauthn/prepare', 'POST', [], [], [], [], json_encode([
            'username' => 'admin',
            'csrfToken' => 'valid-token',
        ]));

        $response = $this->controller->prepare($request);

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testLoginDefersToTokenStepWhenTwoFactorIsEnabled(): void
    {
        // A passkey is only the first factor on a TOTP-protected account: the session must be
        // parked in the same 2fa-pending state the password login uses, never fully granted.
        $session = $this->prepareAuthenticatedPasskeyLogin();

        $currentUserMock = $this->createMock(CurrentUser::class);
        $currentUserMock->method('getUserByLogin')->willReturn(true);
        $currentUserMock->method('isBlocked')->willReturn(false);
        $currentUserMock->method('getUserId')->willReturn(1);
        $currentUserMock->method('getUserData')->with('twofactor_enabled')->willReturn(1);
        $currentUserMock->method('isTwoFactorLockedOut')->willReturn(false);
        $currentUserMock->expects($this->never())->method('setLoggedIn');
        $currentUserMock->expects($this->never())->method('saveToSession');
        $currentUserMock->expects($this->never())->method('updateSessionId');
        $this->injectControllerProperty('loginCurrentUser', $currentUserMock);

        $response = $this->controller->login($this->passkeyLoginRequest());

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $payload = json_decode($response->getContent(), associative: true);
        $this->assertSame('ok', $payload['success']);
        $this->assertSame('http://localhost/admin/token?user-id=1', $payload['redirect']);
        $this->assertSame(1, $session->get('2fa_pending_user_id'));
        $this->assertFalse($session->get('2fa_pending_remember_me'));
    }

    public function testLoginRejectsTwoFactorLockedOutAccount(): void
    {
        $session = $this->prepareAuthenticatedPasskeyLogin();

        $currentUserMock = $this->createMock(CurrentUser::class);
        $currentUserMock->method('getUserByLogin')->willReturn(true);
        $currentUserMock->method('isBlocked')->willReturn(false);
        $currentUserMock->method('getUserId')->willReturn(1);
        $currentUserMock->method('getUserData')->with('twofactor_enabled')->willReturn(1);
        $currentUserMock->method('isTwoFactorLockedOut')->willReturn(true);
        $currentUserMock->expects($this->never())->method('setLoggedIn');
        $this->injectControllerProperty('loginCurrentUser', $currentUserMock);

        $response = $this->controller->login($this->passkeyLoginRequest());

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertNull($session->get('2fa_pending_user_id'));
    }

    public function testLoginGrantsSessionWhenTwoFactorIsDisabled(): void
    {
        $session = $this->prepareAuthenticatedPasskeyLogin();

        $currentUserMock = $this->createMock(CurrentUser::class);
        $currentUserMock->method('getUserByLogin')->willReturn(true);
        $currentUserMock->method('isBlocked')->willReturn(false);
        $currentUserMock->method('getUserData')->with('twofactor_enabled')->willReturn(0);
        $currentUserMock->expects($this->once())->method('setLoggedIn')->with(true);
        $currentUserMock->expects($this->once())->method('saveToSession');
        $this->injectControllerProperty('loginCurrentUser', $currentUserMock);

        $response = $this->controller->login($this->passkeyLoginRequest());

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $payload = json_decode($response->getContent(), associative: true);
        $this->assertSame('http://localhost/', $payload['redirect']);
        $this->assertNull($session->get('2fa_pending_user_id'));
    }

    /**
     * Wires a WebAuthn-enabled configuration, a user with stored keys, an AuthWebAuthn that
     * accepts the assertion, and a real session on the container. Returns that session.
     */
    private function prepareAuthenticatedPasskeyLogin(): Session
    {
        $this->configurationMock
            ->method('get')
            ->willReturnCallback(fn(string $item) => match ($item) {
                'security.enableWebAuthnSupport' => true,
                default => null,
            });
        $this->configurationMock->method('getDefaultUrl')->willReturn('http://localhost/');

        $session = new Session(new MockArraySessionStorage());
        $this->containerMock->method('get')->with('session')->willReturn($session);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByLogin')->willReturn(true);
        $userMock->method('getWebAuthnKeys')->willReturn('[]');
        $this->injectControllerProperty('user', $userMock);

        $authWebAuthnMock = $this->createMock(AuthWebAuthn::class);
        $authWebAuthnMock->method('authenticate')->willReturn(true);
        $this->injectControllerProperty('authWebAuthn', $authWebAuthnMock);

        return $session;
    }

    private function passkeyLoginRequest(): Request
    {
        return Request::create('/api/webauthn/login', 'POST', [], [], [], [], json_encode([
            'username' => 'admin',
            'login' => ['id' => 'credential-id'],
        ]));
    }

    private function enableWebAuthnAndRegistration(): void
    {
        $this->configurationMock
            ->method('get')
            ->willReturnCallback(fn(string $item) => match ($item) {
                'security.enableWebAuthnSupport' => true,
                'security.enableRegistration' => true,
                default => null,
            });
    }

    /**
     * Primes a valid CSRF token in a real session plus its matching cookie, so that
     * Token::verifyToken() succeeds and the controller logic beyond the CSRF check runs.
     */
    private function primeCsrfToken(string $page, string $tokenValue): void
    {
        Token::resetInstanceForTests();

        $session = new Session(new MockArraySessionStorage());

        $tokenReflection = new ReflectionClass(Token::class);
        $token = $tokenReflection->newInstanceWithoutConstructor();
        $token
            ->setPage($page)
            ->setExpiry(time() + 3600)
            ->setSessionToken($tokenValue)
            ->setCookieToken($tokenValue);

        $session->set(sprintf('%s.%s', Token::PMF_SESSION_NAME, $page), $token);

        $this->containerMock->method('get')->with('session')->willReturn($session);

        $_COOKIE[sprintf('%s-%s', Token::PMF_SESSION_NAME, substr(md5($page), 0, 10))] = $tokenValue;
    }

    /**
     * Sets one of the controller's own (constructor-initialised) dependencies via reflection.
     */
    private function injectControllerProperty(string $property, object $value): void
    {
        $reflection = new ReflectionClass(WebAuthnController::class);
        $reflection->getProperty($property)->setValue($this->controller, $value);
    }

    private function injectCurrentUser(CurrentUser $currentUser): void
    {
        $reflection = new ReflectionClass(WebAuthnController::class);
        $currentUserProp = $reflection->getParentClass()->getProperty('currentUser');
        $currentUserProp->setValue($this->controller, $currentUser);
    }

    protected function tearDown(): void
    {
        Token::resetInstanceForTests();
        $_COOKIE = [];
        parent::tearDown();
    }
}
