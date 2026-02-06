<?php

namespace phpMyFAQ\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PushSubscriptionEntityTest extends TestCase
{
    private PushSubscriptionEntity $entity;

    protected function setUp(): void
    {
        $this->entity = new PushSubscriptionEntity();
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf(PushSubscriptionEntity::class, $this->entity);
    }

    public function testIdGetterAndSetter(): void
    {
        $result = $this->entity->setId(42);

        $this->assertInstanceOf(PushSubscriptionEntity::class, $result);
        $this->assertEquals(42, $this->entity->getId());
    }

    public function testUserIdGetterAndSetter(): void
    {
        $result = $this->entity->setUserId(7);

        $this->assertInstanceOf(PushSubscriptionEntity::class, $result);
        $this->assertEquals(7, $this->entity->getUserId());
    }

    public function testEndpointGetterAndSetter(): void
    {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/abc123';
        $result = $this->entity->setEndpoint($endpoint);

        $this->assertInstanceOf(PushSubscriptionEntity::class, $result);
        $this->assertEquals($endpoint, $this->entity->getEndpoint());
    }

    public function testEndpointHashGetterAndSetter(): void
    {
        $hash = hash('sha256', 'https://fcm.googleapis.com/fcm/send/abc123');
        $result = $this->entity->setEndpointHash($hash);

        $this->assertInstanceOf(PushSubscriptionEntity::class, $result);
        $this->assertEquals($hash, $this->entity->getEndpointHash());
    }

    public function testPublicKeyGetterAndSetter(): void
    {
        $key = 'BNc...publickey';
        $result = $this->entity->setPublicKey($key);

        $this->assertInstanceOf(PushSubscriptionEntity::class, $result);
        $this->assertEquals($key, $this->entity->getPublicKey());
    }

    public function testAuthTokenGetterAndSetter(): void
    {
        $token = 'auth_token_value';
        $result = $this->entity->setAuthToken($token);

        $this->assertInstanceOf(PushSubscriptionEntity::class, $result);
        $this->assertEquals($token, $this->entity->getAuthToken());
    }

    public function testContentEncodingGetterAndSetter(): void
    {
        $result = $this->entity->setContentEncoding('aes128gcm');

        $this->assertInstanceOf(PushSubscriptionEntity::class, $result);
        $this->assertEquals('aes128gcm', $this->entity->getContentEncoding());
    }

    public function testContentEncodingNullable(): void
    {
        $this->entity->setContentEncoding(null);

        $this->assertNull($this->entity->getContentEncoding());
    }

    public function testCreatedAtGetterAndSetter(): void
    {
        $date = new DateTimeImmutable('2026-02-02 12:00:00');
        $result = $this->entity->setCreatedAt($date);

        $this->assertInstanceOf(PushSubscriptionEntity::class, $result);
        $this->assertEquals($date, $this->entity->getCreatedAt());
    }

    public function testFluentInterface(): void
    {
        $date = new DateTimeImmutable();
        $result = $this->entity
            ->setId(1)
            ->setUserId(5)
            ->setEndpoint('https://example.com/push')
            ->setEndpointHash(hash('sha256', 'https://example.com/push'))
            ->setPublicKey('publickey')
            ->setAuthToken('authtoken')
            ->setContentEncoding('aesgcm')
            ->setCreatedAt($date);

        $this->assertInstanceOf(PushSubscriptionEntity::class, $result);
        $this->assertEquals(1, $this->entity->getId());
        $this->assertEquals(5, $this->entity->getUserId());
        $this->assertEquals('https://example.com/push', $this->entity->getEndpoint());
        $this->assertEquals('publickey', $this->entity->getPublicKey());
        $this->assertEquals('authtoken', $this->entity->getAuthToken());
        $this->assertEquals('aesgcm', $this->entity->getContentEncoding());
    }
}
