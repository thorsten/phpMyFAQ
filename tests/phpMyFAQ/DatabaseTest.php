<?php

namespace phpMyFAQ;

use phpMyFAQ\Configuration\DatabaseConfiguration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{

    /**
     * @throws Exception
     */
    public function testFactoryReturnsInstanceOfDatabaseDriver(): void
    {
        $type = 'sqlite3';
        $driver = Database::factory($type);
        $this->assertInstanceOf(Sqlite3::class, $driver);
    }

    public function testGetInstance(): void
    {
        $instance = Database::getInstance();

        $this->assertInstanceOf(Sqlite3::class, $instance);
        $this->assertSame($instance, Database::getInstance());
    }

    public function testGetType(): void
    {
        $expectedType = 'sqlite3';

        Database::getInstance();

        $actualType = Database::getType();
        $this->assertEquals($expectedType, $actualType);
    }

    /**
     * @throws Exception
     */
    public function testCheckOnEmptyTable(): void
    {
        $dbConfig = new DatabaseConfiguration(PMF_TEST_DIR . '/content/core/config/database.php');
        Database::setTablePrefix($dbConfig->getPrefix());
        $db = Database::factory($dbConfig->getType());
        $db->connect(
            $dbConfig->getServer(),
            $dbConfig->getUser(),
            $dbConfig->getPassword(),
            $dbConfig->getDatabase(),
            $dbConfig->getPort()
        );

        $actual = Database::checkOnEmptyTable('faqconfig');
        $this->assertEquals(0, $actual);
    }

    public function testErrorPage(): void
    {
        ob_start();
        Database::errorPage('Error message');
        $output = ob_get_clean();

        $this->assertStringContainsString(
            '<title>Fatal phpMyFAQ Error</title>',
            $output
        );
        $this->assertStringContainsString(
            '<p class="alert alert-danger">The connection to the database server could not be established.</p>',
            $output
        );
        $this->assertStringContainsString(
            '<p class="alert alert-danger">The error message of the database server: Error message</p>',
            $output
        );
    }
}
