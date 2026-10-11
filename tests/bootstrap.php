<?php

/**
 * Bootstrap phpMyFAQ PHPUnit testing environment
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015 phpMyFAQ Team
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2015-02-12
 */

use Composer\Autoload\ClassLoader;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Setup\Installation\DatabaseSchema;
use phpMyFAQ\Setup\Installer;
use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\SqliteDialect;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

date_default_timezone_set('Europe/Berlin');

// nosemgrep: php.lang.security.search-active-debug.search-active-debug - PHPUnit bootstrap, never production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED);

//
// The root directory
//
define('PMF_ROOT_DIR', dirname(__DIR__) . '/phpmyfaq');

//
// ParaTest exports TEST_TOKEN (1..n) to every worker process. Each worker gets its own
// copy of tests/content so that tests which create, rename or delete config files,
// attachments or upgrade packages never race against a test in another worker. The copy
// is made below, after the shared test database has been prepared. Without a token
// (plain PHPUnit) the checked-in directory is used directly, as before.
//
const PMF_SHARED_CONTENT_DIR = __DIR__ . '/content';
$testWorkerToken = getenv('TEST_TOKEN');
if ($testWorkerToken !== false && $testWorkerToken !== '') {
    define('PMF_CONTENT_DIR', sys_get_temp_dir() . '/phpmyfaq-tests/worker-' . (int) $testWorkerToken . '/content');
} else {
    define('PMF_CONTENT_DIR', PMF_SHARED_CONTENT_DIR);
}
define('PMF_CONFIG_DIR', PMF_CONTENT_DIR . '/core/config');

const PMF_LOG_DIR = __DIR__ . '/logs/phpmyfaq.log';

const PMF_TEST_DIR = __DIR__;

const IS_VALID_PHPMYFAQ = true;

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';

// Ensure test log destination exists as a writable file for Monolog
$logDirectory = dirname(PMF_LOG_DIR);
if (!is_dir($logDirectory)) {
    mkdir($logDirectory, 0777, true);
}

if (!file_exists(PMF_LOG_DIR)) {
    touch(PMF_LOG_DIR);
}

//
// Everything below that mutates files shared by all test processes (the filesystem
// cache, tests/test.db and the generated database.php) runs under this lock, so
// concurrent ParaTest workers and PHPUnit separate-process children cannot rebuild
// the database underneath each other.
//
$bootstrapLockHandle = fopen(__DIR__ . '/.bootstrap.lock', 'c');
if ($bootstrapLockHandle === false || !flock($bootstrapLockHandle, LOCK_EX)) {
    throw new RuntimeException('PHPUnit bootstrap failed: cannot acquire tests/.bootstrap.lock.');
}

//
// Clear the filesystem cache so state persisted by a previous run (e.g. the
// rate limiter's fixed-window counters) cannot make tests flaky.
//
$cacheDirectory = PMF_ROOT_DIR . '/content/core/cache/pmf';
if (is_dir($cacheDirectory)) {
    $cacheFiles = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cacheDirectory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($cacheFiles as $cacheFile) {
        $cacheFile->isDir() ? @rmdir($cacheFile->getPathname()) : @unlink($cacheFile->getPathname());
    }
}

require PMF_ROOT_DIR . '/content/core/config/constants.php';

//
// The /src directory
//
define('PMF_SRC_DIR', dirname(__DIR__) . '/phpmyfaq/src');

//
// The directory where the translations reside
//
define('PMF_TRANSLATION_DIR', dirname(__DIR__) . '/phpmyfaq/translations');
require PMF_TRANSLATION_DIR . '/language_en.php';

//
// Setting up autoloader
//
$loader = new ClassLoader();
$loader->add('phpMyFAQ', PMF_SRC_DIR);
$loader->add('phpMyFAQ', PMF_TEST_DIR);
$loader->register();

// Namespace-level function overrides must exist before the code they shadow runs
// for the first time in this process; see the file for details.
require PMF_TEST_DIR . '/phpMyFAQ/Configuration/Storage/RedisExtensionShim.php';

