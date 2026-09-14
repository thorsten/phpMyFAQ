<?php

namespace phpMyFAQ\Auth;

use Monolog\Logger;
use phpMyFAQ\Auth\EntraId\EntraIdSession;
use phpMyFAQ\Auth\EntraId\OAuth;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\AuthenticationSourceType;
use phpMyFAQ\User;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\RedirectResponse;

const AAD_OAUTH_TENANTID = 'test-tenant-id';

const AAD_OAUTH_CLIENTID = 'test-client-id';

const AAD_OAUTH_SCOPE = 'test-scope';

#[AllowMockObjectsWithoutExpectations]
class AuthEntraIdTest extends TestCase
{
    private Configuration $configurationMock;
    private OAuth $oAuthMock;
    private EntraIdSession $sessionMock;
    private Logger $loggerMock;
    private AuthEntraId $authEntraId;

    protected function setUp(): void
    {
        $this->configurationMock = $this->createMock(Configuration::class);
        $this->oAuthMock = $this->createMock(OAuth::class);
        $this->sessionMock = $this->createMock(EntraIdSession::class);
        $this->loggerMock = $this->createStub(Logger::class);

        $this->configurationMock->method('getLogger')->willReturn($this->loggerMock);
        $this->oAuthMock->method('getEntraIdSession')->willReturn($this->sessionMock);

        $this->authEntraId = new AuthEntraId($this->configurationMock, $this->oAuthMock);
    }

    private function createAuthWithUser(User $user): AuthEntraId
    {
        return new AuthEntraId($this->configurationMock, $this->oAuthMock, static fn(): User => $user);
    }

    public function testConstruct(): void
    {
        $this->assertInstanceOf(AuthEntraId::class, $this->authEntraId);
        $this->assertInstanceOf(AuthDriverInterface::class, $this->authEntraId);
    }

    public function testCreateLinksTheNewAccountToTheObjectIdentifier(): void
    {
        $this->oAuthMock->method('getName')->willReturn('John Doe');
        $this->oAuthMock->method('getMail')->willReturn('john@example.com');
        $this->oAuthMock->method('getObjectId')->willReturn('oid-123');

        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('createUser')->with('john@example.com', '', '')->willReturn(true);
        $user
            ->expects($this->once())
            ->method('setUserData')
            ->with(['display_name' => 'John Doe', 'email' => 'john@example.com', 'entra_oid' => 'oid-123'])
            ->willReturn(true);
        $user->expects($this->once())->method('setStatus')->with('active');
        $user->expects($this->once())->method('setAuthSource')->with(AuthenticationSourceType::AUTH_AZURE->value);
        $user->method('getUserId')->willReturn(42);

        $auth = $this->createAuthWithUser($user);

        $this->assertTrue($auth->create('john@example.com', ''));
        $this->assertSame(42, $auth->getAuthenticatedUserId());
    }

    public function testCreateNeverTouchesAnExistingAccountWhenTheLoginIsTaken(): void
    {
        $this->oAuthMock->method('getMail')->willReturn('john@example.com');
        $this->oAuthMock->method('getObjectId')->willReturn('oid-123');

        $user = $this->createMock(User::class);
        $user
            ->expects($this->once())
            ->method('createUser')
            ->willThrowException(new Exception(User::ERROR_USER_LOGIN_NOT_UNIQUE));
        $user->expects($this->never())->method('setStatus');
        $user->expects($this->never())->method('setAuthSource');
        $user->expects($this->never())->method('setUserData');

        $auth = $this->createAuthWithUser($user);

        $this->assertFalse($auth->create('john@example.com', ''));
        $this->assertSame(0, $auth->getAuthenticatedUserId());
    }

    public function testCreateFailsWhenTheLinkCannotBePersisted(): void
    {
        $this->oAuthMock->method('getMail')->willReturn('john@example.com');
        $this->oAuthMock->method('getObjectId')->willReturn('oid-123');

        $user = $this->createMock(User::class);
        $user->method('createUser')->willReturn(true);
        $user->method('setUserData')->willReturn(false);
        $user->expects($this->never())->method('setStatus');

        $auth = $this->createAuthWithUser($user);

        $this->assertFalse($auth->create('john@example.com', ''));
    }

