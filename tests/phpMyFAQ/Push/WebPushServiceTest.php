<?php

namespace phpMyFAQ\Push;

use GuzzleHttp\Client;
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
            ->expects($this->once())
            ->method('get')
            ->with('push.vapidPublicKey')
            ->willReturn('BNcR...publicKey');

        $this->assertEquals('BNcR...publicKey', $this->service->getVapidPublicKey());
    }

    public function testGetVapidPublicKeyReturnsEmptyStringWhenNull(): void
    {
        $this->configuration->expects($this->once())->method('get')->with('push.vapidPublicKey')->willReturn(null);

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

        $this->repository->expects($this->once())->method('getByUserId')->with(7)->willReturn([]);

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

        $this->repository->expects($this->once())->method('getByUserIds')->with([1, 2])->willReturn([]);

        $this->service->sendToUsers([1, 2], 'Test', 'Body');
        $this->assertTrue(true);
    }

    /**
     * Endpoints are user-supplied: the client must not follow redirects into the internal
     * network and must not hang on an unresponsive host.
     */
    public function testHttpClientNeverFollowsRedirectsAndHasTimeouts(): void
    {
        $method = new \ReflectionMethod($this->service, 'createHttpClient');
        $client = $method->invoke($this->service);

        $this->assertInstanceOf(Client::class, $client);
        $this->assertFalse($client->getConfig('allow_redirects'));
        $this->assertFalse($client->getConfig('http_errors'));
        $this->assertGreaterThan(0, $client->getConfig('timeout'));
        $this->assertGreaterThan(0, $client->getConfig('connect_timeout'));
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

        $this->repository->expects($this->once())->method('getAll')->willReturn([$subscription]);

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

        $this->repository->expects($this->once())->method('getByUserId')->with(9)->willReturn([$subscription]);

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

        $this->repository->expects($this->once())->method('getByUserIds')->with([10, 11])->willReturn([$subscription]);

        $this->service->sendToUsers([10, 11], "\xB1\x31", 'Body');
    }

    public function testSendToAllLogsErrorWhenSubscriptionContentEncodingIsUnsupported(): void
    {
        $keys = $this->generateVapidKeysOrSkip();

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
            ->setContentEncoding('unsupported-encoding')
            ->setCreatedAt(new \DateTimeImmutable('2026-03-09 12:00:00'));

        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['push.enableWebPush', 'true'],
                ['push.vapidPublicKey', $keys['publicKey']],
                ['push.vapidPrivateKey', $keys['privateKey']],
                ['push.vapidSubject', 'mailto:push@example.com'],
            ]);
        $this->configuration->method('getDefaultUrl')->willReturn('https://localhost/');
        $this->configuration->method('getLogger')->willReturn($logger);

        $this->repository->expects($this->once())->method('getAll')->willReturn([$subscription]);
        $this->repository->expects($this->never())->method('deleteByEndpoint');

        $this->service->sendToAll('Title', 'Body', '/faq', 'tag');
    }

    public function testSendToAllLogsErrorWhenSubscriptionKeyCannotBeUsedForEncryption(): void
    {
        $keys = $this->generateVapidKeysOrSkip();

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
            ->setPublicKey('not-a-p256dh-key')
            ->setAuthToken('auth')
            ->setContentEncoding(null)
            ->setCreatedAt(new \DateTimeImmutable('2026-03-09 12:00:00'));

        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['push.enableWebPush', 'true'],
                ['push.vapidPublicKey', $keys['publicKey']],
                ['push.vapidPrivateKey', $keys['privateKey']],
                ['push.vapidSubject', ''],
            ]);
        $this->configuration->method('getAdminEmail')->willReturn('admin@example.com');
        $this->configuration->method('getDefaultUrl')->willReturn('https://localhost/');
        $this->configuration->method('getLogger')->willReturn($logger);

        $this->repository->expects($this->once())->method('getAll')->willReturn([$subscription]);
        $this->repository->expects($this->never())->method('deleteByEndpoint');

        $this->service->sendToAll('Title', 'Body', '/faq', 'tag');
    }

    public function testSendToAllKeepsTheSubscriptionWhenThePushServiceCannotBeReached(): void
    {
        $keys = $this->generateVapidKeysOrSkip();

        $logger = $this->createMock(Logger::class);
        $logger->expects($this->never())->method('error');

        // Nothing listens on port 1, so the request fails at transport level: the report carries
        // no response and the subscription must not be treated as expired.
        $subscription = $this->createDeliverableSubscription('http://127.0.0.1:1/push/unreachable', $keys['publicKey']);
        $this->configureEnabledPush($keys, $logger);

        $this->repository->expects($this->once())->method('getAll')->willReturn([$subscription]);
        $this->repository->expects($this->never())->method('deleteByEndpoint');

        $this->service->sendToAll('Title', 'Body', '/faq', 'tag');
    }

    public function testSendToAllDeletesTheSubscriptionWhenThePushServiceReportsItGone(): void
    {
        $keys = $this->generateVapidKeysOrSkip();
        [$process, $port] = $this->startFakePushServer(
            "HTTP/1.1 410 Gone\r\nContent-Length: 0\r\nConnection: close\r\n\r\n",
        );

        $logger = $this->createMock(Logger::class);
        $logger->expects($this->never())->method('error');

        $endpoint = sprintf('http://127.0.0.1:%d/push/gone', $port);
        $subscription = $this->createDeliverableSubscription($endpoint, $keys['publicKey']);
        $this->configureEnabledPush($keys, $logger);

        $this->repository->expects($this->once())->method('getAll')->willReturn([$subscription]);
        $this->repository->expects($this->once())->method('deleteByEndpoint')->with($endpoint);

        try {
            $this->service->sendToAll('Title', 'Body', '/faq', 'tag');
        } finally {
            proc_close($process);
        }
    }

    /**
     * A subscription whose keys let the payload encryption succeed: any P-256 public key works as
     * the p256dh key, so the freshly generated VAPID public key is reused for it.
     */
    private function createDeliverableSubscription(string $endpoint, string $p256dhKey): PushSubscriptionEntity
    {
        return new PushSubscriptionEntity()
            ->setId(1)
            ->setUserId(4)
            ->setEndpoint($endpoint)
            ->setEndpointHash(sha1($endpoint))
            ->setPublicKey($p256dhKey)
            ->setAuthToken(rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '='))
            ->setContentEncoding('aes128gcm')
            ->setCreatedAt(new \DateTimeImmutable('2026-03-09 12:00:00'));
    }

    /**
     * @param array{publicKey: string, privateKey: string} $keys
     */
    private function configureEnabledPush(array $keys, Logger $logger): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['push.enableWebPush', 'true'],
                ['push.vapidPublicKey', $keys['publicKey']],
                ['push.vapidPrivateKey', $keys['privateKey']],
                ['push.vapidSubject', 'mailto:push@example.com'],
            ]);
        $this->configuration->method('getDefaultUrl')->willReturn('https://localhost/');
        $this->configuration->method('getLogger')->willReturn($logger);
    }

    /**
     * Starts a one-shot HTTP server in a child PHP process that answers the first request with $reply.
     *
     * @return array{0: resource, 1: int}
     */
    private function startFakePushServer(string $reply): array
    {
        $script = <<<'PHP'
            $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
            if ($server === false) {
                fwrite(STDERR, $errstr);
                exit(1);
            }
            $name = stream_socket_get_name($server, false);
            fwrite(STDOUT, substr($name, strrpos($name, ':') + 1) . "\n");
            fflush(STDOUT);
            $connection = stream_socket_accept($server, 10);
            if ($connection !== false) {
                $contentLength = 0;
                while (($line = fgets($connection)) !== false && trim($line) !== '') {
                    if (stripos($line, 'Content-Length:') === 0) {
                        $contentLength = (int) trim(substr($line, 15));
                    }
                }
                if ($contentLength > 0) {
                    fread($connection, $contentLength);
                }
                fwrite($connection, $argv[1]);
                fclose($connection);
            }
            fclose($server);
            PHP;

        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, '-r', $script, '--', $reply],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        if ($process === false) {
            static::markTestSkipped('Unable to start the fake push server process.');
        }

        $port = (int) trim((string) fgets($pipes[1]));
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        if ($port <= 0) {
            proc_close($process);
            static::markTestSkipped('The fake push server did not report a port.');
        }

        return [$process, $port];
    }

    /**
     * @return array{publicKey: string, privateKey: string}
     */
    private function generateVapidKeysOrSkip(): array
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

        if ($warnings !== [] || $keys['publicKey'] === '' || $keys['privateKey'] === '') {
            $this->markTestSkipped('VAPID key generation is not available in this environment.');
        }

        return $keys;
    }
}
