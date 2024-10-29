<?php

namespace phpMyFAQ\Auth\Azure;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use phpMyFAQ\Configuration;
use phpMyFAQ\Session;
use stdClass;

const AAD_OAUTH_TENANTID = 'fake_tenant_id';
const AAD_OAUTH_CLIENTID = 'fake_client_id';
const AAD_OAUTH_SECRET = 'fake_secret';
const AAD_OAUTH_SCOPE = 'fake_scope';

class OAuthTest extends TestCase
{
    private HttpClientInterface $mockClient;
    private Configuration $mockConfiguration;
    private Session $mockSession;
    private OAuth $oAuth;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->mockClient = $this->createMock(HttpClientInterface::class);
        $this->mockConfiguration = $this->createMock(Configuration::class);
        $this->mockSession = $this->createMock(Session::class);

        $this->oAuth = new OAuth($this->mockConfiguration, $this->mockSession);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws \JsonException
     */
    public function testGetOAuthTokenSuccess(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getContent')->willReturn(json_encode([
            'access_token' => 'fake_access_token',
            'id_token' => 'fake_id_token'
        ]));

        $this->mockSession->expects($this->exactly(1))
            ->method('get')
            ->with(Session::ENTRA_ID_OAUTH_VERIFIER)
            ->willReturnOnConsecutiveCalls('', 'code_verifier');

        $this->mockClient->expects($this->once())
            ->method('request')
            ->with('POST', $this->stringContains('microsoftonline.com'))
            ->willReturn($mockResponse);

        $reflection = new \ReflectionClass($this->oAuth);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
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
        $mockResponse = $this->createMock(ResponseInterface::class);
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

        $reflection = new \ReflectionClass($this->oAuth);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
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
            ->with(Session::ENTRA_ID_JWT, $this->stringContains('John Doe'));

        $this->oAuth->setToken($token);

        $this->assertEquals('John Doe', $this->oAuth->getName());
        $this->assertEquals('john@example.com', $this->oAuth->getMail());
    }

    public function testGetRefreshToken(): void
    {
        $this->oAuth->setRefreshToken('test_refresh_token');
        $this->assertEquals('test_refresh_token', $this->oAuth->getRefreshToken());
    }

    public function testGetAccessToken(): void
    {
        $this->oAuth->setAccessToken('test_access_token');
        $this->assertEquals('test_access_token', $this->oAuth->getAccessToken());
    }

    public function testErrorMessage(): void
    {
        $this->assertEquals('Error occurred', $this->oAuth->errorMessage('Error occurred'));
    }
}