    public function testUpdate(): void
    {
        $result = $this->authEntraId->update('test@example.com', 'password');
        $this->assertTrue($result);
    }

    public function testDelete(): void
    {
        $result = $this->authEntraId->delete('test@example.com');
        $this->assertTrue($result);
    }

    public function testCheckCredentialsAcceptsTheAccountLinkedToTheObjectIdentifier(): void
    {
        $this->oAuthMock->method('getMail')->willReturn('john@example.com');
        $this->oAuthMock->method('getObjectId')->willReturn('oid-123');

        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getUserIdByEntraOid')->with('oid-123')->willReturn(7);
        $user->expects($this->once())->method('getUserById')->with(7, true)->willReturn(true);
        $user->method('getStatus')->willReturn('active');
        $user->method('getUserId')->willReturn(7);
        $user->expects($this->never())->method('createUser');
        $user->expects($this->never())->method('setStatus');

        $auth = $this->createAuthWithUser($user);

        $this->assertTrue($auth->checkCredentials('john@example.com', ''));
        $this->assertSame(7, $auth->getAuthenticatedUserId());
    }

    public function testCheckCredentialsRefusesABlockedLinkedAccount(): void
    {
        $this->oAuthMock->method('getMail')->willReturn('john@example.com');
        $this->oAuthMock->method('getObjectId')->willReturn('oid-123');

        $user = $this->createMock(User::class);
        $user->method('getUserIdByEntraOid')->willReturn(7);
        $user->method('getUserById')->willReturn(true);
        $user->method('getStatus')->willReturn('blocked');
        $user->expects($this->never())->method('createUser');
        $user->expects($this->never())->method('setStatus');

        $auth = $this->createAuthWithUser($user);

        $this->assertFalse($auth->checkCredentials('john@example.com', ''));
        $this->assertSame(0, $auth->getAuthenticatedUserId());
    }

    public function testCheckCredentialsRefusesAnExistingAccountThatIsNotLinked(): void
    {
        $this->oAuthMock->method('getMail')->willReturn('admin@example.com');
        $this->oAuthMock->method('getObjectId')->willReturn('oid-attacker');

        $user = $this->createMock(User::class);
        $user->method('getUserIdByEntraOid')->willReturn(0);
        $user->expects($this->once())->method('getUserByLogin')->with('admin@example.com', false)->willReturn(true);
        $user->expects($this->never())->method('createUser');
        $user->expects($this->never())->method('setStatus');
        $user->expects($this->never())->method('setAuthSource');
        $user->expects($this->never())->method('setUserData');

        $auth = $this->createAuthWithUser($user);

        $this->assertFalse($auth->checkCredentials('admin@example.com', ''));
        $this->assertSame(0, $auth->getAuthenticatedUserId());
    }

    public function testCheckCredentialsProvisionsAnAccountForAnUnknownLogin(): void
    {
        $this->oAuthMock->method('getName')->willReturn('Jane Doe');
        $this->oAuthMock->method('getMail')->willReturn('jane@example.com');
        $this->oAuthMock->method('getObjectId')->willReturn('oid-jane');

        $user = $this->createMock(User::class);
        $user->method('getUserIdByEntraOid')->willReturn(0);
        $user->expects($this->once())->method('getUserByLogin')->with('jane@example.com', false)->willReturn(false);
        $user->expects($this->once())->method('createUser')->with('jane@example.com', '', '')->willReturn(true);
        $user->expects($this->once())->method('setUserData')->willReturn(true);
        $user->expects($this->once())->method('setStatus')->with('active');
        $user->method('getUserId')->willReturn(9);

        $auth = $this->createAuthWithUser($user);

        $this->assertTrue($auth->checkCredentials('jane@example.com', ''));
        $this->assertSame(9, $auth->getAuthenticatedUserId());
    }

