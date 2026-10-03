<?php

declare(strict_types=1);

namespace phpMyFAQ;

use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use ReflectionProperty;
use RuntimeException;

/**
 * Gives a test its own copy of the prepared SQLite test database.
 *
 * tests/test.db is built once by the bootstrap and has to stay pristine: a test that
 * writes to it (even a Configuration::set()) races every other test that copies the file
 * at that moment, which surfaces as "no such table" or "disk I/O error" failures in
 * unrelated tests. Each call copies the file to a temporary path and connects the given
 * driver to that copy. All copies are closed and deleted after every test.
 */
trait TestDatabaseTrait
{
    /** @var list<DatabaseDriver> */
    private array $testDatabaseHandles = [];

    /** @var list<string> */
    private array $testDatabaseCopies = [];

    private ?Configuration $previousConfigurationInstance = null;

    private string $previousTablePrefix = '';

    /**
     * Configuration::__construct() registers the first instance of the process as the
     * singleton, so a test that creates one leaks it to every later test. Remember what was
     * there before the test (and the table prefix, another process-wide static) and put it
     * back afterwards, so this test is invisible to others.
     */
    #[Before]
    protected function rememberConfigurationInstance(): void
    {
        /** @var Configuration|null $previous */
        $previous = new ReflectionProperty(Configuration::class, 'configuration')->getValue();
        $this->previousConfigurationInstance = $previous;
        $this->previousTablePrefix = Database::getTablePrefix();
    }

    /**
     * @template TDriver of DatabaseDriver
     * @param TDriver $driver A fresh, not yet connected driver instance
     * @return TDriver The same driver, connected to a private copy of tests/test.db
     */
    protected function connectToTestDatabaseCopy(DatabaseDriver $driver): DatabaseDriver
    {
        $copy = tempnam(sys_get_temp_dir(), 'pmf-test-db-');
        if ($copy === false || !copy(PMF_TEST_DIR . '/test.db', $copy)) {
            throw new RuntimeException('Unable to copy the prepared SQLite test database.');
        }

        $driver->connect($copy, '', '');

        // The prepared database has no table prefix, whatever an earlier test left behind.
        Database::setTablePrefix('');

        $this->testDatabaseHandles[] = $driver;
        $this->testDatabaseCopies[] = $copy;

        return $driver;
    }

    /**
     * Creates a Configuration on a private database copy and installs it as the process-wide
     * singleton that Configuration::getConfigurationInstance() and the DI container hand out.
     * Reusing whatever singleton an earlier test left behind couples tests to their execution
     * order; this replaces it for the duration of the test and restores it afterwards.
     */
    protected function createTestConfiguration(): Configuration
    {
        $configuration = new Configuration($this->connectToTestDatabaseCopy(new Sqlite3()));
        new ReflectionProperty(Configuration::class, 'configuration')->setValue(null, $configuration);

        // Load the stored settings right away. Configuration::get() reloads the whole array
        // from the database as soon as a key is missing, which would silently discard values
        // a test injects into the array through reflection.
        $configuration->getAll();

        return $configuration;
    }

    #[After]
    protected function removeTestDatabaseCopies(): void
    {
        new ReflectionProperty(Configuration::class, 'configuration')->setValue(
            null,
            $this->previousConfigurationInstance,
        );
        $this->previousConfigurationInstance = null;
        Database::setTablePrefix($this->previousTablePrefix);

        foreach ($this->testDatabaseHandles as $handle) {
            $handle->close();
        }

        foreach ($this->testDatabaseCopies as $copy) {
            // nosemgrep: php.lang.security.unlink-use.unlink-use - temp file created by tempnam() above
            @unlink($copy);
        }

        $this->testDatabaseHandles = [];
        $this->testDatabaseCopies = [];
    }
}
