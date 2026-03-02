<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\OAuth2\Entity;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClientEntity::class)]
class ClientEntityTest extends TestCase
{
    private ClientEntity $entity;

    protected function setUp(): void
    {
        $this->entity = new ClientEntity();
    }

    public function testImplementsClientEntityInterface(): void
    {
        $this->assertInstanceOf(ClientEntityInterface::class, $this->entity);
    }

    public function testSetAndGetIdentifier(): void
    {
        $this->entity->setIdentifier('client-id-abc');
        $this->assertSame('client-id-abc', $this->entity->getIdentifier());
    }

    public function testSetAndGetName(): void
    {
        $this->entity->setName('My OAuth App');
        $this->assertSame('My OAuth App', $this->entity->getName());
    }

    public function testSetAndGetRedirectUriAsString(): void
    {
        $this->entity->setRedirectUri('https://example.com/callback');
        $this->assertSame('https://example.com/callback', $this->entity->getRedirectUri());
    }

    public function testSetAndGetRedirectUriAsArray(): void
    {
        $uris = ['https://example.com/callback', 'https://example.com/auth'];
        $this->entity->setRedirectUri($uris);
        $this->assertSame($uris, $this->entity->getRedirectUri());
    }

    public function testIsNotConfidentialByDefault(): void
    {
        $this->assertFalse($this->entity->isConfidential());
    }

    public function testSetConfidential(): void
    {
        $this->entity->setConfidential(true);
        $this->assertTrue($this->entity->isConfidential());
    }

    public function testSetConfidentialToFalse(): void
    {
        $this->entity->setConfidential(true);
        $this->entity->setConfidential(false);
        $this->assertFalse($this->entity->isConfidential());
    }

    public function testSecretIsNullByDefault(): void
    {
        $this->assertNull($this->entity->secret);
    }

    public function testSetAndGetSecret(): void
    {
        $this->entity->secret = 'super-secret';
        $this->assertSame('super-secret', $this->entity->secret);
    }

    public function testAllowedGrantsEmptyByDefault(): void
    {
        $this->assertSame([], $this->entity->allowedGrants);
    }

    public function testSetAllowedGrants(): void
    {
        $this->entity->allowedGrants = ['authorization_code', 'refresh_token'];
        $this->assertSame(['authorization_code', 'refresh_token'], $this->entity->allowedGrants);
    }

    public function testSupportsGrantTypeReturnsTrueWhenNoGrantsConfigured(): void
    {
        $this->assertTrue($this->entity->supportsGrantType('authorization_code'));
        $this->assertTrue($this->entity->supportsGrantType('client_credentials'));
    }

    public function testSupportsGrantTypeReturnsTrueForAllowedGrant(): void
    {
        $this->entity->allowedGrants = ['authorization_code', 'refresh_token'];
        $this->assertTrue($this->entity->supportsGrantType('authorization_code'));
        $this->assertTrue($this->entity->supportsGrantType('refresh_token'));
    }

    public function testSupportsGrantTypeReturnsFalseForDisallowedGrant(): void
    {
        $this->entity->allowedGrants = ['authorization_code'];
        $this->assertFalse($this->entity->supportsGrantType('client_credentials'));
        $this->assertFalse($this->entity->supportsGrantType('refresh_token'));
    }
}