    #[DataProvider('rejectedLoginProvider')]
    public function testCheckCredentialsRefusesWithoutAUsableIdentity(string $login, string $mail, string $oid): void
    {
        $this->oAuthMock->method('getMail')->willReturn($mail);
        $this->oAuthMock->method('getObjectId')->willReturn($oid);

        $user = $this->createMock(User::class);
        $user->expects($this->never())->method('createUser');
        $user->expects($this->never())->method('getUserIdByEntraOid');

        $auth = $this->createAuthWithUser($user);

        $this->assertFalse($auth->checkCredentials($login, ''));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function rejectedLoginProvider(): array
    {
        return [
            'login differs from token mail' => ['other@example.com', 'john@example.com', 'oid-123'],
            'empty login and empty token' => ['', '', 'oid-123'],
            'token without object identifier' => ['john@example.com', 'john@example.com', ''],
        ];
    }

    public function testIsValidLoginRejectsAnEmptyLoginEvenWhenTheTokenIsEmpty(): void
    {
        $this->oAuthMock->method('getMail')->willReturn('');

        $this->assertSame(0, $this->authEntraId->isValidLogin(''));
    }

    public function testIsValidStateAcceptsTheStateStoredInTheSession(): void
    {
        $this->sessionMock
            ->expects($this->exactly(2))
            ->method('get')
            ->with(EntraIdSession::ENTRA_ID_OAUTH_STATE)
            ->willReturn('state-123');

        $this->assertTrue($this->authEntraId->isValidState('state-123'));
        $this->assertFalse($this->authEntraId->isValidState('state-456'));
    }

    public function testIsValidStateFallsBackToTheCookieWhenTheSessionIsEmpty(): void
    {
        $this->sessionMock->method('get')->willReturn(null);
        $this->sessionMock
            ->expects($this->once())
            ->method('getCookie')
            ->with(EntraIdSession::ENTRA_ID_OAUTH_STATE)
            ->willReturn('state-123');

        $this->assertTrue($this->authEntraId->isValidState('state-123'));
    }

    public function testIsValidStateRejectsMissingStates(): void
    {
        $this->sessionMock->method('get')->willReturn(null);
        $this->sessionMock->method('getCookie')->willReturn('');

        $this->assertFalse($this->authEntraId->isValidState(''));
        $this->assertFalse($this->authEntraId->isValidState('state-123'));
    }

    public function testIsValidLoginSuccess(): void
    {
        $login = 'test@example.com';

        $this->oAuthMock->expects($this->once())->method('getMail')->willReturn('test@example.com');

        $result = $this->authEntraId->isValidLogin($login);
        $this->assertEquals(1, $result);
    }

    public function testIsValidLoginFailure(): void
    {
        $login = 'test@example.com';

        $this->oAuthMock->expects($this->once())->method('getMail')->willReturn('different@example.com');

        $result = $this->authEntraId->isValidLogin($login);
        $this->assertEquals(0, $result);
    }

    public function testAuthorize(): void
    {
        $defaultUrl = 'https://example.com/';

        $this->configurationMock->expects($this->once())->method('getDefaultUrl')->willReturn($defaultUrl);

        $this->sessionMock->expects($this->once())->method('setCurrentSessionKey');

        $stored = [];
        $this->sessionMock
            ->expects($this->exactly(3))
            ->method('set')
            ->willReturnCallback(static function (string $key, mixed $value) use (&$stored): void {
                $stored[$key] = $value;
            });

        $cookies = [];
        $this->sessionMock
            ->expects($this->exactly(3))
            ->method('setCookie')
            ->willReturnCallback(static function (string $name, mixed $value, int $timeout, bool $strict) use (
                &$cookies,
            ): void {
                $cookies[$name] = [$value, $timeout, $strict];
            });

        $response = $this->authEntraId->authorize();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $location = (string) $response->headers->get('Location');
        $this->assertStringContainsString('login.microsoftonline.com', $location);
        $this->assertStringContainsString('test-tenant-id', $location);
        $this->assertStringContainsString('test-client-id', $location);

        $this->assertArrayHasKey(EntraIdSession::ENTRA_ID_OAUTH_VERIFIER, $stored);
        $this->assertArrayHasKey(EntraIdSession::ENTRA_ID_OAUTH_STATE, $stored);
        $state = $stored[EntraIdSession::ENTRA_ID_OAUTH_STATE];
        $this->assertIsString($state);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $state);
        $this->assertStringContainsString('&state=' . $state, $location);

        $this->assertArrayHasKey(EntraIdSession::ENTRA_ID_OAUTH_NONCE, $stored);
        $nonce = $stored[EntraIdSession::ENTRA_ID_OAUTH_NONCE];
        $this->assertIsString($nonce);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $nonce);
        $this->assertNotSame($state, $nonce);
        $this->assertStringContainsString('&nonce=' . $nonce, $location);

