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
use phpMyFAQ\Auth\WebAuthn\WebAuthnUser;
use phpMyFAQ\Configuration;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
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

        $this->createController();
    }

    /**
     * Builds a controller with fresh configuration and container mocks. The controller's
     * dependencies are readonly, so a test that runs two requests against different
     * collaborators calls this again in between.
     */
    private function createController(): void
    {
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

    public function testPrepareLoginDoesNotPersistKeysForUnknownAccount(): void
    {
        // A request for a non-existing login must still answer with a decoy, but it must not
        // touch the database: the User object is at its default id and has nothing to write.
        $this->configurationMock
            ->method('get')
            ->willReturnCallback(fn(string $item) => match ($item) {
                'security.enableWebAuthnSupport' => true,
                'security.salt' => 'test-salt',
                default => null,
            });

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByLogin')->with('ghost', false)->willReturn(false);
        $userMock->expects($this->never())->method('getWebAuthnKeys');
        $userMock->expects($this->never())->method('setWebAuthnKeys');
        $this->injectControllerProperty('user', $userMock);

        $this->configurationMock->method('getDefaultUrl')->willReturn('http://localhost/');
        $authWebAuthn = new AuthWebAuthn($this->configurationMock);
        $this->injectControllerProperty('authWebAuthn', $authWebAuthn);

        $request = Request::create('/api/webauthn/prepare-login', 'POST', [], [], [], [], json_encode([
            'username' => 'ghost',
        ]));

        $response = $this->controller->prepareLogin($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $payload = json_decode($response->getContent());
        $this->assertCount(1, $payload->allowCredentials);
    }

    public function testPrepareLoginDoesNotPersistKeysForAccountWithoutPasskey(): void
    {
        $this->configurationMock
            ->method('get')
            ->willReturnCallback(fn(string $item) => match ($item) {
                'security.enableWebAuthnSupport' => true,
                'security.salt' => 'test-salt',
                default => null,
            });

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByLogin')->with('alice', false)->willReturn(true);
        $userMock->method('getWebAuthnKeys')->willReturn('');
        $userMock->expects($this->never())->method('setWebAuthnKeys');
        $this->injectControllerProperty('user', $userMock);

        $this->configurationMock->method('getDefaultUrl')->willReturn('http://localhost/');
        $authWebAuthn = new AuthWebAuthn($this->configurationMock);
        $this->injectControllerProperty('authWebAuthn', $authWebAuthn);

        $request = Request::create('/api/webauthn/prepare-login', 'POST', [], [], [], [], json_encode([
            'username' => 'alice',
        ]));

        $response = $this->controller->prepareLogin($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $payload = json_decode($response->getContent());
        $this->assertCount(1, $payload->allowCredentials);
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

    /**
     * An anonymous caller must get the same answer whether or not the login exists, otherwise
     * the endpoint is an oracle for account names. The answer must not be derived from the
     * account either: no stored keys, no database id, and nothing is created at this point.
     */
    public function testPrepareAnswersAnonymousCallerIdenticallyForKnownAndUnknownLogin(): void
    {
        [$knownResponse, $knownState, $knownHandle] = $this->prepareAsAnonymous('admin', userExists: true);
        [$unknownResponse, $unknownState, $unknownHandle] = $this->prepareAsAnonymous('admin', userExists: false);

        $this->assertEquals(Response::HTTP_OK, $unknownResponse->getStatusCode());
        $this->assertSame($unknownResponse->getStatusCode(), $knownResponse->getStatusCode());

        $knownPayload = json_decode((string) $knownResponse->getContent(), associative: true);
        $unknownPayload = json_decode((string) $unknownResponse->getContent(), associative: true);
        $this->assertSame($unknownPayload['challenge'], $knownPayload['challenge']);
        $this->assertSame(array_keys($unknownPayload), array_keys($knownPayload));

        foreach ([
            [$knownState,   $knownHandle],
            [$unknownState, $unknownHandle],
        ] as [$state, $handle]) {
            $this->assertInstanceOf(WebAuthnUser::class, $state);
            $this->assertSame(WebAuthnController::PENDING_SIGN_UP_ID, $state->getId());
            $this->assertSame('admin', $state->getName());
            $this->assertSame('', $state->getWebAuthnKeys());
            // The user handle sent to the client must not be the account's database id.
            $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', (string) $handle);
        }
    }

    public function testPrepareRejectsUnknownLoginForLoggedInCaller(): void
    {
        // Same answer as for an existing account the caller does not own: a logged-in user can
        // neither probe logins nor sign up a second account from an authenticated session.
        $this->enableWebAuthnAndRegistration();
        $this->primeCsrfToken('webauthn-prepare', 'valid-token');

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByLogin')->with('ghost', false)->willReturn(false);
        $userMock->expects($this->never())->method('createUser');
        $this->injectControllerProperty('user', $userMock);

        $authWebAuthnMock = $this->createAuthWebAuthnMock();
        $authWebAuthnMock->expects($this->never())->method('storeUserInSession');
        $this->injectControllerProperty('authWebAuthn', $authWebAuthnMock);

        $currentUserMock = $this->createMock(CurrentUser::class);
        $currentUserMock->method('isLoggedIn')->willReturn(true);
        $currentUserMock->method('getUserId')->willReturn(2);
        $this->injectCurrentUser($currentUserMock);

        $response = $this->controller->prepare($this->prepareRequest('ghost'));

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

    /**
     * A valid session plus CSRF token must not be enough to add a passkey (CWE-620): a passkey
     * planted through a hijacked session would survive logout, remember-me revocation and a
     * password reset. Password accounts have to re-enter the current password.
     */
    public function testPrepareRejectsOwnerWithWrongCurrentPassword(): void
    {
        $this->enableWebAuthnAndRegistration();
        $this->primeCsrfToken('webauthn-prepare', 'valid-token');
        [, $currentUserMock, $authWebAuthnMock] = $this->primeOwnerEnrollment();

        $currentUserMock->method('isStepUpLockedOut')->willReturn(false);
        $currentUserMock->method('verifyPassword')->with('wrong')->willReturn(false);
        $currentUserMock->expects($this->once())->method('stepUpFailure')->willReturn(true);
        $currentUserMock->expects($this->never())->method('stepUpSuccess');
        $authWebAuthnMock->expects($this->never())->method('storeUserInSession');

        $response = $this->controller->prepare($this->prepareRequestWithPassword('admin', 'wrong'));

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testPrepareRejectsOwnerWithoutCurrentPassword(): void
    {
        $this->enableWebAuthnAndRegistration();
        $this->primeCsrfToken('webauthn-prepare', 'valid-token');
        [, $currentUserMock, $authWebAuthnMock] = $this->primeOwnerEnrollment();

        $currentUserMock->method('isStepUpLockedOut')->willReturn(false);
        $currentUserMock->method('verifyPassword')->with('')->willReturn(false);
        $currentUserMock->expects($this->once())->method('stepUpFailure')->willReturn(true);
        $authWebAuthnMock->expects($this->never())->method('storeUserInSession');

        // The pre-fix client payload: username and CSRF token only.
        $response = $this->controller->prepare($this->prepareRequest('admin'));

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testPrepareIsThrottledWhileOwnerIsLockedOut(): void
    {
        $this->enableWebAuthnAndRegistration();
        $this->primeCsrfToken('webauthn-prepare', 'valid-token');
        [, $currentUserMock, $authWebAuthnMock] = $this->primeOwnerEnrollment();

        $currentUserMock->method('isStepUpLockedOut')->willReturn(true);
        $currentUserMock->expects($this->never())->method('verifyPassword');
        $authWebAuthnMock->expects($this->never())->method('storeUserInSession');

        $response = $this->controller->prepare($this->prepareRequestWithPassword('admin', 'correct'));

        $this->assertEquals(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
    }

    /**
     * The ceremony must start from the passkeys already on the account. Seeded with '' the
     * merge in AuthWebAuthn::register() returns only the new key and register() replaces every
     * passkey the owner already has.
     */
    public function testPrepareSeedsCeremonyWithStoredKeysAfterStepUp(): void
    {
        $this->enableWebAuthnAndRegistration();
        $this->primeCsrfToken('webauthn-prepare', 'valid-token');
        [, $currentUserMock, $authWebAuthnMock] = $this->primeOwnerEnrollment(storedKeys: '[{"id":[1,2,3]}]');

        $currentUserMock->method('isStepUpLockedOut')->willReturn(false);
        $currentUserMock->method('verifyPassword')->with('correct')->willReturn(true);
        $currentUserMock->expects($this->once())->method('stepUpSuccess')->willReturn(true);
        $currentUserMock->expects($this->never())->method('stepUpFailure');

        $authWebAuthnMock
            ->expects($this->once())
            ->method('storeUserInSession')
            ->with($this->callback(
                static fn(WebAuthnUser $webAuthnUser): bool => $webAuthnUser->getWebAuthnKeys() === '[{"id":[1,2,3]}]',
            ));

        $response = $this->controller->prepare($this->prepareRequestWithPassword('admin', 'correct'));

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * Passwordless accounts have no password to re-enter; a recent passkey assertion in this
     * session stands in. Without one the enrollment is refused.
     */
    public function testPrepareRejectsPasskeyAccountWithoutRecentAssertion(): void
    {
        $this->enableWebAuthnAndRegistration();
        $this->primeCsrfToken('webauthn-prepare', 'valid-token');
        [, $currentUserMock, $authWebAuthnMock] = $this->primeOwnerEnrollment(
            authSource: 'webauthn',
            storedKeys: '[{"id":[1]}]',
        );

        $currentUserMock->expects($this->never())->method('verifyPassword');
        $authWebAuthnMock->expects($this->never())->method('storeUserInSession');

        $response = $this->controller->prepare($this->prepareRequest('admin'));

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testPrepareRejectsPasskeyAccountWithStaleAssertion(): void
    {
        $this->enableWebAuthnAndRegistration();
        $session = $this->primeCsrfToken('webauthn-prepare', 'valid-token');
        $session->set(
            WebAuthnController::SESSION_PASSKEY_ASSERTED_AT,
            time() - WebAuthnController::PASSKEY_REAUTH_WINDOW - 1,
        );
        [, , $authWebAuthnMock] = $this->primeOwnerEnrollment(authSource: 'webauthn', storedKeys: '[{"id":[1]}]');
        $authWebAuthnMock->expects($this->never())->method('storeUserInSession');

        $response = $this->controller->prepare($this->prepareRequest('admin'));

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testPrepareAcceptsPasskeyAccountWithRecentAssertion(): void
    {
        $this->enableWebAuthnAndRegistration();
        $session = $this->primeCsrfToken('webauthn-prepare', 'valid-token');
        $session->set(WebAuthnController::SESSION_PASSKEY_ASSERTED_AT, time() - 10);
        [, $currentUserMock, $authWebAuthnMock] = $this->primeOwnerEnrollment(
            authSource: 'webauthn',
            storedKeys: '[{"id":[1]}]',
        );

        $currentUserMock->expects($this->never())->method('verifyPassword');
        $authWebAuthnMock
            ->expects($this->once())
            ->method('storeUserInSession')
            ->with($this->callback(
                static fn(WebAuthnUser $webAuthnUser): bool => $webAuthnUser->getWebAuthnKeys() === '[{"id":[1]}]',
            ));

        $response = $this->controller->prepare($this->prepareRequest('admin'));

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    #[DataProvider('loginExistenceProvider')]
    public function testPrepareRejectsLoginOutsideDomainWhitelist(bool $userExists): void
    {
        // The whitelist is checked for every anonymous request, whether or not the login exists.
        $this->enableWebAuthnAndRegistration([
            'security.domainWhiteListForRegistrations' => 'corp.example',
        ]);
        $this->primeCsrfToken('webauthn-prepare', 'valid-token');

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByLogin')->with('outsider@evil.example', false)->willReturn($userExists);
        $userMock->method('isValidLogin')->willReturn(true);
        $userMock->expects($this->never())->method('createUser');
        $userMock->expects($this->never())->method('setStatus');
        $this->injectControllerProperty('user', $userMock);

        $authWebAuthnMock = $this->createAuthWebAuthnMock();
        $authWebAuthnMock->expects($this->never())->method('storeUserInSession');
        $this->injectControllerProperty('authWebAuthn', $authWebAuthnMock);

        $response = $this->controller->prepare($this->prepareRequest('outsider@evil.example'));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString('domain is not allowed', (string) $response->getContent());
    }

    #[DataProvider('loginExistenceProvider')]
    public function testPrepareRejectsInvalidLogin(bool $userExists): void
    {
        $this->enableWebAuthnAndRegistration();
        $this->primeCsrfToken('webauthn-prepare', 'valid-token');

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByLogin')->with('x', false)->willReturn($userExists);
        $userMock->method('isValidLogin')->with('x')->willReturn(false);
        $userMock->expects($this->never())->method('createUser');
        $this->injectControllerProperty('user', $userMock);

        $authWebAuthnMock = $this->createAuthWebAuthnMock();
        $authWebAuthnMock->expects($this->never())->method('storeUserInSession');
        $this->injectControllerProperty('authWebAuthn', $authWebAuthnMock);

        $response = $this->controller->prepare($this->prepareRequest('x'));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    /**
     * @return array<string, array{0: bool}>
     */
    public static function loginExistenceProvider(): array
    {
        return [
            'login exists' => [true],
            'login is unknown' => [false],
        ];
    }

    public function testRegisterRejectsSessionStateIssuedByOtherInstance(): void
    {
        // Multisite instances share one session store. State prepared on tenant A (where the
        // ownership check passed for A's "admin") must not key the master's "admin".
        $this->primeRegister(sessionAppId: 'a.faq.test', instanceAppId: 'faq.test');

        $userMock = $this->createMock(User::class);
        $userMock->expects($this->never())->method('setWebAuthnKeys');
        $this->injectControllerProperty('user', $userMock);

        $response = $this->controller->register($this->registerRequest());

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testRegisterRejectsSessionStateWithoutInstanceBinding(): void
    {
        // Fails closed: state without an appId (e.g. from before the binding existed) is rejected.
        $this->primeRegister(sessionAppId: '', instanceAppId: 'faq.test');

        $userMock = $this->createMock(User::class);
        $userMock->expects($this->never())->method('setWebAuthnKeys');
        $this->injectControllerProperty('user', $userMock);

        $response = $this->controller->register($this->registerRequest());

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testRegisterRejectsAccountIdMismatch(): void
    {
        // The login in the session must resolve to the very account prepare() staged.
        $this->primeRegister(sessionAppId: 'faq.test', instanceAppId: 'faq.test', sessionUserId: '7');

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByLogin')->with('admin')->willReturn(true);
        $userMock->method('getUserId')->willReturn(1);
        $userMock->expects($this->never())->method('setWebAuthnKeys');
        $this->injectControllerProperty('user', $userMock);

        $response = $this->controller->register($this->registerRequest());

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testRegisterRejectsAnonymousCallerForPasswordAccount(): void
    {
        // Independent of prepare(): an anonymous caller must not replace the passkeys of an
        // existing password-based account, even with matching session state.
        $this->primeRegister(sessionAppId: 'faq.test', instanceAppId: 'faq.test');

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByLogin')->with('admin')->willReturn(true);
        $userMock->method('getUserId')->willReturn(1);
        $userMock->method('getUserAuthSource')->willReturn('local');
        $userMock->method('getWebAuthnKeys')->willReturn('');
        $userMock->expects($this->never())->method('setWebAuthnKeys');
        $this->injectControllerProperty('user', $userMock);

        $currentUserMock = $this->createMock(CurrentUser::class);
        $currentUserMock->method('isLoggedIn')->willReturn(false);
        $this->injectCurrentUser($currentUserMock);

        $response = $this->controller->register($this->registerRequest());

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testRegisterAcceptsAuthenticatedOwner(): void
    {
        $this->primeRegister(sessionAppId: 'faq.test', instanceAppId: 'faq.test');

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByLogin')->with('admin')->willReturn(true);
        $userMock->method('getUserId')->willReturn(1);
        $userMock->expects($this->once())->method('setWebAuthnKeys')->with('[new-key]')->willReturn(true);
        // The owner is told about the new credential, so a planted passkey does not go unnoticed.
        $userMock->expects($this->once())->method('mailUser')->willReturn(1);
        $this->injectControllerProperty('user', $userMock);

        $currentUserMock = $this->createMock(CurrentUser::class);
        $currentUserMock->method('isLoggedIn')->willReturn(true);
        $currentUserMock->method('getUserId')->willReturn(1);
        $this->injectCurrentUser($currentUserMock);

        $response = $this->controller->register($this->registerRequest());

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testRegisterSucceedsWhenOwnerNotificationFails(): void
    {
        $this->primeRegister(sessionAppId: 'faq.test', instanceAppId: 'faq.test');
        $logger = $this->createMock(\Monolog\Logger::class);
        $logger->expects($this->once())->method('warning');
        $this->configurationMock->method('getLogger')->willReturn($logger);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByLogin')->with('admin')->willReturn(true);
        $userMock->method('getUserId')->willReturn(1);
        $userMock->method('setWebAuthnKeys')->willReturn(true);
        $userMock->method('mailUser')->willThrowException(new \RuntimeException('no mailer'));
        $this->injectControllerProperty('user', $userMock);

        $currentUserMock = $this->createMock(CurrentUser::class);
        $currentUserMock->method('isLoggedIn')->willReturn(true);
        $currentUserMock->method('getUserId')->willReturn(1);
        $this->injectCurrentUser($currentUserMock);

        $response = $this->controller->register($this->registerRequest());

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testRegisterRejectsAnonymousCallerForKeylessPasskeyAccount(): void
    {
        // Accounts are no longer created by prepare(), so a WebAuthn-sourced account without a
        // passkey is not a sign-up in progress and must not be keyed by an anonymous caller.
        $this->primeRegister(sessionAppId: 'faq.test', instanceAppId: 'faq.test');

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByLogin')->with('admin')->willReturn(true);
        $userMock->method('getUserId')->willReturn(1);
        $userMock->method('getUserAuthSource')->willReturn('webauthn');
        $userMock->method('getWebAuthnKeys')->willReturn('');
        $userMock->expects($this->never())->method('setWebAuthnKeys');
        $this->injectControllerProperty('user', $userMock);

        $currentUserMock = $this->createMock(CurrentUser::class);
        $currentUserMock->method('isLoggedIn')->willReturn(false);
        $this->injectCurrentUser($currentUserMock);

        $response = $this->controller->register($this->registerRequest());

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testRegisterCreatesSignUpAccountBlockedWhenManualActivationIsOn(): void
    {
        // GHSA-w22q-m2fm-x9f4 follow-up: a passwordless sign-up must not bypass the admin's
        // registration policy. With spam.manualActivation on, the account stays blocked.
        $this->primeSignUp(['spam.manualActivation' => true]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserId')->willReturn(42);
        $userMock->expects($this->once())->method('createUser')->with('newbie@corp.example')->willReturn(true);
        $userMock->expects($this->once())->method('setStatus')->with('blocked')->willReturn(true);
        $userMock->expects($this->once())->method('setAuthSource')->with('webauthn')->willReturn(true);
        $userMock->expects($this->once())->method('setWebAuthnKeys')->with('[new-key]')->willReturn(true);
        // A sign-up has no owner to warn; the administrator is notified instead.
        $userMock->expects($this->never())->method('mailUser');
        $this->injectControllerProperty('user', $userMock);

        $response = $this->controller->register($this->registerRequest());

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testRegisterActivatesSignUpAccountWhenManualActivationIsOff(): void
    {
        $this->primeSignUp(['spam.manualActivation' => false]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserId')->willReturn(42);
        $userMock->method('createUser')->willReturn(true);
        $userMock->method('setWebAuthnKeys')->willReturn(true);
        $statuses = [];
        $userMock
            ->expects($this->exactly(2))
            ->method('setStatus')
            ->willReturnCallback(function (string $status) use (&$statuses): bool {
                $statuses[] = $status;
                return true;
            });
        $this->injectControllerProperty('user', $userMock);

        $response = $this->controller->register($this->registerRequest());

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(['blocked', 'active'], $statuses);
    }

    /**
     * A sign-up for a login or e-mail address that is already taken is discarded, and the answer
     * is the one a successful sign-up gets. The existing account is never written to.
     */
    #[DataProvider('signUpCollisionProvider')]
    public function testRegisterDiscardsSignUpForTakenLoginWithSuccessResponse(string $collision): void
    {
        $this->primeSignUp();
        $userMock = $this->createMock(User::class);
        $userMock->method('getUserId')->willReturn(42);
        $userMock->method('createUser')->willReturn(true);
        $userMock->method('setStatus')->willReturn(true);
        $userMock->method('setWebAuthnKeys')->willReturn(true);
        $this->injectControllerProperty('user', $userMock);
        $created = $this->controller->register($this->registerRequest());

        $this->primeSignUp();
        $userMock = $this->createMock(User::class);
        $userMock->method('getUserId')->willReturn(1);
        $userMock->method('createUser')->willThrowException(new \phpMyFAQ\Core\Exception($collision));
        $userMock->expects($this->never())->method('setStatus');
        $userMock->expects($this->never())->method('setAuthSource');
        $userMock->expects($this->never())->method('setUserData');
        $userMock->expects($this->never())->method('setWebAuthnKeys');
        $this->injectControllerProperty('user', $userMock);
        $discarded = $this->controller->register($this->registerRequest());

        $this->assertEquals(Response::HTTP_OK, $created->getStatusCode());
        $this->assertSame($created->getStatusCode(), $discarded->getStatusCode());
        $this->assertSame($created->getContent(), $discarded->getContent());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function signUpCollisionProvider(): array
    {
        return [
            'login is taken' => [User::ERROR_USER_LOGIN_NOT_UNIQUE],
            'e-mail address is taken' => [User::ERROR_USER_EMAIL_NOT_UNIQUE],
        ];
    }

    public function testRegisterDoesNotCreateSignUpAccountWithoutValidAttestation(): void
    {
        // The ceremony is verified first; a failed one must not reach the account lookup.
        $authWebAuthnMock = $this->primeSignUp();
        $authWebAuthnMock
            ->method('register')
            ->willThrowException(new \phpMyFAQ\Core\Exception('Registration challenge mismatch or missing'));

        $userMock = $this->createMock(User::class);
        $userMock->expects($this->never())->method('createUser');
        $userMock->expects($this->never())->method('getUserByLogin');
        $userMock->expects($this->never())->method('setWebAuthnKeys');
        $this->injectControllerProperty('user', $userMock);

        $response = $this->controller->register($this->registerRequest());

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testRegisterRejectsSignUpWhenRegistrationIsDisabled(): void
    {
        $this->primeSignUp(['security.enableRegistration' => false]);

        $userMock = $this->createMock(User::class);
        $userMock->expects($this->never())->method('createUser');
        $this->injectControllerProperty('user', $userMock);

        $response = $this->controller->register($this->registerRequest());

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
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
        // Recorded so a passwordless account can add another passkey right after signing in.
        $this->assertEqualsWithDelta(time(), $session->get(WebAuthnController::SESSION_PASSKEY_ASSERTED_AT), 5);
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

    /**
     * Enables WebAuthn, primes the register CSRF token and stubs AuthWebAuthn with session state
     * for login "admin" issued by $sessionAppId while this instance runs as $instanceAppId.
     */
    private function primeRegister(string $sessionAppId, string $instanceAppId, string $sessionUserId = '1'): void
    {
        $this->configurationMock
            ->method('get')
            ->willReturnCallback(fn(string $item) => match ($item) {
                'security.enableWebAuthnSupport' => true,
                default => null,
            });
        $this->primeCsrfToken('webauthn-register', 'valid-token');

        $webAuthnUser = (new WebAuthnUser())
            ->setName('admin')
            ->setId($sessionUserId)
            ->setWebAuthnKeys('')
            ->setChallenge('challenge')
            ->setAppId($sessionAppId);

        $authWebAuthnMock = $this->createMock(AuthWebAuthn::class);
        $authWebAuthnMock->method('getUserFromSession')->willReturn($webAuthnUser);
        $authWebAuthnMock->method('getAppId')->willReturn($instanceAppId);
        $authWebAuthnMock->method('register')->willReturn('[new-key]');
        $this->injectControllerProperty('authWebAuthn', $authWebAuthnMock);
    }

    /**
     * Enables WebAuthn and registration, primes the register CSRF token and stages the state
     * prepare() leaves behind for a passwordless sign-up of "newbie@corp.example". Unless the
     * returned mock is told otherwise, the attestation is accepted.
     *
     * @param array<string, mixed> $extraConfig additional configuration items, e.g. spam.manualActivation
     * @return AuthWebAuthn&\PHPUnit\Framework\MockObject\MockObject
     */
    private function primeSignUp(array $extraConfig = []): AuthWebAuthn
    {
        $config = $extraConfig
        + [
            'security.enableWebAuthnSupport' => true,
            'security.enableRegistration' => true,
        ];

        $this->createController();
        $this->configurationMock->method('get')->willReturnCallback(fn(string $item) => $config[$item] ?? null);
        $this->primeCsrfToken('webauthn-register', 'valid-token');

        $webAuthnUser = (new WebAuthnUser())
            ->setName('newbie@corp.example')
            ->setId(WebAuthnController::PENDING_SIGN_UP_ID)
            ->setWebAuthnKeys('')
            ->setChallenge('challenge')
            ->setAppId('faq.test');

        $authWebAuthnMock = $this->createMock(AuthWebAuthn::class);
        $authWebAuthnMock->method('getUserFromSession')->willReturn($webAuthnUser);
        $authWebAuthnMock->method('getAppId')->willReturn('faq.test');
        $authWebAuthnMock->method('register')->with('{}', '', 'challenge')->willReturn('[new-key]');
        $this->injectControllerProperty('authWebAuthn', $authWebAuthnMock);

        return $authWebAuthnMock;
    }

    /**
     * Runs prepare() as an anonymous caller for a login that does or does not exist.
     *
     * @return array{0: Response, 1: WebAuthnUser|null, 2: string|null} response, staged session state, user handle
     */
    private function prepareAsAnonymous(string $username, bool $userExists): array
    {
        $this->createController();
        $this->enableWebAuthnAndRegistration();
        $this->primeCsrfToken('webauthn-prepare', 'valid-token');

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByLogin')->with($username, false)->willReturn($userExists);
        $userMock->method('isValidLogin')->willReturn(true);
        $userMock->method('getUserId')->willReturn($userExists ? 1 : -1);
        $userMock->method('getWebAuthnKeys')->willReturn('[{"id":[1,2,3]}]');
        $userMock->expects($this->never())->method('createUser');
        $userMock->expects($this->never())->method('setStatus');
        $userMock->expects($this->never())->method('setWebAuthnKeys');
        $this->injectControllerProperty('user', $userMock);

        $stagedState = null;
        $userHandle = null;
        $authWebAuthnMock = $this->createMock(AuthWebAuthn::class);
        $authWebAuthnMock
            ->method('prepareChallengeForRegistration')
            ->willReturnCallback(static function (string $name, string $userId) use (&$userHandle): array {
                $userHandle = $userId;
                return ['b64challenge' => 'Y2hhbGxlbmdl', 'publicKey' => ['user' => ['name' => $name]]];
            });
        $authWebAuthnMock->method('getAppId')->willReturn('faq.test');
        $authWebAuthnMock
            ->method('storeUserInSession')
            ->willReturnCallback(static function (WebAuthnUser $webAuthnUser) use (&$stagedState): void {
                $stagedState = $webAuthnUser;
            });
        $this->injectControllerProperty('authWebAuthn', $authWebAuthnMock);

        return [$this->controller->prepare($this->prepareRequest($username)), $stagedState, $userHandle];
    }

    private function registerRequest(): Request
    {
        return Request::create('/api/webauthn/register', 'POST', [], [], [], [], json_encode([
            'csrfToken' => 'valid-token',
            'register' => '{}',
        ]));
    }

    private function passkeyLoginRequest(): Request
    {
        return Request::create('/api/webauthn/login', 'POST', [], [], [], [], json_encode([
            'username' => 'admin',
            'login' => ['id' => 'credential-id'],
        ]));
    }

    /**
     * @param array<string, mixed> $extraConfig additional configuration items, e.g. spam.manualActivation
     */
    private function enableWebAuthnAndRegistration(array $extraConfig = []): void
    {
        $config =
            [
                'security.enableWebAuthnSupport' => true,
                'security.enableRegistration' => true,
            ] + $extraConfig;

        $this->configurationMock->method('get')->willReturnCallback(fn(string $item) => $config[$item] ?? null);
    }

    private function prepareRequest(string $username): Request
    {
        return Request::create('/api/webauthn/prepare', 'POST', [], [], [], [], json_encode([
            'username' => $username,
            'csrfToken' => 'valid-token',
        ]));
    }

    private function createAuthWebAuthnMock(): AuthWebAuthn
    {
        $authWebAuthnMock = $this->createMock(AuthWebAuthn::class);
        $authWebAuthnMock
            ->method('prepareChallengeForRegistration')
            ->willReturn(['b64challenge' => 'Y2hhbGxlbmdl', 'publicKey' => []]);
        $authWebAuthnMock->method('getAppId')->willReturn('faq.test');

        return $authWebAuthnMock;
    }

    /**
     * Primes a valid CSRF token in a real session plus its matching cookie, so that
     * Token::verifyToken() succeeds and the controller logic beyond the CSRF check runs.
     */
    private function primeCsrfToken(string $page, string $tokenValue): Session
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

        return $session;
    }

    /**
     * Wires an existing password account "admin" (id 1) owned by the logged-in caller, with the
     * given stored passkeys, and a real AuthWebAuthn-free challenge stub.
     *
     * @return array{0: User&\PHPUnit\Framework\MockObject\MockObject, 1: CurrentUser&\PHPUnit\Framework\MockObject\MockObject, 2: AuthWebAuthn&\PHPUnit\Framework\MockObject\MockObject}
     */
    private function primeOwnerEnrollment(string $authSource = 'local', string $storedKeys = ''): array
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByLogin')->with('admin', false)->willReturn(true);
        $userMock->method('getUserId')->willReturn(1);
        $userMock->method('getUserAuthSource')->willReturn($authSource);
        $userMock->method('getWebAuthnKeys')->willReturn($storedKeys);
        $this->injectControllerProperty('user', $userMock);

        $currentUserMock = $this->createMock(CurrentUser::class);
        $currentUserMock->method('isLoggedIn')->willReturn(true);
        $currentUserMock->method('getUserId')->willReturn(1);
        $this->injectCurrentUser($currentUserMock);

        $authWebAuthnMock = $this->createAuthWebAuthnMock();
        $this->injectControllerProperty('authWebAuthn', $authWebAuthnMock);

        return [$userMock, $currentUserMock, $authWebAuthnMock];
    }

    private function prepareRequestWithPassword(string $username, string $currentPassword): Request
    {
        return Request::create('/api/webauthn/prepare', 'POST', [], [], [], [], json_encode([
            'username' => $username,
            'csrfToken' => 'valid-token',
            'currentPassword' => $currentPassword,
        ]));
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