/**
 * Reuses the prepared SQLite test database across bootstrap invocations when possible.
 * This avoids reinstall races in PHPUnit separate-process tests and coverage subprocesses.
 *
 * The expected table set is derived from the authoritative DatabaseSchema, so a test
 * database that predates a schema change (a newly added table) is automatically
 * detected as stale and rebuilt — instead of silently producing "no such table" errors.
 */
function canReusePreparedTestDatabase(string $databasePath, string $databaseConfigPath): bool
{
    if (!file_exists($databasePath) || !file_exists($databaseConfigPath)) {
        return false;
    }

    // The recorded server path in database.php must match the current environment's
    // database path. A previously generated config may carry a different absolute path
    // — e.g. the same checkout accessed under a different case on a case-insensitive
    // filesystem, or a moved/shared working copy. Reusing it would leave database.php
    // pointing at the wrong path string and break path-sensitive assertions. A mismatch
    // forces a rebuild below, which regenerates database.php with the current path.
    if (!is_readable($databaseConfigPath)) {
        return false;
    }

    $DB = [];
    include $databaseConfigPath;
    if (!isset($DB['server']) || $DB['server'] !== $databasePath) {
        return false;
    }

    try {
        $pdo = new \PDO('sqlite:' . $databasePath);
        $statement = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");

        if ($statement === false) {
            return false;
        }

        $existingTables = $statement->fetchAll(\PDO::FETCH_COLUMN);
        $expectedTables = new DatabaseSchema(new SqliteDialect())->getTableNames();

        // The prepared database is reusable only when every table the current schema
        // defines is present. Any missing table means the schema has advanced since the
        // database was built, so it must be rebuilt from scratch.
        return array_diff($expectedTables, $existingTables) === [];
    } catch (\PDOException) {
        return false;
    }
}

//
// Use tests/test.db as stable SQLite test database expected by tests.
//
$testDbAlias = PMF_TEST_DIR . '/test.db';
$databaseConfigFile = PMF_SHARED_CONTENT_DIR . '/core/config/database.php';

//
// Create database credentials for SQLite
//
$setup = [
    'dbServer' => $testDbAlias,
    'dbType' => 'pdo_sqlite',
    'dbPort' => null,
    'dbDatabaseName' => '',
    'loginname' => 'admin',
    'password' => 'password',
    'password_retyped' => 'password',
    'rootDir' => PMF_TEST_DIR,
    'mainUrl' => 'https://localhost/',
];

Strings::init();

Request::setTrustedHosts(['^.*$']); // Trust all hosts for testing

if (!canReusePreparedTestDatabase($testDbAlias, $databaseConfigFile)) {
    if (is_link($testDbAlias)) {
        // test.db may deliberately be a symlink onto a filesystem with stable inodes:
        // the workaround for network or virtiofs mounts, where SQLite otherwise fails
        // with SQLITE_READONLY_DBMOVED. Keep such a link and rebuild only its target.
        $testDbTarget = readlink($testDbAlias);

        if ($testDbTarget !== false && is_dir(dirname($testDbTarget))) {
            // nosemgrep: php.lang.security.unlink-use.unlink-use - fixed test database path
            @unlink($testDbTarget);
        } else {
            // A dangling link, e.g. one created on another machine whose target path
            // does not exist here. file_exists() follows symlinks and reports false for
            // these, so without an explicit is_link() check the link would survive and
            // every install would fail with "unable to open database file".
            // nosemgrep: php.lang.security.unlink-use.unlink-use - fixed test database path
            @unlink($testDbAlias);
        }
    } elseif (file_exists($testDbAlias) && !is_dir($testDbAlias)) {
        // nosemgrep: php.lang.security.unlink-use.unlink-use - fixed test database path
        @unlink($testDbAlias);
    }

    // Remove the generated install marker as well. The Installer refuses to run while
    // content/core/config/database.php exists ("phpMyFAQ is already installed!"), so a
    // stale database could not be rebuilt without clearing it first.
    if (file_exists($databaseConfigFile) && !is_dir($databaseConfigFile)) {
        // nosemgrep: php.lang.security.unlink-use.unlink-use - fixed test config path
        @unlink($databaseConfigFile);
    }

    try {
        $installer = new Installer(new System());
        $installer->startInstall($setup);
    } catch (Exception $exception) {
        throw new RuntimeException(
            'PHPUnit bootstrap failed during test database installation: ' . $exception->getMessage(),
            0,
            $exception,
        );
    }

    //
    // The Installer constructs its own Configuration instances, and the first
    // Configuration created in a process becomes the global singleton (see
    // Configuration::__construct). That leaves the singleton — and, more importantly,
    // the installer's database connection — alive for the rest of the process, holding
    // an open lock on the SQLite file. Subsequent test writes from a separate connection
    // then fail with "database is locked". Drop every reference to the installer and reset
    // the singleton, then force a garbage-collection pass so the installer's connection is
    // closed and its lock released before any test runs.
    //
    unset($installer);
    new ReflectionProperty(Configuration::class, 'configuration')->setValue(null, null);
    gc_collect_cycles();
}

