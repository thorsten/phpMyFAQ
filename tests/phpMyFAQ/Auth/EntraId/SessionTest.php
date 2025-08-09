<?php

namespace phpMyFAQ\Auth\EntraId;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;

class SessionTest extends TestCase
{
    private Configuration $configurationMock;
    private SymfonySession $sessionMock;
    private EntraIdSession $session;

    protected function setUp(): void
    {
        $this->configurationMock = $this->createMock(Configuration::class);
        $this->sessionMock = $this->createMock(SymfonySession::class);
        $this->session = new EntraIdSession($this->configurationMock, $this->sessionMock);
    }

    public function testCreateCurrentSessionKey(): void
    {
        $this->session->createCurrentSessionKey();
        $key = $this->session->getCurrentSessionKey();
        $this->assertNotNull($key);
        $this->assertIsString($key);
        // UUID v4 format check
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $key
        );
    }

    public function testGetCurrentSessionKey(): void
    {
        $this->session->createCurrentSessionKey();
        $key1 = $this->session->getCurrentSessionKey();
        $key2 = $this->session->getCurrentSessionKey();
        $this->assertEquals($key1, $key2);
        $this->assertIsString($key1);
    }

    /**
     * @throws \Exception
     */
    public function testSetCurrentSessionKey(): void
    {
        $this->sessionMock->expects($this->once())
            ->method('set')
            ->with(
                EntraIdSession::ENTRA_ID_SESSION_KEY,
                $this->isString()
            );

        $result = $this->session->setCurrentSessionKey();
        $this->assertInstanceOf(EntraIdSession::class, $result);
        $this->assertNotNull($this->session->getCurrentSessionKey());
    }

    /**
     * @throws \Exception
     */
    public function testSetCurrentSessionKeyIsChainable(): void
    {
        $this->sessionMock->expects($this->once())
            ->method('set')
            ->with(
                EntraIdSession::ENTRA_ID_SESSION_KEY,
                $this->isString()
            );

        $result = $this->session->setCurrentSessionKey();
        $this->assertSame($this->session, $result);
    }

    public function testConstants(): void
    {
        $this->assertEquals('pmf-entra-id-session-key', EntraIdSession::ENTRA_ID_SESSION_KEY);
        $this->assertEquals('pmf-entra-id-oauth-verifier', EntraIdSession::ENTRA_ID_OAUTH_VERIFIER);
        $this->assertEquals('pmf-entra-id-jwt', EntraIdSession::ENTRA_ID_JWT);
    }

    public function testSetCookieWithDefaults(): void
    {
        $this->configurationMock->expects($this->once())
            ->method('getDefaultUrl')
            ->willReturn('https://example.com');

        $this->session->setCookie('test-cookie', 'test-value');
        $this->assertTrue(true);
    }

    public function testSetCookieWithCustomTimeout(): void
    {
        $this->configurationMock->expects($this->once())
            ->method('getDefaultUrl')
            ->willReturn('https://example.com');

        $this->session->setCookie('test-cookie', 'test-value', 7200, false);
        $this->assertTrue(true);
    }

    public function testSetCookieWithNullSessionId(): void
    {
        $this->configurationMock->expects($this->once())
            ->method('getDefaultUrl')
            ->willReturn('https://example.com');

        $this->session->setCookie('test-cookie', null);
        $this->assertTrue(true);
    }

    public function testGetCookie(): void
    {
        $_COOKIE['test-cookie'] = 'test-value';

        $result = $this->session->getCookie('test-cookie');
        $this->assertEquals('test-value', $result);

        unset($_COOKIE['test-cookie']);
    }

    public function testGetCookieNonExistent(): void
    {
        $result = $this->session->getCookie('non-existent-cookie');
        $this->assertEquals('', $result);
    }

    public function testUniqueSessionKeys(): void
    {
        $session1 = new EntraIdSession($this->configurationMock, $this->sessionMock);
        $session2 = new EntraIdSession($this->configurationMock, $this->sessionMock);

        $key1 = $session1->getCurrentSessionKey();
        $key2 = $session2->getCurrentSessionKey();

        $this->assertNotEquals($key1, $key2);
    }
}
