<?php

declare(strict_types=1);

namespace phpMyFAQ\Setup;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\System;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Runs a complete SQLite installation into a temporary root directory.
 *
 * Only paths that cannot trigger Installer::cleanFailedInstallationFiles() are exercised:
 * that cleanup deletes content/core/config/database.php below PMF_ROOT_DIR, which is the
 * checked-out application, not the temporary root used here.
 */
#[CoversClass(InstallationRunner::class)]
#[UsesNamespace('phpMyFAQ')]
final class InstallationRunnerIntegrationTest extends TestCase
{
    private string $rootDir;

    private ?Configuration $previousConfiguration = null;

    private mixed $previousDatabaseDriver = null;

    private mixed $previousDatabaseType = null;

    private string $previousTablePrefix = '';

    protected function setUp(): void
    {
        $this->rootDir = sys_get_temp_dir() . '/pmf-install-' . bin2hex(random_bytes(6));
        mkdir($this->rootDir . '/content/core/config', 0777, true);

        $this->previousConfiguration = new ReflectionProperty(Configuration::class, 'configuration')->getValue();
        $this->previousDatabaseDriver = new ReflectionProperty(Database::class, 'databaseDriver')->getValue();
        $this->previousDatabaseType = new ReflectionProperty(Database::class, 'dbType')->getValue();
        $this->previousTablePrefix = Database::getTablePrefix();
    }

    protected function tearDown(): void
    {
        new ReflectionProperty(Configuration::class, 'configuration')->setValue(null, $this->previousConfiguration);
        new ReflectionProperty(Database::class, 'databaseDriver')->setValue(null, $this->previousDatabaseDriver);
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, $this->previousDatabaseType);
        Database::setTablePrefix($this->previousTablePrefix);

        new Filesystem()->remove($this->rootDir);
    }

    /**
     * The runner's input as the web and headless installers hand it over; the four personal
     * settings default like the validator defaults them.
     *
     * @param array<string, mixed> $dbOverrides
     */
    private function createInput(
        array $dbOverrides = [],
        string $language = 'en',
        string $realname = 'Admin User',
        string $email = 'admin@example.org',
        string $permLevel = 'basic',
    ): InstallationInput {
        return new InstallationInput(
            dbSetup: array_merge([
                'dbType' => 'sqlite3',
                'dbServer' => $this->rootDir . '/install.db',
                'dbPort' => null,
                'dbUser' => '',
                'dbPassword' => '',
                'dbDatabaseName' => '',
                'dbPrefix' => '',
            ], $dbOverrides),
            ldapSetup: [],
            esSetup: [],
            osSetup: [],
            loginName: 'admin',
            password: 'password',
            language: $language,
            realname: $realname,
            email: $email,
            permLevel: $permLevel,
            rootDir: $this->rootDir,
        );
    }

    private function connect(): Sqlite3
    {
        $db = new Sqlite3();
        $db->connect($this->rootDir . '/install.db', '', '');

        return $db;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rows(Sqlite3 $db, string $sql): array
    {
        $rows = [];
        foreach ($db->fetchAll($db->query($sql)) ?? [] as $row) {
            $rows[] = (array) $row;
        }

        return $rows;
    }

    public function testRunInstallsACompleteSqliteInstallation(): void
    {
        new InstallationRunner(new System())->run($this->createInput());

        $this->assertFileExists($this->rootDir . '/content/core/config/database.php');
        $this->assertFileDoesNotExist($this->rootDir . '/content/core/config/ldap.php');

        $DB = [];
        include $this->rootDir . '/content/core/config/database.php';
        $this->assertSame($this->rootDir . '/install.db', $DB['server']);
        $this->assertSame('sqlite3', $DB['type']);
        $this->assertSame('', $DB['prefix']);

        $db = $this->connect();

        $users = [];
        foreach ($this->rows($db, 'SELECT user_id, login, account_status, is_superadmin FROM faquser') as $row) {
            $users[(string) $row['login']] = $row;
        }
        $this->assertEqualsCanonicalizing(['admin', 'anonymous'], array_keys($users));
        $this->assertSame(1, (int) $users['admin']['user_id']);
        $this->assertSame('protected', $users['admin']['account_status']);
        $this->assertSame(1, (int) $users['admin']['is_superadmin']);
        $this->assertSame(-1, (int) $users['anonymous']['user_id']);
        $this->assertSame('protected', $users['anonymous']['account_status']);
        $this->assertSame(0, (int) $users['anonymous']['is_superadmin']);

        $adminData = $this->rows($db, 'SELECT display_name, email FROM faquserdata WHERE user_id = 1');
        $this->assertSame('Admin User', $adminData[0]['display_name']);
        $this->assertSame('admin@example.org', $adminData[0]['email']);

        $rights = $this->rows($db, 'SELECT COUNT(*) AS amount FROM faqright');
        $this->assertGreaterThan(0, (int) $rights[0]['amount']);
        $adminRights = $this->rows($db, 'SELECT COUNT(*) AS amount FROM faquser_right WHERE user_id = 1');
        $this->assertSame((int) $rights[0]['amount'], (int) $adminRights[0]['amount']);

        $this->assertGreaterThan(0, (int) $this->rows($db, 'SELECT COUNT(*) AS amount FROM faqstopwords')[0]['amount']);
        $this->assertGreaterThan(0, (int) $this->rows($db, 'SELECT COUNT(*) AS amount FROM faqforms')[0]['amount']);
        $this->assertSame(1, (int) $this->rows($db, 'SELECT COUNT(*) AS amount FROM faqinstances')[0]['amount']);

        $config = [];
        foreach ($this->rows($db, 'SELECT config_name, config_value FROM faqconfig') as $row) {
            $config[(string) $row['config_name']] = (string) $row['config_value'];
        }

        $this->assertSame('Admin User', $config['main.metaPublisher']);
        $this->assertSame('admin@example.org', $config['main.administrationMail']);
        $this->assertSame(System::getVersion(), $config['main.currentVersion']);
        $this->assertStringStartsWith('http', $config['main.referenceURL']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $config['security.salt']);

        $db->close();
    }

    public function testRunStoresTheRequestedPermissionLevelAndLanguage(): void
    {
        new InstallationRunner(new System())->run($this->createInput(language: 'de', permLevel: 'medium'));

        $db = $this->connect();
        $config = [];
        foreach ($this->rows($db, 'SELECT config_name, config_value FROM faqconfig') as $row) {
            $config[(string) $row['config_name']] = (string) $row['config_value'];
        }
        $db->close();

        $this->assertSame('medium', $config['security.permLevel']);
        $this->assertSame('de', $config['main.language']);
    }

    public function testRunRejectsAnUnknownDatabaseTypeBeforeTouchingTheFilesystem(): void
    {
        try {
            new InstallationRunner(new System())->run($this->createInput(['dbType' => 'nosuchdb']));
            $this->fail('An unknown database type must be rejected.');
        } catch (Exception $exception) {
            $this->assertStringContainsString('nosuchdb', $exception->getMessage());
        }

        $this->assertFileDoesNotExist($this->rootDir . '/content/core/config/database.php');
        $this->assertFileDoesNotExist($this->rootDir . '/install.db');
    }
}
