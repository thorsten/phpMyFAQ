<?php

namespace phpMyFAQ\Push;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class WebPushServiceTest extends TestCase
{
    private Configuration&MockObject $configuration;

    private PushSubscriptionRepository&MockObject $repository;

    private WebPushService $service;

    protected function setUp(): void
    {
        $this->configuration = $this->createMock(Configuration::class);
        $this->repository = $this->createMock(PushSubscriptionRepository::class);
        $this->service = new WebPushService($this->configuration, $this->repository);
    }

    public function testIsEnabledReturnsTrueWhenConfigured(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['push.enableWebPush',   'true'],
                ['push.vapidPublicKey',  'BNcR...publicKey'],
                ['push.vapidPrivateKey', 'privateKeyValue'],
            ]);

        $this->assertTrue($this->service->isEnabled());
    }

    public function testIsEnabledReturnsFalseWhenDisabled(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['push.enableWebPush',   'false'],
                ['push.vapidPublicKey',  'BNcR...publicKey'],
                ['push.vapidPrivateKey', 'privateKeyValue'],
            ]);

        $this->assertFalse($this->service->isEnabled());
    }

    public function testIsEnabledReturnsFalseWithoutPublicKey(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['push.enableWebPush',   'true'],
                ['push.vapidPublicKey',  ''],
                ['push.vapidPrivateKey', 'privateKeyValue'],
            ]);

        $this->assertFalse($this->service->isEnabled());
    }

    public function testIsEnabledReturnsFalseWithoutPrivateKey(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['push.enableWebPush',   'true'],
                ['push.vapidPublicKey',  'BNcR...publicKey'],
                ['push.vapidPrivateKey', ''],
            ]);

        $this->assertFalse($this->service->isEnabled());
    }

    public function testGetVapidPublicKey(): void
    {
        $this->configuration
            ->method('get')
            ->with('push.vapidPublicKey')
            ->willReturn('BNcR...publicKey');

        $this->assertEquals('BNcR...publicKey', $this->service->getVapidPublicKey());
    }

    public function testGetVapidPublicKeyReturnsEmptyStringWhenNull(): void
    {
        $this->configuration
            ->method('get')
            ->with('push.vapidPublicKey')
            ->willReturn(null);

        $this->assertEquals('', $this->service->getVapidPublicKey());
    }

    public function testGenerateVapidKeysReturnsValidKeys(): void
    {
        $keys = WebPushService::generateVapidKeys();

        $this->assertArrayHasKey('publicKey', $keys);
        $this->assertArrayHasKey('privateKey', $keys);
        $this->assertNotEmpty($keys['publicKey']);
        $this->assertNotEmpty($keys['privateKey']);
    }

    public function testSendToAllSkipsWhenDisabled(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['push.enableWebPush',   'false'],
                ['push.vapidPublicKey',  ''],
                ['push.vapidPrivateKey', ''],
            ]);

        $this->repository->expects($this->never())->method('getAll');

        $this->service->sendToAll('Test', 'Body');
    }

    public function testSendToAllSkipsWhenNoSubscriptions(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['push.enableWebPush',   'true'],
                ['push.vapidPublicKey',  'BNcR...publicKey'],
                ['push.vapidPrivateKey', 'privateKeyValue'],
            ]);

        $this->repository->method('getAll')->willReturn([]);

        $this->service->sendToAll('Test', 'Body');

        $this->assertTrue(true); // No exception thrown
    }
}
