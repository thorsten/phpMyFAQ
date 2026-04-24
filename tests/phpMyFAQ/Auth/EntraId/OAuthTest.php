<?php

namespace phpMyFAQ\Auth\EntraId;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

const AAD_OAUTH_TENANTID = 'fake_tenant_id';

const AAD_OAUTH_CLIENTID = 'fake_client_id';

const AAD_OAUTH_SECRET = 'fake_secret';

const AAD_OAUTH_SCOPE = 'fake_scope';

#[AllowMockObjectsWithoutExpectations]
class OAuthTest extends TestCase
{
    private HttpClientInterface $mockClient;
    private EntraIdSession $mockSession;
    private JwksProvider $mockJwksProvider;
    private OAuth $oAuth;

    private static string $privateKey;
    private static string $publicKey;
    private const string KID = 'test-kid';

    public static function setUpBeforeClass(): void
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($resource, $privateKey);
        $details = openssl_pkey_get_details($resource);

        self::$privateKey = $privateKey;
        self::$publicKey = $details['key'];
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->mockClient = $this->createMock(HttpClientInterface::class);
        $this->mockSession = $this->createMock(EntraIdSession::class);
        $this->mockJwksProvider = $this->createMock(JwksProvider::class);
        $mockConfiguration = $this->createStub(Configuration::class);

        $this->mockJwksProvider
            ->method('getKeys')
            ->willReturn([self::KID => new Key(self::$publicKey, 'RS256')]);

