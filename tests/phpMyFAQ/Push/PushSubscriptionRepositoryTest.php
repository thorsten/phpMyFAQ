<?php

namespace phpMyFAQ\Push;

use DateTimeImmutable;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Entity\PushSubscriptionEntity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PushSubscriptionRepositoryTest extends TestCase
{
    private Configuration&MockObject $configuration;

    private DatabaseDriver&MockObject $dbDriver;

    protected function setUp(): void
    {
        $this->configuration = $this->createMock(Configuration::class);
        $this->dbDriver = $this->createMock(DatabaseDriver::class);

        $this->configuration->method('getDb')->willReturn($this->dbDriver);
    }

    public function testHasSubscriptionReturnsTrueWhenExists(): void
    {
        $this->dbDriver->method('query')->willReturn(true);

        $this->dbDriver->method('fetchObject')->willReturn((object) ['id' => 1]);

        $repository = new PushSubscriptionRepository($this->configuration);

        $this->assertTrue($repository->hasSubscription(1));
    }

    public function testHasSubscriptionReturnsFalseWhenNotExists(): void
    {
        $this->dbDriver->method('query')->willReturn(true);

        $this->dbDriver->method('fetchObject')->willReturn(false);

        $repository = new PushSubscriptionRepository($this->configuration);

        $this->assertFalse($repository->hasSubscription(999));
    }

    public function testGetByUserIdReturnsEmptyArrayWhenNoResults(): void
    {
        $this->dbDriver->method('query')->willReturn(true);

        $this->dbDriver->method('fetchObject')->willReturn(false);

        $repository = new PushSubscriptionRepository($this->configuration);

        $this->assertEquals([], $repository->getByUserId(999));
    }

    public function testGetAllReturnsEmptyArrayWhenNoResults(): void
    {
        $this->dbDriver->method('query')->willReturn(true);

        $this->dbDriver->method('fetchObject')->willReturn(false);

        $repository = new PushSubscriptionRepository($this->configuration);

        $this->assertEquals([], $repository->getAll());
    }

    public function testDeleteByEndpointHashReturnsTrue(): void
    {
        $this->dbDriver->method('escape')->willReturnArgument(0);

        $this->dbDriver->method('query')->willReturn(true);

        $repository = new PushSubscriptionRepository($this->configuration);

        $this->assertTrue($repository->deleteByEndpointHash('abc123hash'));
    }

    public function testDeleteByUserIdReturnsTrue(): void
    {
        $this->dbDriver->method('query')->willReturn(true);

        $repository = new PushSubscriptionRepository($this->configuration);

        $this->assertTrue($repository->deleteByUserId(1));
    }

    public function testDeleteByEndpointDelegatesToDeleteByEndpointHash(): void
    {
        $this->dbDriver->method('escape')->willReturnArgument(0);

        $this->dbDriver->method('query')->willReturn(true);

        $repository = new PushSubscriptionRepository($this->configuration);
        $endpoint = 'https://fcm.googleapis.com/fcm/send/abc123';

        $this->assertTrue($repository->deleteByEndpoint($endpoint));
    }

    public function testSaveReturnsTrueWhenInsertSucceeds(): void
    {
        $entity = $this->createEntity(endpoint: 'https://example.com/1', contentEncoding: null);

        $this->dbDriver->method('escape')->willReturnArgument(0);
        $this->dbDriver
            ->expects($this->once())
            ->method('nextId')
            ->with('faqpush_subscriptions', 'id')
            ->willReturn(11);
        $this->dbDriver->method('now')->willReturn('CURRENT_TIMESTAMP');
        $this->dbDriver
            ->expects($this->once())
            ->method('query')
            ->willReturn(true);

        $repository = new PushSubscriptionRepository($this->configuration);

        $this->assertTrue($repository->save($entity));
    }

    public function testSaveFallsBackToUpdateWhenInsertReturnsFalse(): void
    {
        $entity = $this->createEntity(endpoint: 'https://example.com/2');

        $this->dbDriver->method('escape')->willReturnArgument(0);
        $this->dbDriver->method('nextId')->willReturn(12);
        $this->dbDriver->method('now')->willReturn('CURRENT_TIMESTAMP');
        $this->dbDriver
            ->expects($this->exactly(2))
            ->method('query')
            ->willReturnOnConsecutiveCalls(false, true);

        $repository = new PushSubscriptionRepository($this->configuration);

        $this->assertTrue($repository->save($entity));
    }

    public function testSaveReturnsFalseWhenInsertThrowsAndUpdateFails(): void
    {
        $entity = $this->createEntity(endpoint: 'https://example.com/3');

        $this->dbDriver->method('escape')->willReturnArgument(0);
        $this->dbDriver->method('nextId')->willReturn(13);
        $this->dbDriver->method('now')->willReturn('CURRENT_TIMESTAMP');
        $this->dbDriver
            ->expects($this->exactly(2))
            ->method('query')
            ->willReturnCallback(static function () use (&$callCount): bool {
                $callCount = ($callCount ?? 0) + 1;
                if ($callCount === 1) {
                    throw new \RuntimeException('duplicate');
                }

                throw new \RuntimeException('update failed');
            });

        $repository = new PushSubscriptionRepository($this->configuration);

        $this->assertFalse($repository->save($entity));
    }

    public function testDeleteByEndpointHashAndUserIdReturnsTrue(): void
    {
        $this->dbDriver->method('escape')->willReturnArgument(0);
        $this->dbDriver->method('query')->willReturn(true);

        $repository = new PushSubscriptionRepository($this->configuration);

        $this->assertTrue($repository->deleteByEndpointHashAndUserId('hash123', 9));
    }

    public function testGetByUserIdReturnsMappedEntities(): void
    {
        $this->dbDriver->method('query')->willReturn(true);
        $this->dbDriver->method('fetchObject')->willReturnOnConsecutiveCalls(
            (object) [
                'id' => 1,
                'user_id' => 7,
                'endpoint' => 'https://example.com/a',
                'endpoint_hash' => 'hash-a',
                'public_key' => 'public-a',
                'auth_token' => 'auth-a',
                'content_encoding' => 'aes128gcm',
                'created_at' => '2026-03-08 12:00:00',
            ],
            false,
        );

        $repository = new PushSubscriptionRepository($this->configuration);
        $result = $repository->getByUserId(7);

        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]->getId());
        $this->assertSame(7, $result[0]->getUserId());
        $this->assertSame('https://example.com/a', $result[0]->getEndpoint());
        $this->assertSame('aes128gcm', $result[0]->getContentEncoding());
        $this->assertSame('2026-03-08 12:00:00', $result[0]->getCreatedAt()->format('Y-m-d H:i:s'));
    }

    public function testGetByUserIdsReturnsEmptyArrayForEmptyInput(): void
    {
        $this->dbDriver->expects($this->never())->method('query');

        $repository = new PushSubscriptionRepository($this->configuration);

        $this->assertSame([], $repository->getByUserIds([]));
    }

    public function testGetByUserIdsReturnsMappedEntitiesAndCastsIds(): void
    {
        $this->dbDriver->method('query')->willReturn(true);
        $this->dbDriver->method('fetchObject')->willReturnOnConsecutiveCalls(
            (object) [
                'id' => 2,
                'user_id' => 4,
                'endpoint' => 'https://example.com/b',
                'endpoint_hash' => 'hash-b',
                'public_key' => 'public-b',
                'auth_token' => 'auth-b',
                'content_encoding' => null,
                'created_at' => 'invalid-date',
            ],
            false,
        );

        $repository = new PushSubscriptionRepository($this->configuration);
        $result = $repository->getByUserIds(['4', 5]);

        $this->assertCount(1, $result);
        $this->assertSame(2, $result[0]->getId());
        $this->assertSame(4, $result[0]->getUserId());
        $this->assertNull($result[0]->getContentEncoding());
        $this->assertInstanceOf(DateTimeImmutable::class, $result[0]->getCreatedAt());
    }

    public function testGetByUserIdsReturnsEmptyArrayWhenQueryFails(): void
    {
        $this->dbDriver->method('query')->willReturn(false);

        $repository = new PushSubscriptionRepository($this->configuration);

        $this->assertSame([], $repository->getByUserIds([1, 2]));
    }

    public function testGetAllReturnsMappedEntities(): void
    {
        $this->dbDriver->method('query')->willReturn(true);
        $this->dbDriver->method('fetchObject')->willReturnOnConsecutiveCalls(
            (object) [
                'id' => 3,
                'user_id' => 8,
                'endpoint' => 'https://example.com/c',
                'endpoint_hash' => 'hash-c',
                'public_key' => 'public-c',
                'auth_token' => 'auth-c',
                'content_encoding' => 'aesgcm',
                'created_at' => '2026-03-09 10:11:12',
            ],
            false,
        );

        $repository = new PushSubscriptionRepository($this->configuration);
        $result = $repository->getAll();

        $this->assertCount(1, $result);
        $this->assertSame('hash-c', $result[0]->getEndpointHash());
    }

    public function testHasSubscriptionReturnsFalseWhenQueryFails(): void
    {
        $this->dbDriver->method('query')->willReturn(false);

        $repository = new PushSubscriptionRepository($this->configuration);

        $this->assertFalse($repository->hasSubscription(123));
    }

    private function createEntity(string $endpoint, ?string $contentEncoding = 'aesgcm'): PushSubscriptionEntity
    {
        return new PushSubscriptionEntity()
            ->setId(1)
            ->setUserId(5)
            ->setEndpoint($endpoint)
            ->setEndpointHash(hash('sha256', $endpoint))
            ->setPublicKey('public-key')
            ->setAuthToken('auth-token')
            ->setContentEncoding($contentEncoding)
            ->setCreatedAt(new DateTimeImmutable('2026-03-09 12:00:00'));
    }
}
