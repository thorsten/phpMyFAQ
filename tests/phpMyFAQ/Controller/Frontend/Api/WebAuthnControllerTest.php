<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Auth\AuthWebAuthn;
use phpMyFAQ\Auth\WebAuthn\WebAuthnUser;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\TestDatabaseTrait;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(WebAuthnController::class)]
#[UsesNamespace('phpMyFAQ')]
class WebAuthnControllerTest extends TestCase
{
    use TestDatabaseTrait;

    private Configuration $configuration;

    private string $originalWebAuthnValue;

    private string $originalRegistrationValue;

    /**
     * @throws Exception
     */
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

        // Save original values
        $configArray = $this->getConfigArray();
        $this->originalWebAuthnValue = $configArray['security.enableWebAuthnSupport'] ?? 'false';
        $this->originalRegistrationValue = $configArray['security.enableRegistration'] ?? 'true';
    }

    protected function tearDown(): void
    {
        // Restore original config values in-memory (no DB writes)
        $this->setInMemoryConfig('security.enableWebAuthnSupport', $this->originalWebAuthnValue);
        $this->setInMemoryConfig('security.enableRegistration', $this->originalRegistrationValue);
        Token::resetInstanceForTests();
        $_COOKIE = [];

        parent::tearDown();
    }

    /**
     * Sets a config value directly in the in-memory config array using Reflection.
     */
    private function setInMemoryConfig(string $key, string $value): void
    {
        $reflection = new ReflectionClass($this->configuration);
        $property = $reflection->getProperty('config');
        $config = $property->getValue($this->configuration);
        $config[$key] = $value;
        $property->setValue($this->configuration, $config);
    }

    /**
     * Gets the in-memory config array using Reflection.
     */
    private function getConfigArray(): array
    {
        $reflection = new ReflectionClass($this->configuration);
        $property = $reflection->getProperty('config');
        return $property->getValue($this->configuration);
    }

    public function testPrepareReturnsForbiddenWhenWebAuthnDisabled(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'false');

        $requestData = json_encode(['username' => 'testuser']);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $response = $controller->prepare($request);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testRegisterReturnsForbiddenWhenWebAuthnDisabled(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'false');

        $requestData = json_encode(['register' => 'test-data']);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $response = $controller->register($request);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testPrepareLoginReturnsForbiddenWhenWebAuthnDisabled(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'false');

        $requestData = json_encode(['username' => 'testuser']);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $response = $controller->prepareLogin($request);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testLoginReturnsForbiddenWhenWebAuthnDisabled(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'false');

        $requestData = json_encode(['username' => 'testuser', 'login' => 'test-data']);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $response = $controller->login($request);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    /**
     * @throws \JsonException
     * @throws \Exception
     */
    public function testPrepareWithInvalidJsonThrowsException(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'true');

        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $this->expectException(\Exception::class);
        $controller->prepare($request);
    }

    /**
     * @throws \JsonException
     * @throws Exception
     * @throws \Exception
     */
    public function testPrepareWithMissingUsernameThrowsException(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'true');

        $requestData = json_encode([]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $this->expectException(\Exception::class);
        $controller->prepare($request);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testRegisterWithInvalidJsonThrowsException(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'true');

        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $this->expectException(\Exception::class);
        $controller->register($request);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testRegisterWithMissingRegisterDataThrowsException(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'true');

        $requestData = json_encode([]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $this->expectException(\Exception::class);
        $controller->register($request);
    }

    /**
     * @throws \JsonException
     * @throws \Exception
     */
    public function testPrepareLoginWithInvalidJsonThrowsException(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'true');

        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $this->expectException(\Exception::class);
        $controller->prepareLogin($request);
    }

    /**
     * @throws \JsonException
     * @throws \Exception
     */
    public function testPrepareLoginWithMissingUsernameThrowsException(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'true');

        $requestData = json_encode([]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $this->expectException(\Exception::class);
        $controller->prepareLogin($request);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     * @throws \Exception
     */
    public function testLoginWithInvalidJsonThrowsException(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'true');

        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $this->expectException(\Exception::class);
        $controller->login($request);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     * @throws \Exception
     */
    public function testLoginWithMissingUsernameThrowsException(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'true');

        $requestData = json_encode([
            'login' => 'test-data',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $this->expectException(\Exception::class);
        $controller->login($request);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     * @throws \Exception
     */
    public function testLoginWithMissingLoginDataThrowsException(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'true');

        $requestData = json_encode([
            'username' => 'testuser',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $this->expectException(\Exception::class);
        $controller->login($request);
    }

    public function testPrepareReturnsForbiddenWhenRegistrationDisabled(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'true');
        $this->setInMemoryConfig('security.enableRegistration', 'false');

        $requestData = json_encode(['username' => 'nonexistent_user_' . uniqid()]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $response = $controller->prepare($request);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testPrepareRefusesToRegisterAPasskeyForAnotherAccount(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'true');

        $user = $this->createStub(User::class);
        $user->method('getUserByLogin')->willReturn(true);
        $user->method('getUserId')->willReturn(7);

        $authWebAuthn = $this->createMock(AuthWebAuthn::class);
        $authWebAuthn->expects($this->never())->method('prepareChallengeForRegistration');

        $controller = new WebAuthnController($authWebAuthn, $user);
        $session = $this->createSession();
        $this->injectControllerState($controller, $this->createCurrentUser(loggedIn: true, userId: 1), $session);

        $response = $controller->prepare($this->createJsonRequest([
            'username' => 'alice',
            'pmf-csrf-token' => $this->createCsrfToken($session, 'webauthn'),
        ]));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(['error' => Translation::get('msgNoPermission')], $this->decodePayload($response));
    }

    /**
     * @throws \Exception
     */
    public function testPrepareReportsWhyANewAccountCannotBeCreated(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'true');
        $this->setInMemoryConfig('security.enableRegistration', 'true');
        $this->setInMemoryConfig('spam.enableCaptchaCode', 'false');

        $user = $this->createMock(User::class);
        $user->method('getUserByLogin')->willReturn(false);
        $user
            ->expects($this->once())
            ->method('createUser')
            ->with('alice')
            ->willThrowException(new \Exception('Login is already taken.'));

        $authWebAuthn = $this->createMock(AuthWebAuthn::class);
        $authWebAuthn->expects($this->never())->method('prepareChallengeForRegistration');

        $controller = new WebAuthnController($authWebAuthn, $user);
        $session = $this->createSession();
        $this->injectControllerState($controller, $this->createCurrentUser(loggedIn: false, userId: -1), $session);

        $response = $controller->prepare($this->createJsonRequest([
            'username' => 'alice',
            'pmf-csrf-token' => $this->createCsrfToken($session, 'webauthn'),
        ]));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(['error' => 'Login is already taken.'], $this->decodePayload($response));
    }

    /**
     * @throws \Exception
     */
    public function testRegisterFailsWithoutAPreparedUserInTheSession(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'true');

        $authWebAuthn = $this->createStub(AuthWebAuthn::class);
        $authWebAuthn->method('getUserFromSession')->willReturn(null);

        $controller = new WebAuthnController($authWebAuthn, $this->createStub(User::class));
        $session = $this->createSession();
        $this->injectControllerState($controller, $this->createCurrentUser(loggedIn: false, userId: -1), $session);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('User not found in session');

        $controller->register($this->createJsonRequest([
            'register' => 'attestation',
            'pmf-csrf-token' => $this->createCsrfToken($session, 'webauthn'),
        ]));
    }

    /**
     * @throws \Exception
     */
    public function testRegisterFailsWhenThePreparedAccountDisappeared(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'true');

        $webAuthnUser = new WebAuthnUser()
            ->setId('7')
            ->setName('alice')
            ->setWebAuthnKeys('')
            ->setChallenge('challenge');

        $authWebAuthn = $this->createMock(AuthWebAuthn::class);
        $authWebAuthn->method('getUserFromSession')->willReturn($webAuthnUser);
        $authWebAuthn->method('register')->willReturn('stored-keys');
        $authWebAuthn->expects($this->once())->method('storeUserInSession')->with($webAuthnUser);

        $user = $this->createMock(User::class);
        $user
            ->expects($this->once())
            ->method('getUserByLogin')
            ->with('alice')
            ->willThrowException(new Exception('User not found.'));
        $user->expects($this->never())->method('setWebAuthnKeys');

        $controller = new WebAuthnController($authWebAuthn, $user);
        $session = $this->createSession();
        $this->injectControllerState($controller, $this->createCurrentUser(loggedIn: false, userId: -1), $session);

        $response = $controller->register($this->createJsonRequest([
            'register' => 'attestation',
            'pmf-csrf-token' => $this->createCsrfToken($session, 'webauthn'),
        ]));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(['error' => Translation::get('ad_auth_fail')], $this->decodePayload($response));
        self::assertSame('', $webAuthnUser->getChallenge());
        self::assertSame('stored-keys', $webAuthnUser->getWebAuthnKeys());
    }

    /**
     * @throws \Exception
     */
    public function testLoginRejectsLoginDataThatIsNotAnObject(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'true');

        $user = $this->createMock(User::class);
        $user->expects($this->never())->method('getUserByLogin');

        $controller = new WebAuthnController($this->createStub(AuthWebAuthn::class), $user);
        $this->injectControllerState($controller, $this->createCurrentUser(loggedIn: false, userId: -1), $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing login data');

        $controller->login($this->createJsonRequest(['username' => 'alice', 'login' => 'assertion']));
    }

    private function createSession(): Session
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        return $session;
    }

    private function createCurrentUser(bool $loggedIn, int $userId): CurrentUser
    {
        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn($loggedIn);
        $currentUser->method('getUserId')->willReturn($userId);

        return $currentUser;
    }

    private function injectControllerState(WebAuthnController $controller, CurrentUser $currentUser, Session $session): void
    {
        new \ReflectionProperty($controller, 'currentUser')->setValue($controller, $currentUser);
        new \ReflectionProperty($controller, 'session')->setValue($controller, $session);
        new \ReflectionProperty($controller, 'configuration')->setValue($controller, $this->configuration);
    }

    /**
     * @throws \Exception
     */
    private function createCsrfToken(Session $session, string $page): string
    {
        $token = Token::getInstance($session)->getTokenString($page);
        $_COOKIE['pmf-csrf-token-' . substr(md5($page), 0, 10)] = $token;

        return $token;
    }

    /**
     * @param array<string, mixed> $payload
     * @throws \JsonException
     */
    private function createJsonRequest(array $payload): Request
    {
        return new Request([], [], [], [], [], [], json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, mixed>
     * @throws \JsonException
     */
    private function decodePayload(Response $response): array
    {
        return json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