if (!file_exists($databaseConfigFile)) {
    throw new RuntimeException('PHPUnit bootstrap failed: tests/content/core/config/database.php was not generated.');
}

//
// Give a ParaTest worker its private content directory: a copy of the shared one,
// refreshed from it on every bootstrap so a generated config file is never stale.
// Files a previous run left behind in the copy are not removed here; tests clean up
// after themselves exactly as they do in the shared directory.
//
if (PMF_CONTENT_DIR !== PMF_SHARED_CONTENT_DIR) {
    $sharedContentFiles = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(PMF_SHARED_CONTENT_DIR, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST,
    );
    foreach ($sharedContentFiles as $sharedContentFile) {
        $target = PMF_CONTENT_DIR . substr($sharedContentFile->getPathname(), strlen(PMF_SHARED_CONTENT_DIR));
        if ($sharedContentFile->isDir()) {
            if (!is_dir($target) && !mkdir($target, 0777, true) && !is_dir($target)) {
                throw new RuntimeException('PHPUnit bootstrap failed: cannot create ' . $target);
            }
            continue;
        }
        if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0777, true) && !is_dir(dirname($target))) {
            throw new RuntimeException('PHPUnit bootstrap failed: cannot create ' . dirname($target));
        }
        if (!copy($sharedContentFile->getPathname(), $target)) {
            throw new RuntimeException('PHPUnit bootstrap failed: cannot copy ' . $sharedContentFile->getPathname());
        }
    }
}

flock($bootstrapLockHandle, LOCK_UN);
fclose($bootstrapLockHandle);

require $databaseConfigFile;

//
// Initialize the global Configuration singleton so that tests relying on
// Configuration::getConfigurationInstance() get a deterministic, database-backed
// instance regardless of test execution order. Tests that need their own
// Configuration still create one explicitly and override the singleton.
//
$bootstrapDbHandle = new Sqlite3();
$bootstrapDbHandle->connect($testDbAlias, '', '');
$bootstrapConfiguration = new Configuration($bootstrapDbHandle);

//
// Configuration::__construct does not load the stored configuration; it is read lazily
// via getAll(). Load it now so the singleton's typed getters (getVersion(), getTitle(),
// …) return their database values instead of a null that violates their non-nullable
// return types. Also give the singleton a Language, which is a runtime object rather than
// a stored value and so is not covered by getAll(). Without this full initialisation, any
// test that uses the singleton (instead of building its own Configuration) only passes
// when it happens to run after a test that set these — the execution-order dependency this
// removes.
//
$bootstrapConfiguration->getAll();
$bootstrapLanguage = new Language($bootstrapConfiguration, new Session(new MockArraySessionStorage()));
$bootstrapLanguage->setLanguageFromConfiguration('en');
$bootstrapConfiguration->setLanguage($bootstrapLanguage);
