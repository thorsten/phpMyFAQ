<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\OAuth2\Repository;

use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use phpMyFAQ\Auth\OAuth2\Entity\ClientEntity;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Database\PdoSqlite;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClientRepository::class)]
#[AllowMockObjectsWithoutExpectations]
#[UsesClass(Database::class)]
#[UsesClass(ClientEntity::class)]
#[UsesClass(PdoSqlite::class)]
class ClientRepositoryTest extends TestCase
{
    private DatabaseDriver&MockObject $db;
    private ClientRepository $repository;

    protected function setUp(): void
    {
        Database::setTablePrefix('');

        $this->db = $this->createMock(DatabaseDriver::class);
        $this->db->method('escape')->willReturnCallback(static fn(string $value): string => $value);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($this->db);

        $this->repository = new ClientRepository($configuration);
    }

    public function testImplementsClientRepositoryInterface(): void
    {
        $this->assertInstanceOf(ClientRepositoryInterface::class, $this->repository);
    }

    // --- getClientEntity ---

    public function testGetClientEntityReturnsClientWhenFound(): void
    {
        $row = (object) [
            'client_id' => 'my-app',
            'client_secret' => 'secret-hash',
            'name' => 'My Application',
            'redirect_uri' => 'https://example.com/callback',
            'grants' => 'authorization_code,refresh_token',
            'is_confidential' => 1,
        ];

        $this->db->method('query')->willReturn(new \stdClass());
        $this->db->method('fetchObject')->willReturn($row);

        $client = $this->repository->getClientEntity('my-app');

        $this->assertInstanceOf(ClientEntity::class, $client);
        $this->assertSame('my-app', $client->getIdentifier());
        $this->assertSame('secret-hash', $client->secret);
        $this->assertSame('My Application', $client->getName());
        $this->assertSame('https://example.com/callback', $client->getRedirectUri());
        $this->assertTrue($client->isConfidential());
        $this->assertSame(['authorization_code', 'refresh_token'], $client->allowedGrants);
    }

    public function testGetClientEntityReturnsNullWhenQueryFails(): void
    {
        $this->db->method('query')->willReturn(false);

        $this->assertNull($this->repository->getClientEntity('non-existent'));
    }

    public function testGetClientEntityReturnsNullWhenRowNotFound(): void
    {
        $this->db->method('query')->willReturn(new \stdClass());
        $this->db->method('fetchObject')->willReturn(false);

        $this->assertNull($this->repository->getClientEntity('non-existent'));
    }

    public function testGetClientEntityHandlesNullableFieldsGracefully(): void
    {
        $row = (object) [
            'client_id' => 'simple-app',
            'client_secret' => null,
            'name' => null,
            'redirect_uri' => null,
            'grants' => null,
            'is_confidential' => null,
        ];

        $this->db->method('query')->willReturn(new \stdClass());
        $this->db->method('fetchObject')->willReturn($row);

        $client = $this->repository->getClientEntity('simple-app');

        $this->assertInstanceOf(ClientEntity::class, $client);
        $this->assertSame('simple-app', $client->getIdentifier());
        $this->assertNull($client->secret);
        $this->assertSame('simple-app', $client->getName());
        $this->assertSame('', $client->getRedirectUri());
        $this->assertTrue($client->isConfidential());
        $this->assertSame([], $client->allowedGrants);
    }

    public function testGetClientEntityWithEmptyGrantsProducesEmptyArray(): void
    {
        $row = (object) [
            'client_id' => 'open-app',
            'client_secret' => null,
            'name' => 'Open App',
            'redirect_uri' => 'https://example.com',
            'grants' => '',
            'is_confidential' => 0,
        ];

        $this->db->method('query')->willReturn(new \stdClass());
        $this->db->method('fetchObject')->willReturn($row);

        $client = $this->repository->getClientEntity('open-app');

        $this->assertSame([], $client->allowedGrants);
        $this->assertFalse($client->isConfidential());
    }

    // --- validateClient ---

    public function testValidateClientReturnsFalseWhenClientNotFound(): void
    {
        $this->db->method('query')->willReturn(false);

        $this->assertFalse($this->repository->validateClient('unknown', 'secret', 'authorization_code'));
    }

    public function testValidateClientReturnsFalseForUnsupportedGrantType(): void
    {
        $row = (object) [
            'client_id' => 'restricted-app',
            'client_secret' => 'secret',
            'name' => 'Restricted App',
            'redirect_uri' => 'https://example.com/cb',
            'grants' => 'authorization_code',
            'is_confidential' => 0,
        ];

        $this->db->method('query')->willReturn(new \stdClass());
        $this->db->method('fetchObject')->willReturn($row);

        $this->assertFalse($this->repository->validateClient('restricted-app', null, 'client_credentials'));
    }

