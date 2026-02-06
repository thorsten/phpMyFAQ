<?php

namespace phpMyFAQ\Push;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
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
}
