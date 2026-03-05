<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\OAuth2\Repository;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use phpMyFAQ\Auth;
use phpMyFAQ\Auth\AuthDatabase;
use phpMyFAQ\Auth\OAuth2\Entity\UserEntity;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Encryption;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserRepository::class)]
#[AllowMockObjectsWithoutExpectations]
#[UsesClass(Database::class)]
#[UsesClass(UserEntity::class)]
#[UsesClass(Auth::class)]
#[UsesClass(AuthDatabase::class)]
#[UsesClass(Encryption::class)]
class UserRepositoryTest extends TestCase
{
    private DatabaseDriver&MockObject $db;
    private UserRepository $repository;

    protected function setUp(): void
    {
        Database::setTablePrefix('');

        $this->db = $this->createMock(DatabaseDriver::class);
        $this->db->method('escape')->willReturnCallback(static fn(string $value): string => $value);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($this->db);

        $this->repository = new UserRepository($configuration);
    }

    public function testImplementsUserRepositoryInterface(): void
    {
        $this->assertInstanceOf(UserRepositoryInterface::class, $this->repository);
    }

    public function testGetUserEntityReturnsNullWhenAuthDatabaseThrows(): void
    {
        // AuthDatabase constructor will fail because Configuration mock
        // doesn't provide a real database connection — caught by the try/catch
        $client = $this->createStub(ClientEntityInterface::class);

        $result = $this->repository->getUserEntityByUserCredentials('admin', 'password', 'password', $client);

        $this->assertNull($result);
    }
}
