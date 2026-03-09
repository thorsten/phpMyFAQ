<?php

namespace phpMyFAQ\Push;

use Monolog\Logger;
use phpMyFAQ\Configuration;
use phpMyFAQ\Entity\PushSubscriptionEntity;
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
        $warnings = [];
        set_error_handler(static function (int $severity, string $message) use (&$warnings): bool {
            $warnings[] = $message;
            return true;
        });

        try {
            $keys = WebPushService::generateVapidKeys();
        } finally {
            restore_error_handler();
        }

        if ($warnings !== []) {
            $this->markTestSkipped(
                'VAPID key generation is not available in this environment: ' . implode(' | ', $warnings),
            );
        }

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

    public function testSendToUserSkipsWhenDisabled(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['push.enableWebPush',   'false'],
                ['push.vapidPublicKey',  ''],
                ['push.vapidPrivateKey', ''],
            ]);

        $this->repository->expects($this->never())->method('getByUserId');

        $this->service->sendToUser(1, 'Test', 'Body');
    }

    public function testSendToUserSkipsWhenNoSubscriptions(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['push.enableWebPush',   'true'],
                ['push.vapidPublicKey',  'public'],
                ['push.vapidPrivateKey', 'private'],
            ]);

        $this->repository
            ->expects($this->once())
            ->method('getByUserId')
            ->with(7)
            ->willReturn([]);

        $this->service->sendToUser(7, 'Test', 'Body');
        $this->assertTrue(true);
    }

    public function testSendToUsersSkipsWhenUserIdsAreEmpty(): void
    {
        $this->repository->expects($this->never())->method('getByUserIds');

        $this->service->sendToUsers([], 'Test', 'Body');
    }

    public function testSendToUsersSkipsWhenNoSubscriptions(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['push.enableWebPush',   'true'],
                ['push.vapidPublicKey',  'public'],
                ['push.vapidPrivateKey', 'private'],
            ]);

        $this->repository
            ->expects($this->once())
            ->method('getByUserIds')
            ->with([1, 2])
            ->willReturn([]);

        $this->service->sendToUsers([1, 2], 'Test', 'Body');
        $this->assertTrue(true);
    }

    public function testSendToAllLogsErrorWhenPayloadEncodingFails(): void
    {
        $logger = $this->createMock(Logger::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with($this->stringStartsWith('Web Push notification failed:'));

        $subscription = new PushSubscriptionEntity()
            ->setId(1)
            ->setUserId(4)
            ->setEndpoint('https://push.example.test/subscription')
            ->setEndpointHash('hash')
            ->setPublicKey('public')
            ->setAuthToken('auth')
            ->setContentEncoding('aesgcm')
            ->setCreatedAt(new \DateTimeImmutable('2026-03-09 12:00:00'));

        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['push.enableWebPush',   'true'],
                ['push.vapidPublicKey',  'public'],
                ['push.vapidPrivateKey', 'private'],
                ['push.vapidSubject',    null],
            ]);
        $this->configuration->method('getAdminEmail')->willReturn('admin@example.com');
        $this->configuration->method('getDefaultUrl')->willReturn('https://localhost/');
        $this->configuration->method('getLogger')->willReturn($logger);

        $this->repository
            ->expects($this->once())
            ->method('getAll')
            ->willReturn([$subscription]);

        $this->service->sendToAll("\xB1\x31", 'Body', '/faq', 'tag');
    }

    public function testSendToUserLogsErrorWhenPayloadEncodingFails(): void
    {
        $logger = $this->createMock(Logger::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with($this->stringStartsWith('Web Push notification failed:'));

        $subscription = new PushSubscriptionEntity()
            ->setId(2)
            ->setUserId(9)
            ->setEndpoint('https://push.example.test/user')
            ->setEndpointHash('hash-user')
            ->setPublicKey('public')
            ->setAuthToken('auth')
            ->setContentEncoding(null)
            ->setCreatedAt(new \DateTimeImmutable('2026-03-09 12:00:00'));

        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['push.enableWebPush',   true],
                ['push.vapidPublicKey',  'public'],
                ['push.vapidPrivateKey', 'private'],
                ['push.vapidSubject',    'mailto:subject@example.com'],
            ]);
        $this->configuration->method('getAdminEmail')->willReturn('admin@example.com');
        $this->configuration->method('getDefaultUrl')->willReturn('https://localhost/');
        $this->configuration->method('getLogger')->willReturn($logger);

        $this->repository
            ->expects($this->once())
            ->method('getByUserId')
            ->with(9)
            ->willReturn([$subscription]);

        $this->service->sendToUser(9, "\xB1\x31", 'Body');
    }

    public function testSendToUsersLogsErrorWhenPayloadEncodingFails(): void
    {
        $logger = $this->createMock(Logger::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with($this->stringStartsWith('Web Push notification failed:'));

        $subscription = new PushSubscriptionEntity()
            ->setId(3)
            ->setUserId(10)
            ->setEndpoint('https://push.example.test/users')
            ->setEndpointHash('hash-users')
            ->setPublicKey('public')
            ->setAuthToken('auth')
            ->setContentEncoding('aes128gcm')
            ->setCreatedAt(new \DateTimeImmutable('2026-03-09 12:00:00'));

        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['push.enableWebPush',   'true'],
                ['push.vapidPublicKey',  'public'],
                ['push.vapidPrivateKey', 'private'],
                ['push.vapidSubject',    null],
            ]);
        $this->configuration->method('getAdminEmail')->willReturn('admin@example.com');
        $this->configuration->method('getDefaultUrl')->willReturn('https://localhost/');
        $this->configuration->method('getLogger')->willReturn($logger);

        $this->repository
            ->expects($this->once())
            ->method('getByUserIds')
            ->with([10, 11])
            ->willReturn([$subscription]);

        $this->service->sendToUsers([10, 11], "\xB1\x31", 'Body');
    }
}
