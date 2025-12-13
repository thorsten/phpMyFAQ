<?php

namespace phpMyFAQ\Auth\EntraId;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

const AAD_OAUTH_TENANTID = 'fake_tenant_id';
const AAD_OAUTH_CLIENTID = 'fake_client_id';
const AAD_OAUTH_SECRET = 'fake_secret';
const AAD_OAUTH_SCOPE = 'fake_scope';

#[AllowMockObjectsWithoutExpectations]
class OAuthTest extends TestCase
{
    private HttpClientInterface $mockClient;
    private EntraIdSession $mockSession;
    private OAuth $oAuth;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->mockClient = $this->createMock(HttpClientInterface::class);
        $this->mockSession = $this->createMock(EntraIdSession::class);
        $mockConfiguration = $this->createStub(Configuration::class);

        $this->oAuth = new OAuth($mockConfiguration, $this->mockSession);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws \JsonException
     */
    public function testGetOAuthTokenSuccess(): void
    {
        $mockResponse = $this->createStub(ResponseInterface::class);
        $mockResponse->method('getContent')->willReturn(json_encode([
            'access_token' => 'fake_access_token',
            'id_token' => 'fake_id_token'
        ]));

        $this->mockSession->expects($this->exactly(1))
            ->method('get')
            ->with(EntraIdSession::ENTRA_ID_OAUTH_VERIFIER)
            ->willReturnOnConsecutiveCalls('', 'code_verifier');

        $this->mockClient->expects($this->once())
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
        $mockResponse->method('getContent')->willReturn(json_encode([
            'access_token' => 'new_access_token',
            'refresh_token' => 'new_refresh_token',
            'id_token' => 'new_id_token'
        ]));

        $this->oAuth->setRefreshToken('fake_refresh_token');

        $this->mockClient->expects($this->once())
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


    public function testSetToken(): void
    {
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode(['name' => 'John Doe', 'preferred_username' => 'john@example.com']));
        $signature = 'dummy_signature'; // Signature is not used in this case
        $idToken = $header . '.' . $payload . '.' . $signature;

        $token = new stdClass();
        $token->id_token = $idToken;

        $this->mockSession->expects($this->once())
            ->method('set')
            ->with(EntraIdSession::ENTRA_ID_JWT, $this->stringContains('John Doe'));

        $this->oAuth->setToken($token);

        $this->assertEquals('John Doe', $this->oAuth->getName());
        $this->assertEquals('john@example.com', $this->oAuth->getMail());
    }

    public function testSetRefreshToken(): void
    {
        $refreshToken = 'test_refresh_token';
        $this->oAuth->setRefreshToken($refreshToken);

        // Use reflection to verify the token was set
        $reflection = new ReflectionClass($this->oAuth);
        $property = $reflection->getProperty('refreshToken');

        $this->assertEquals($refreshToken, $property->getValue($this->oAuth));
    }

    public function testGetName(): void
    {
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode(['name' => 'Jane Doe', 'preferred_username' => 'jane@example.com']));
        $signature = 'dummy_signature';
        $idToken = $header . '.' . $payload . '.' . $signature;

        $token = new stdClass();
        $token->id_token = $idToken;

        $this->mockSession->expects($this->once())
            ->method('set')
            ->with(EntraIdSession::ENTRA_ID_JWT, $this->anything());

        $this->oAuth->setToken($token);
        $this->assertEquals('Jane Doe', $this->oAuth->getName());
    }

    public function testGetMail(): void
    {
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode(['name' => 'Test User', 'preferred_username' => 'test@company.com']));
        $signature = 'dummy_signature';
        $idToken = $header . '.' . $payload . '.' . $signature;

        $token = new stdClass();
        $token->id_token = $idToken;

        $this->mockSession->expects($this->once())
            ->method('set')
            ->with(EntraIdSession::ENTRA_ID_JWT, $this->anything());

        $this->oAuth->setToken($token);
        $this->assertEquals('test@company.com', $this->oAuth->getMail());
    }
    public function testSetTokenWithMalformedJWT(): void
    {
        $token = new stdClass();
        $token->id_token = 'invalid-jwt';

        $this->mockSession->expects($this->once())
            ->method('set')
            ->with(EntraIdSession::ENTRA_ID_JWT, '{}');

        $this->oAuth->setToken($token);
        $this->assertEquals('', $this->oAuth->getName());
        $this->assertEquals('', $this->oAuth->getMail());
    }

    public function testSetTokenWithIncompleteJWTParts(): void
    {
        $token = new stdClass();
        $token->id_token = 'header.payload'; // Missing signature part

        $this->mockSession->expects($this->once())
            ->method('set')
            ->with(EntraIdSession::ENTRA_ID_JWT, '{}');

        $this->oAuth->setToken($token);
        $this->assertEquals('', $this->oAuth->getName());
        $this->assertEquals('', $this->oAuth->getMail());
    }

    public function testSetTokenWithInvalidBase64(): void
    {
        $token = new stdClass();
        $token->id_token = 'header.invalid-base64!!!.signature';

        $this->mockSession->expects($this->once())
            ->method('set')
            ->with(EntraIdSession::ENTRA_ID_JWT, '{}');

        $this->oAuth->setToken($token);
        $this->assertEquals('', $this->oAuth->getName());
        $this->assertEquals('', $this->oAuth->getMail());
    }

    public function testSetTokenWithMalformedJSON(): void
    {
        $header = base64_encode('valid-header');
        $payload = base64_encode('invalid-json{malformed}');
        $signature = 'dummy_signature';
        $token = new stdClass();
        $token->id_token = $header . '.' . $payload . '.' . $signature;

        $this->mockSession->expects($this->once())
            ->method('set')
            ->with(EntraIdSession::ENTRA_ID_JWT, '{}');

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

        $this->mockSession->expects($this->once())
            ->method('set');

        $this->oAuth->setToken($token);
        $this->assertEquals('', $this->oAuth->getName());
    }

    public function testGetMailWithEmptyToken(): void
    {
        $token = new stdClass();
        $token->id_token = 'invalid';

        $this->mockSession->expects($this->once())
            ->method('set');

        $this->oAuth->setToken($token);
        $this->assertEquals('', $this->oAuth->getMail());
    }

    public function testSetTokenWithValidJWTButMissingFields(): void
    {
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode(['sub' => '12345'])); // Missing name and preferred_username
        $signature = 'dummy_signature';
        $idToken = $header . '.' . $payload . '.' . $signature;

        $token = new stdClass();
        $token->id_token = $idToken;

        $this->mockSession->expects($this->once())
            ->method('set')
            ->with(EntraIdSession::ENTRA_ID_JWT, $this->anything());

        $this->oAuth->setToken($token);
        $this->assertEquals('', $this->oAuth->getName());
        $this->assertEquals('', $this->oAuth->getMail());
    }
}
