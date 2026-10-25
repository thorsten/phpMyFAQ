<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\OAuth2\Repository;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use phpMyFAQ\Auth;
use phpMyFAQ\Auth\AuthDatabase;
use phpMyFAQ\Auth\OAuth2\Entity\UserEntity;
use phpMyFAQ\Auth\PasswordHasher;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Encryption;
use phpMyFAQ\Environment;
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
#[UsesClass(PasswordHasher::class)]
#[UsesClass(Encryption::class)]
#[UsesClass(Environment::class)]
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

    public function testGetUserEntityReturnsTheUserIdentifierForValidCredentials(): void
    {
        $this->allowLoginFor('admin', 'password');
        $this->db
            ->expects($this->once())
            ->method('query')
            ->with("SELECT user_id FROM faquser WHERE login = 'admin'")
            ->willReturn(true);
        $this->db->method('fetchObject')->willReturn((object) ['user_id' => 42]);

        $user = $this->repository->getUserEntityByUserCredentials(
            'admin',
            'password',
            'password',
            $this->createStub(ClientEntityInterface::class),
        );

        $this->assertInstanceOf(UserEntity::class, $user);
        $this->assertSame('42', $user->getIdentifier());
    }

    public function testGetUserEntityReturnsNullWhenTheUserLookupFails(): void
    {
        $this->allowLoginFor('admin', 'password');
        $this->db->method('query')->willReturn(false);
        $this->db->expects($this->never())->method('fetchObject');

        $this->assertNull($this->repository->getUserEntityByUserCredentials(
            'admin',
            'password',
            'password',
            $this->createStub(ClientEntityInterface::class),
        ));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function unusableUserRowProvider(): iterable
    {
        yield 'no row' => [false];
        yield 'row without user id' => [(object) ['login' => 'admin']];
        yield 'null user id' => [(object) ['user_id' => null]];
        yield 'empty user id' => [(object) ['user_id' => '']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('unusableUserRowProvider')]
    public function testGetUserEntityReturnsNullWithoutAUsableUserId(mixed $row): void
    {
        $this->allowLoginFor('admin', 'password');
        $this->db->method('query')->willReturn(true);
        $this->db->method('fetchObject')->willReturn($row);

        $this->assertNull($this->repository->getUserEntityByUserCredentials(
            'admin',
            'password',
            'password',
            $this->createStub(ClientEntityInterface::class),
        ));
    }

    /**
     * Lets AuthDatabase::checkCredentials() succeed for the given login and password.
     */
    private function allowLoginFor(string $login, string $password): void
    {
        $this->db->method('queryPrepared')->willReturn(true);
        $this->db->method('error')->willReturn('');
        $this->db->method('numRows')->willReturn(1);
        $this->db->method('fetchArray')->willReturnOnConsecutiveCalls([
            'login' => $login,
            'pass' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]),
        ], false);
    }
}