        $this->assertSame(
            [$stored[EntraIdSession::ENTRA_ID_OAUTH_VERIFIER], 7200, false],
            $cookies[EntraIdSession::ENTRA_ID_OAUTH_VERIFIER],
        );
        $this->assertSame([$state, 7200, false], $cookies[EntraIdSession::ENTRA_ID_OAUTH_STATE]);
        $this->assertSame([$nonce, 7200, false], $cookies[EntraIdSession::ENTRA_ID_OAUTH_NONCE]);
    }

    public function testLogout(): void
    {
        $response = $this->authEntraId->logout();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString(
            'login.microsoftonline.com/common/wsfederation',
            (string) $response->headers->get('Location'),
        );
        $this->assertStringContainsString('wa=wsignout1.0', (string) $response->headers->get('Location'));
    }

    public function testCreateOAuthChallengeGeneration(): void
    {
        // Use reflection to test a private method
        $reflection = new ReflectionClass($this->authEntraId);
        $method = $reflection->getMethod('createOAuthChallenge');

        // Get private properties
        $verifierProperty = $reflection->getProperty('oAuthVerifier');

        $challengeProperty = $reflection->getProperty('oAuthChallenge');

        // Test challenge generation
        $method->invoke($this->authEntraId);

        $verifier = $verifierProperty->getValue($this->authEntraId);
        $challenge = $challengeProperty->getValue($this->authEntraId);

        // Verify verifier is generated
        $this->assertNotEmpty($verifier);
        $this->assertEquals(128, strlen($verifier));
        $this->assertMatchesRegularExpression('/^[0-9a-zA-Z\-\._~]+$/', $verifier);

        // Verify the challenge is generated correctly
        $this->assertNotEmpty($challenge);
        $expectedChallenge = str_replace(
            '=',
            '',
            strtr(base64_encode(pack('H*', hash('sha256', $verifier))), '+/', '-_'),
        );
        $this->assertEquals($expectedChallenge, $challenge);
    }

    public function testCreateOAuthChallengeWithExistingVerifier(): void
    {
        $reflection = new ReflectionClass($this->authEntraId);
        $method = $reflection->getMethod('createOAuthChallenge');

        $verifierProperty = $reflection->getProperty('oAuthVerifier');

        // Set existing verifier
        $existingVerifier = 'existing_verifier_123';
        $verifierProperty->setValue($this->authEntraId, $existingVerifier);

        $method->invoke($this->authEntraId);

        // Verifier should remain the same
        $this->assertEquals($existingVerifier, $verifierProperty->getValue($this->authEntraId));
    }

    public function testCreateOAuthChallengeWithEmptyStringVerifier(): void
    {
        $reflection = new ReflectionClass($this->authEntraId);
        $method = $reflection->getMethod('createOAuthChallenge');

        $verifierProperty = $reflection->getProperty('oAuthVerifier');

        // Set empty string verifier (should trigger generation)
        $verifierProperty->setValue($this->authEntraId, '');

        $method->invoke($this->authEntraId);

        $verifier = $verifierProperty->getValue($this->authEntraId);
        $this->assertNotEmpty($verifier);
        $this->assertEquals(128, strlen($verifier));
    }

    public function testCreateOAuthChallengeWithZeroStringVerifier(): void
    {
        $reflection = new ReflectionClass($this->authEntraId);
        $method = $reflection->getMethod('createOAuthChallenge');

        $verifierProperty = $reflection->getProperty('oAuthVerifier');

        // Set '0' string verifier (should trigger generation)
        $verifierProperty->setValue($this->authEntraId, '0');

        $method->invoke($this->authEntraId);

        $verifier = $verifierProperty->getValue($this->authEntraId);
        $this->assertNotEmpty($verifier);
        $this->assertNotEquals('0', $verifier);
        $this->assertEquals(128, strlen($verifier));
    }

    public function testConstants(): void
    {
        $reflection = new ReflectionClass(AuthEntraId::class);

        $this->assertEquals('S256', $reflection->getConstant('ENTRAID_CHALLENGE_METHOD'));
        $this->assertEquals(
            'https://login.microsoftonline.com/common/wsfederation?wa=wsignout1.0',
            $reflection->getConstant('ENTRAID_LOGOUT_URL'),
        );
    }
}
