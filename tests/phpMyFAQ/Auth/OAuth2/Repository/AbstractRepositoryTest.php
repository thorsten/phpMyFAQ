<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\OAuth2\Repository;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractRepository::class)]
#[UsesClass(Database::class)]
final class AbstractRepositoryTest extends TestCase
{
    protected function tearDown(): void
    {
        Database::setTablePrefix('');
    }

    private function createRepository(Configuration $configuration): object
    {
        return new class ($configuration) extends AbstractRepository {
            public function exposedDb(): DatabaseDriver
            {
                return $this->db();
            }

            public function exposedTable(string $tableName): string
            {
                return $this->table($tableName);
            }
        };
    }

    public function testDbReturnsTheConfiguredDriver(): void
    {
        $driver = $this->createStub(DatabaseDriver::class);
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($driver);

        $this->assertSame($driver, $this->createRepository($configuration)->exposedDb());
    }

    public function testTablePrependsTheConfiguredPrefix(): void
    {
        $repository = $this->createRepository($this->createStub(Configuration::class));

        Database::setTablePrefix('pmf_');
        $this->assertSame('pmf_faqoauth_clients', $repository->exposedTable('faqoauth_clients'));

        Database::setTablePrefix('');
        $this->assertSame('faqoauth_clients', $repository->exposedTable('faqoauth_clients'));
    }
}
