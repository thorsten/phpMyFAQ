<?php

namespace phpMyFAQ\Search;

use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\DatabaseConfiguration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Strings;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Class SearchFactoryTest
 */
#[AllowMockObjectsWithoutExpectations]
class SearchFactoryTest extends TestCase
{
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
        $db = Database::factory($dbConfig->getType());
        $db->connect(
            $dbConfig->getServer(),
            $dbConfig->getUser(),
            $dbConfig->getPassword(),
            $dbConfig->getDatabase(),
            $dbConfig->getPort(),
        );
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
}