    public function testValidateClientReturnsTrueForPublicClientWithNullGrantType(): void
    {
        $row = (object) [
            'client_id' => 'public-app',
            'client_secret' => null,
            'name' => 'Public App',
            'redirect_uri' => 'https://example.com/cb',
            'grants' => '',
            'is_confidential' => 0,
        ];

        $this->db->method('query')->willReturn(new \stdClass());
        $this->db->method('fetchObject')->willReturn($row);

        $this->assertTrue($this->repository->validateClient('public-app', null, null));
    }

    public function testValidateClientReturnsTrueForPublicClient(): void
    {
        $row = (object) [
            'client_id' => 'public-app',
            'client_secret' => null,
            'name' => 'Public App',
            'redirect_uri' => 'https://example.com/cb',
            'grants' => '',
            'is_confidential' => 0,
        ];

        $this->db->method('query')->willReturn(new \stdClass());
        $this->db->method('fetchObject')->willReturn($row);

        $this->assertTrue($this->repository->validateClient('public-app', null, 'authorization_code'));
    }

    public function testValidateClientReturnsFalseForConfidentialClientWithNullStoredSecret(): void
    {
        $row = (object) [
            'client_id' => 'conf-app',
            'client_secret' => null,
            'name' => 'Confidential App',
            'redirect_uri' => 'https://example.com/cb',
            'grants' => '',
            'is_confidential' => 1,
        ];

        $this->db->method('query')->willReturn(new \stdClass());
        $this->db->method('fetchObject')->willReturn($row);

        $this->assertFalse($this->repository->validateClient('conf-app', 'some-secret', null));
    }

    public function testValidateClientReturnsFalseForConfidentialClientWithNullProvidedSecret(): void
    {
        $row = (object) [
            'client_id' => 'conf-app',
            'client_secret' => 'stored-secret',
            'name' => 'Confidential App',
            'redirect_uri' => 'https://example.com/cb',
            'grants' => '',
            'is_confidential' => 1,
        ];

        $this->db->method('query')->willReturn(new \stdClass());
        $this->db->method('fetchObject')->willReturn($row);

        $this->assertFalse($this->repository->validateClient('conf-app', null, null));
    }

    public function testValidateClientReturnsTrueForConfidentialClientWithMatchingPlainSecret(): void
    {
        $row = (object) [
            'client_id' => 'conf-app',
            'client_secret' => 'my-plain-secret',
            'name' => 'Confidential App',
            'redirect_uri' => 'https://example.com/cb',
            'grants' => '',
            'is_confidential' => 1,
        ];

        $this->db->method('query')->willReturn(new \stdClass());
        $this->db->method('fetchObject')->willReturn($row);

        $this->assertTrue($this->repository->validateClient('conf-app', 'my-plain-secret', null));
    }

    public function testValidateClientReturnsFalseForConfidentialClientWithWrongPlainSecret(): void
    {
        $row = (object) [
            'client_id' => 'conf-app',
            'client_secret' => 'correct-secret',
            'name' => 'Confidential App',
            'redirect_uri' => 'https://example.com/cb',
            'grants' => '',
            'is_confidential' => 1,
        ];

        $this->db->method('query')->willReturn(new \stdClass());
        $this->db->method('fetchObject')->willReturn($row);

        $this->assertFalse($this->repository->validateClient('conf-app', 'wrong-secret', null));
    }

    public function testValidateClientReturnsTrueForConfidentialClientWithHashedSecret(): void
    {
        $hashedSecret = password_hash('correct-password', PASSWORD_BCRYPT);

        $row = (object) [
            'client_id' => 'hashed-app',
            'client_secret' => $hashedSecret,
            'name' => 'Hashed App',
            'redirect_uri' => 'https://example.com/cb',
            'grants' => '',
            'is_confidential' => 1,
        ];

        $this->db->method('query')->willReturn(new \stdClass());
        $this->db->method('fetchObject')->willReturn($row);

        $this->assertTrue($this->repository->validateClient('hashed-app', 'correct-password', null));
    }

    public function testValidateClientReturnsFalseForConfidentialClientWithWrongHashedSecret(): void
    {
        $hashedSecret = password_hash('correct-password', PASSWORD_BCRYPT);

        $row = (object) [
            'client_id' => 'hashed-app',
            'client_secret' => $hashedSecret,
            'name' => 'Hashed App',
            'redirect_uri' => 'https://example.com/cb',
            'grants' => '',
            'is_confidential' => 1,
        ];

        $this->db->method('query')->willReturn(new \stdClass());
        $this->db->method('fetchObject')->willReturn($row);

        $this->assertFalse($this->repository->validateClient('hashed-app', 'wrong-password', null));
    }
}
