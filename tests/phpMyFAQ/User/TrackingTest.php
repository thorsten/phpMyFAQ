<?php

namespace phpMyFAQ\User;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;

class TrackingTest extends TestCase
{
    private Configuration $configurationMock;
    private Request $requestMock;
    private UserSession $userSessionMock;
    private DatabaseDriver $databaseMock;
    private Tracking $tracking;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->configurationMock = $this->createMock(Configuration::class);
        $this->requestMock = $this->createMock(Request::class);
        $this->requestMock->headers = new HeaderBag([
            'X-Forwarded-For' => '192.168.1.1'
        ]);
        $this->requestMock->method('getClientIp')->willReturn('127.0.0.1');
        $this->userSessionMock = $this->createMock(UserSession::class);
        $this->databaseMock = $this->createMock(DatabaseDriver::class);

        $this->configurationMock->method('getDb')->willReturn($this->databaseMock);
        $this->tracking = Tracking::getInstance($this->configurationMock, $this->requestMock, $this->userSessionMock);
    }

    public function testInitializeSessionId(): void
    {
        $this->requestMock->query = new InputBag([
            UserSession::KEY_NAME_SESSION_ID => 123,
            UserSession::COOKIE_NAME_SESSION_ID => 456
        ]);

        $reflection = new ReflectionClass($this->tracking);
        $method = $reflection->getMethod('initializeSessionId');
        $method->setAccessible(true);
        $method->invoke($this->tracking);

        $property = $reflection->getProperty('currentSessionId');
        $property->setAccessible(true);
        $this->assertEquals(123, $property->getValue($this->tracking));
    }

    public function testGetCookieId(): void
    {
        $this->requestMock->query = new InputBag([
            UserSession::KEY_NAME_SESSION_ID => 123,
            UserSession::COOKIE_NAME_SESSION_ID => 456
        ]);

        $reflection = new ReflectionClass($this->tracking);
        $method = $reflection->getMethod('getCookieId');
        $method->setAccessible(true);
        $result = $method->invoke($this->tracking);

        $this->assertEquals(456, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCountBots(): void
    {
        $this->configurationMock->method('get')->with('main.botIgnoreList')->willReturn('bot1,bot2');
        $this->requestMock->headers = new HeaderBag([
            'user-agent' => 'bot1'
        ]);

        $reflection = new ReflectionClass($this->tracking);
        $method = $reflection->getMethod('countBots');
        $method->setAccessible(true);
        $result = $method->invoke($this->tracking);

        $this->assertEquals(1, $result);
    }
}
