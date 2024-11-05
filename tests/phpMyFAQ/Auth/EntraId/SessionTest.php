<?php

namespace phpMyFAQ\Auth\EntraId;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;

class SessionTest extends TestCase
{
    private Configuration $configurationMock;
    private SymfonySession $sessionMock;
    private Session $session;

    protected function setUp(): void
    {
        $this->configurationMock = $this->createMock(Configuration::class);
        $this->sessionMock = $this->createMock(SymfonySession::class);
        $this->session = new Session($this->configurationMock, $this->sessionMock);
    }

    public function testCreateCurrentSessionKey(): void
    {
        $this->session->createCurrentSessionKey();
        $this->assertNotNull($this->session->getCurrentSessionKey());
    }

    public function testGetCurrentSessionKey(): void
    {
        $this->session->createCurrentSessionKey();
        $this->assertEquals($this->session->getCurrentSessionKey(), $this->session->getCurrentSessionKey());
    }

    public function testSetCurrentSessionKey(): void
    {
        $this->session->setCurrentSessionKey();
        $this->assertNotNull($this->session->getCurrentSessionKey());
    }

    public function testUuid(): void
    {
        $uuid = $this->session->uuid();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid
        );
    }
}