        $this->oAuth = new OAuth($mockConfiguration, $this->mockSession, $this->mockJwksProvider);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function signedIdToken(array $overrides = []): string
    {
        $payload = array_merge([
            'iss' => 'https://login.microsoftonline.com/' . AAD_OAUTH_TENANTID . '/v2.0',
            'aud' => AAD_OAUTH_CLIENTID,
            'sub' => 'subject-id',
            'iat' => time() - 60,
            'nbf' => time() - 60,
            'exp' => time() + 3600,
            'name' => 'John Doe',
            'preferred_username' => 'john@example.com',
        ], $overrides);

        return JWT::encode($payload, self::$privateKey, 'RS256', self::KID);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws \JsonException
     */
    public function testGetOAuthTokenSuccess(): void
    {
        $mockResponse = $this->createStub(ResponseInterface::class);
        $mockResponse
            ->method('getContent')
            ->willReturn(json_encode([
                'access_token' => 'fake_access_token',
                'id_token' => 'fake_id_token',
            ]));

        $this->mockSession
            ->expects($this->exactly(1))
            ->method('get')
            ->with(EntraIdSession::ENTRA_ID_OAUTH_VERIFIER)
            ->willReturnOnConsecutiveCalls('', 'code_verifier');

        $this->mockClient
            ->expects($this->once())
            ->method('request')
            ->with('POST', $this->stringContains('microsoftonline.com'))
            ->willReturn($mockResponse);

        $reflection = new ReflectionClass($this->oAuth);
        $clientProperty = $reflection->getProperty('httpClient');
        $clientProperty->setValue($this->oAuth, $this->mockClient);

        $result = $this->oAuth->getOAuthToken('authorization_code');

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals('fake_access_token', $result->access_token);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws \JsonException
     */
    public function testRefreshTokenSuccess(): void
    {
        $mockResponse = $this->createStub(ResponseInterface::class);
        $mockResponse
            ->method('getContent')
            ->willReturn(json_encode([
                'access_token' => 'new_access_token',
                'refresh_token' => 'new_refresh_token',
                'id_token' => 'new_id_token',
            ]));

        $this->oAuth->setRefreshToken('fake_refresh_token');

        $this->mockClient
            ->expects($this->once())
            ->method('request')
            ->with('POST', $this->stringContains('microsoftonline.com'))
            ->willReturn($mockResponse);

        $reflection = new ReflectionClass($this->oAuth);
        $clientProperty = $reflection->getProperty('httpClient');
        $clientProperty->setValue($this->oAuth, $this->mockClient);

        $result = $this->oAuth->refreshToken();

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals('new_access_token', $result->access_token);
        $this->assertEquals('new_refresh_token', $result->refresh_token);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function testGetOAuthTokenThrowsRuntimeExceptionForJsonErrorResponse(): void
    {
        $mockResponse = $this->createStub(ResponseInterface::class);
        $mockResponse
            ->method('getContent')
            ->willReturn(json_encode([
                'error' => 'invalid_grant',
                'error_description' => 'Authorization code expired',
            ], JSON_THROW_ON_ERROR));
        $mockResponse->method('getStatusCode')->willReturn(400);

        $this->mockSession->method('getCookie')->willReturn('cookie-verifier');
        $this->mockSession->method('get')->willReturn('');

        $this->mockClient->expects($this->once())->method('request')->willReturn($mockResponse);

        $reflection = new ReflectionClass($this->oAuth);
        $clientProperty = $reflection->getProperty('httpClient');
        $clientProperty->setValue($this->oAuth, $this->mockClient);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OAuth token exchange failed (invalid_grant): Authorization code expired');

        $this->oAuth->getOAuthToken('authorization_code');
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function testGetOAuthTokenThrowsRuntimeExceptionForNonJsonErrorResponse(): void
    {
        $mockResponse = $this->createStub(ResponseInterface::class);
        $mockResponse->method('getContent')->willReturn('gateway timeout');
        $mockResponse->method('getStatusCode')->willReturn(504);

        $this->mockSession->method('getCookie')->willReturn('cookie-verifier');
        $this->mockSession->method('get')->willReturn('');

        $this->mockClient->expects($this->once())->method('request')->willReturn($mockResponse);

        $reflection = new ReflectionClass($this->oAuth);
        $clientProperty = $reflection->getProperty('httpClient');
        $clientProperty->setValue($this->oAuth, $this->mockClient);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OAuth token exchange failed: gateway timeout');

        $this->oAuth->getOAuthToken('authorization_code');
    }

    public function testSetToken(): void
    {
        $token = new stdClass();
        $token->id_token = $this->signedIdToken();

        $this->mockSession
            ->expects($this->once())
            ->method('set')
            ->with(EntraIdSession::ENTRA_ID_JWT, $this->stringContains('John Doe'));

        $this->oAuth->setToken($token);

        $this->assertEquals('John Doe', $this->oAuth->getName());
        $this->assertEquals('john@example.com', $this->oAuth->getMail());
    }

    public function testSetTokenRejectsUnsignedToken(): void
    {
        $header = rtrim(strtr(base64_encode(json_encode(['alg' => 'none', 'typ' => 'JWT'])), '+/', '-_'), '=');
        $payload = rtrim(strtr(base64_encode(json_encode([
            'iss' => 'https://login.microsoftonline.com/' . AAD_OAUTH_TENANTID . '/v2.0',
            'aud' => AAD_OAUTH_CLIENTID,
            'preferred_username' => 'attacker@example.com',
            'exp' => time() + 3600,
        ])), '+/', '-_'), '=');

        $token = new stdClass();
        $token->id_token = $header . '.' . $payload . '.';

        $this->mockSession->expects($this->once())->method('set')->with(EntraIdSession::ENTRA_ID_JWT, '{}');

        $this->oAuth->setToken($token);

        $this->assertEquals('', $this->oAuth->getMail());
    }

    public function testSetTokenRejectsInvalidSignature(): void
    {
        $otherKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($otherKey, $otherPrivate);

        $payload = [
            'iss' => 'https://login.microsoftonline.com/' . AAD_OAUTH_TENANTID . '/v2.0',
            'aud' => AAD_OAUTH_CLIENTID,
            'preferred_username' => 'attacker@example.com',
            'exp' => time() + 3600,
            'iat' => time() - 60,
            'nbf' => time() - 60,
        ];
        $forged = JWT::encode($payload, $otherPrivate, 'RS256', self::KID);

        $token = new stdClass();
        $token->id_token = $forged;

        $this->mockSession->expects($this->once())->method('set')->with(EntraIdSession::ENTRA_ID_JWT, '{}');

        $this->oAuth->setToken($token);

        $this->assertEquals('', $this->oAuth->getMail());
    }

    public function testSetTokenRejectsWrongAudience(): void
    {
        $token = new stdClass();
        $token->id_token = $this->signedIdToken(['aud' => 'other-client-id']);

        $this->mockSession->expects($this->once())->method('set')->with(EntraIdSession::ENTRA_ID_JWT, '{}');

        $this->oAuth->setToken($token);

        $this->assertEquals('', $this->oAuth->getMail());
    }

    public function testSetTokenRejectsWrongIssuer(): void
    {
        $token = new stdClass();
        $token->id_token = $this->signedIdToken(['iss' => 'https://evil.example.com/']);

        $this->mockSession->expects($this->once())->method('set')->with(EntraIdSession::ENTRA_ID_JWT, '{}');

        $this->oAuth->setToken($token);

        $this->assertEquals('', $this->oAuth->getMail());
    }

    public function testSetTokenRejectsExpiredToken(): void
    {
        $token = new stdClass();
        $token->id_token = $this->signedIdToken([
            'iat' => time() - 7200,
            'nbf' => time() - 7200,
            'exp' => time() - 3600,
        ]);

        $this->mockSession->expects($this->once())->method('set')->with(EntraIdSession::ENTRA_ID_JWT, '{}');

        $this->oAuth->setToken($token);

        $this->assertEquals('', $this->oAuth->getMail());
    }

    public function testSetTokenWithoutJwksProviderRejects(): void
    {
        $mockConfiguration = $this->createStub(Configuration::class);
        $oAuth = new OAuth($mockConfiguration, $this->mockSession);

        $token = new stdClass();
        $token->id_token = $this->signedIdToken();

        $this->mockSession->expects($this->once())->method('set')->with(EntraIdSession::ENTRA_ID_JWT, '{}');

        $oAuth->setToken($token);

        $this->assertEquals('', $oAuth->getMail());
    }

    public function testSetRefreshToken(): void
    {
        $refreshToken = 'test_refresh_token';
        $this->oAuth->setRefreshToken($refreshToken);

        $reflection = new ReflectionClass($this->oAuth);
        $property = $reflection->getProperty('refreshToken');

        $this->assertEquals($refreshToken, $property->getValue($this->oAuth));
    }

    public function testErrorMessageReturnsInput(): void
    {
        $this->assertSame('entra-id failed', $this->oAuth->errorMessage('entra-id failed'));
    }

    public function testGetTokenReturnsPreviouslySetDecodedToken(): void
    {
        $token = new stdClass();
        $token->id_token = $this->signedIdToken(['name' => 'Token User', 'preferred_username' => 'token@example.com']);

        $this->mockSession
            ->expects($this->once())
            ->method('set')
            ->with(EntraIdSession::ENTRA_ID_JWT, $this->stringContains('Token User'));

        $this->oAuth->setToken($token);

        $this->assertInstanceOf(stdClass::class, $this->oAuth->getToken());
        $this->assertSame('Token User', $this->oAuth->getToken()->name);
    }

    public function testGetEntraIdSessionReturnsInjectedSession(): void
    {
        $this->assertSame($this->mockSession, $this->oAuth->getEntraIdSession());
    }

    public function testGetRefreshTokenReturnsPreviouslySetValue(): void
    {
        $this->oAuth->setRefreshToken('refresh-token-value');

        $this->assertSame('refresh-token-value', $this->oAuth->getRefreshToken());
    }

    public function testSetAndGetAccessToken(): void
    {
        $result = $this->oAuth->setAccessToken('access-token-value');

        $this->assertSame($this->oAuth, $result);
        $this->assertSame('access-token-value', $this->oAuth->getAccessToken());
    }

    public function testGetName(): void
    {
        $token = new stdClass();
        $token->id_token = $this->signedIdToken(['name' => 'Jane Doe', 'preferred_username' => 'jane@example.com']);

        $this->mockSession
            ->expects($this->once())
            ->method('set')
            ->with(EntraIdSession::ENTRA_ID_JWT, $this->anything());

        $this->oAuth->setToken($token);
        $this->assertEquals('Jane Doe', $this->oAuth->getName());
    }

    public function testGetMail(): void
    {
        $token = new stdClass();
        $token->id_token = $this->signedIdToken(['name' => 'Test User', 'preferred_username' => 'test@company.com']);

        $this->mockSession
            ->expects($this->once())
            ->method('set')
            ->with(EntraIdSession::ENTRA_ID_JWT, $this->anything());

        $this->oAuth->setToken($token);
        $this->assertEquals('test@company.com', $this->oAuth->getMail());
    }

    public function testSetTokenWithMalformedJWT(): void
    {
        $token = new stdClass();
        $token->id_token = 'invalid-jwt';

        $this->mockSession->expects($this->once())->method('set')->with(EntraIdSession::ENTRA_ID_JWT, '{}');

        $this->oAuth->setToken($token);
        $this->assertEquals('', $this->oAuth->getName());
        $this->assertEquals('', $this->oAuth->getMail());
    }

    public function testSetTokenWithIncompleteJWTParts(): void
    {
        $token = new stdClass();
        $token->id_token = 'header.payload';

        $this->mockSession->expects($this->once())->method('set')->with(EntraIdSession::ENTRA_ID_JWT, '{}');

        $this->oAuth->setToken($token);
        $this->assertEquals('', $this->oAuth->getName());
        $this->assertEquals('', $this->oAuth->getMail());
    }

    public function testSetTokenWithInvalidBase64(): void
    {
        $token = new stdClass();
        $token->id_token = 'header.invalid-base64!!!.signature';

        $this->mockSession->expects($this->once())->method('set')->with(EntraIdSession::ENTRA_ID_JWT, '{}');

        $this->oAuth->setToken($token);
        $this->assertEquals('', $this->oAuth->getName());
        $this->assertEquals('', $this->oAuth->getMail());
    }

    public function testSetTokenWithMalformedJSON(): void
    {
        $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT', 'kid' => self::KID]));
        $payload = base64_encode('invalid-json{malformed}');
        $signature = 'dummy_signature';
        $token = new stdClass();
        $token->id_token = $header . '.' . $payload . '.' . $signature;

        $this->mockSession->expects($this->once())->method('set')->with(EntraIdSession::ENTRA_ID_JWT, '{}');

        $this->oAuth->setToken($token);
        $this->assertEquals('', $this->oAuth->getName());
        $this->assertEquals('', $this->oAuth->getMail());
    }

    public function testGetNameWhenNotSet(): void
    {
        $this->assertEquals('', $this->oAuth->getName());
    }

    public function testGetMailWhenNotSet(): void
    {
        $this->assertEquals('', $this->oAuth->getMail());
    }

    public function testGetNameWithEmptyToken(): void
    {
        $token = new stdClass();
        $token->id_token = 'invalid';

        $this->mockSession->expects($this->once())->method('set');

        $this->oAuth->setToken($token);
        $this->assertEquals('', $this->oAuth->getName());
    }

    public function testGetMailWithEmptyToken(): void
    {
        $token = new stdClass();
        $token->id_token = 'invalid';

        $this->mockSession->expects($this->once())->method('set');

        $this->oAuth->setToken($token);
        $this->assertEquals('', $this->oAuth->getMail());
    }

    public function testSetTokenWithValidJWTButMissingFields(): void
    {
        $token = new stdClass();
        $token->id_token = $this->signedIdToken(['name' => null, 'preferred_username' => null]);

        $this->mockSession
            ->expects($this->once())
            ->method('set')
            ->with(EntraIdSession::ENTRA_ID_JWT, $this->anything());

        $this->oAuth->setToken($token);
        $this->assertEquals('', $this->oAuth->getName());
        $this->assertEquals('', $this->oAuth->getMail());
    }
}
