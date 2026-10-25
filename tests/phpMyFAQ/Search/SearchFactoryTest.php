<?php

namespace phpMyFAQ\Search;

use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\DatabaseConfiguration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Database\PdoMysql;
use phpMyFAQ\Database\PdoPgsql;
use phpMyFAQ\Database\PdoSqlsrv;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Strings;
use phpMyFAQ\TestDatabaseTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Class SearchFactoryTest
 */
#[AllowMockObjectsWithoutExpectations]
class SearchFactoryTest extends TestCase
{
    use TestDatabaseTrait;

    /** @var Configuration */
    private $configuration;

    /**
     * Prepares the environment before running a test.
     *
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        $dbConfig = new DatabaseConfiguration(PMF_TEST_DIR . '/content/core/config/database.php');
        Database::setTablePrefix($dbConfig->getPrefix());
        $db = $this->connectToTestDatabaseCopy(Database::factory($dbConfig->getType()));
        $this->configuration = new Configuration($db);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testCreate(): void
    {
        $search = SearchFactory::create($this->configuration, array('database' => Database::getType()));

        $this->assertInstanceOf('phpMyFAQ\Search\Database\PdoSqlite', $search);
    }

    public function testCreateFallsBackToConfigurationDatabaseTypeWhenHandlerIsEmpty(): void
    {
        $search = SearchFactory::create($this->configuration, ['database' => '']);

        $this->assertInstanceOf('phpMyFAQ\Search\Database\PdoSqlite', $search);
    }

    public function testCreateBuildsPdoMysqlSearchForPdoMysqlHandler(): void
    {
        $search = SearchFactory::create($this->configuration, ['database' => 'pdo_mysql']);

        $this->assertInstanceOf('phpMyFAQ\Search\Database\PdoMysql', $search);
    }

    public function testCreateBuildsPdoPgsqlSearchForPdoPgsqlHandler(): void
    {
        $search = SearchFactory::create($this->configuration, ['database' => 'pdo_pgsql']);

        $this->assertInstanceOf('phpMyFAQ\Search\Database\PdoPgsql', $search);
    }

    public function testCreateBuildsPdoSqlsrvSearchForPdoSqlsrvHandler(): void
    {
        $search = SearchFactory::create($this->configuration, ['database' => 'pdo_sqlsrv']);

        $this->assertInstanceOf('phpMyFAQ\Search\Database\PdoSqlsrv', $search);
    }

    public function testCreateThrowsForUnknownSearchBackend(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown search backend: \phpMyFAQ\Search\Database\Nosuchdriver');

        SearchFactory::create($this->configuration, ['database' => 'nosuchdriver']);
    }

    /**
     * @return array<string, array{0: class-string<DatabaseDriver>, 1: class-string<SearchDatabase>}>
     */
    public static function pdoDriverProvider(): array
    {
        return [
            'mysql' => [PdoMysql::class, \phpMyFAQ\Search\Database\PdoMysql::class],
            'pgsql' => [PdoPgsql::class, \phpMyFAQ\Search\Database\PdoPgsql::class],
            'sqlsrv' => [PdoSqlsrv::class, \phpMyFAQ\Search\Database\PdoSqlsrv::class],
        ];
    }

    #[DataProvider('pdoDriverProvider')]
    public function testCreateResolvesThePdoDriverClassWhenHandlerIsEmpty(
        string $driverClass,
        string $searchClass,
    ): void {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn(new $driverClass());

        $search = SearchFactory::create($configuration, ['database' => '']);

        $this->assertInstanceOf($searchClass, $search);
    }

    public function testCreateBuildsNonPdoSearchForMysqliHandler(): void
    {
        $search = SearchFactory::create($this->configuration, ['database' => 'mysqli']);

        $this->assertInstanceOf('phpMyFAQ\Search\Database\Mysqli', $search);
    }
}
