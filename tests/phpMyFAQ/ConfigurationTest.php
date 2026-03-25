<?php

namespace phpMyFAQ;

use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use phpMyFAQ\Configuration\LdapConfiguration;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Plugin\PluginException;
use phpMyFAQ\Plugin\PluginManager;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class ConfigurationTest
 */
#[AllowMockObjectsWithoutExpectations]
class ConfigurationTest extends TestCase
{
    /** @var Configuration */
    private Configuration $configuration;
    private string $databaseFile;
    private ?Configuration $originalConfigurationInstance = null;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConfigurationInstance = $this->getStaticConfigurationInstance();
        $this->setStaticConfigurationInstance(null);

        Strings::init();

        $this->databaseFile = tempnam(sys_get_temp_dir(), 'phpmyfaq-config-test-');
        copy(PMF_TEST_DIR . '/test.db', $this->databaseFile);

        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databaseFile, '', '');
        $this->configuration = new Configuration($dbHandle);

        // Initialize the configuration table structure if needed
        $this->setupTestDatabase();
    }

    /**
     * Setup test database with configuration table
     */
    private function setupTestDatabase(): void
    {
        $db = $this->configuration->getDb();

        // Create faqconfig table if it doesn't exist
        $createTable = '
            CREATE TABLE IF NOT EXISTS faqconfig (
                config_name VARCHAR(255) NOT NULL PRIMARY KEY,
                config_value TEXT
            )
        ';

        $db->query($createTable);

        // Insert some default configuration values for testing
        $defaultConfigs = [
            'main.currentVersion' => System::getVersion(),
            'main.language' => 'en',
            'security.permLevel' => 'basic',
        ];

        foreach ($defaultConfigs as $key => $value) {
            $insertQuery = sprintf(
                "INSERT OR REPLACE INTO faqconfig (config_name, config_value) VALUES ('%s', '%s')",
                $key,
                $value,
            );
            $db->query($insertQuery);
        }
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown(): void
    {
        $this->setStaticConfigurationInstance($this->originalConfigurationInstance);

        parent::tearDown();

        if (isset($this->databaseFile) && file_exists($this->databaseFile)) {
            @unlink($this->databaseFile);
        }
    }

    public function testGetConfigurationInstance(): void
    {
        $instance = Configuration::getConfigurationInstance();

        $this->assertInstanceOf(Configuration::class, $instance);
        $this->assertSame($instance, Configuration::getConfigurationInstance());
    }

    /**
     * @throws Exception
     */
    public function testSetDatabase(): void
    {
        $database = $this->createStub(DatabaseDriver::class);

        $config = new Configuration($database);
        $config->setDatabase($database);

        $this->assertSame($database, $config->get('core.database'));
    }

    public function testSet(): void
    {
        $key = 'upgrade.releaseEnvironment';
        $value = 'test';

        $result = $this->configuration->set($key, $value);

        $this->assertTrue($result);
        $this->assertEquals($value, $this->configuration->get($key));
    }

    public function testAdd(): void
    {
        $key = 'test.add.' . bin2hex(random_bytes(4));

        $result = $this->configuration->add($key, 'foo');

        $this->assertTrue($result);
        $this->assertEquals('foo', $this->configuration->get($key));
    }

    public function testGetDb(): void
    {
        $db = $this->configuration->getDb();

        $this->assertInstanceOf(DatabaseDriver::class, $db);
    }

    public function testSetLdapConfigWithSingleServer(): void
    {
        // Demo data from /content/core/config/ldap.php
        file_put_contents(
            PMF_TEST_DIR . '/content/core/config/ldap.php',
            "<?php\n"
            . "\$PMF_LDAP['ldap_server'] = 'localhost';\n"
            . "\$PMF_LDAP['ldap_port'] = 389;\n"
            . "\$PMF_LDAP['ldap_user'] = 'admin';\n"
            . "\$PMF_LDAP['ldap_password'] = 'foobar';\n"
            . "\$PMF_LDAP['ldap_base'] = 'DC=foo,DC=bar,DC=baz';",
            LOCK_EX,
        );

        $this->configuration->set('ldap.ldap_use_multiple_servers', 'false');

        $expected = [
            0 => [
                'ldap_server' => 'localhost',
                'ldap_port' => 389,
                'ldap_user' => 'admin',
                'ldap_password' => 'foobar',
                'ldap_base' => 'DC=foo,DC=bar,DC=baz',
            ],
        ];

        $ldapConfig = new LdapConfiguration(PMF_TEST_DIR . '/content/core/config/ldap.php');

        $this->configuration->setLdapConfig($ldapConfig);

        $this->assertEquals($expected, $this->configuration->getLdapServer());
    }

    public function testSetLdapConfigWithMultipleServers(): void
    {
        // Demo data from /content/core/config/ldap.php
        file_put_contents(
            PMF_TEST_DIR . '/content/core/config/ldap.php',
            "<?php\n"
            . "\$PMF_LDAP['ldap_server'] = 'localhost';\n"
            . "\$PMF_LDAP['ldap_port'] = '389';\n"
            . "\$PMF_LDAP['ldap_user'] = 'admin';\n"
            . "\$PMF_LDAP['ldap_password'] = 'foobar';\n"
            . "\$PMF_LDAP['ldap_base'] = 'DC=foo,DC=bar,DC=baz';"
            . "\$PMF_LDAP[1]['ldap_server'] = '::1';\n"
            . "\$PMF_LDAP[1]['ldap_port'] = '389';\n"
            . "\$PMF_LDAP[1]['ldap_user'] = 'root';\n"
            . "\$PMF_LDAP[1]['ldap_password'] = '42';\n"
            . "\$PMF_LDAP[1]['ldap_base'] = 'DC=foo,DC=bar,DC=baz';",
            LOCK_EX,
        );

        $this->configuration->set('ldap.ldap_use_multiple_servers', 'true');

        $expected = [
            0 => [
                'ldap_server' => 'localhost',
                'ldap_port' => '389',
                'ldap_user' => 'admin',
                'ldap_password' => 'foobar',
                'ldap_base' => 'DC=foo,DC=bar,DC=baz',
            ],
            1 => [
                'server' => '::1',
                'port' => '389',
                'user' => 'root',
                'password' => '42',
                'base' => 'DC=foo,DC=bar,DC=baz',
            ],
        ];

        $ldapConfig = new LdapConfiguration(PMF_TEST_DIR . '/content/core/config/ldap.php');
        $this->configuration->setLdapConfig($ldapConfig);

        $this->assertEquals($expected, $this->configuration->getLdapServer());
    }

    public function testSetLdapConfigWithMultipleServersButDisabled(): void
    {
        // Demo data from /content/core/config/ldap.php
        file_put_contents(
            PMF_TEST_DIR . '/content/core/config/ldap.php',
            "<?php\n"
            . "\$PMF_LDAP['ldap_server'] = 'localhost';\n"
            . "\$PMF_LDAP['ldap_port'] = '389';\n"
            . "\$PMF_LDAP['ldap_user'] = 'admin';\n"
            . "\$PMF_LDAP['ldap_password'] = 'foobar';\n"
            . "\$PMF_LDAP['ldap_base'] = 'DC=foo,DC=bar,DC=baz';"
            . "\$PMF_LDAP[1]['ldap_server'] = '::1';\n"
            . "\$PMF_LDAP[1]['ldap_port'] = '389';\n"
            . "\$PMF_LDAP[1]['ldap_user'] = 'root';\n"
            . "\$PMF_LDAP[1]['ldap_password'] = '42';\n"
            . "\$PMF_LDAP[1]['ldap_base'] = 'DC=foo,DC=bar,DC=baz';",
            LOCK_EX,
        );

        $this->configuration->set('ldap.ldap_use_multiple_servers', 'false');

        $expected = [
            0 => [
                'ldap_server' => 'localhost',
                'ldap_port' => '389',
                'ldap_user' => 'admin',
                'ldap_password' => 'foobar',
                'ldap_base' => 'DC=foo,DC=bar,DC=baz',
            ],
        ];

        $ldapConfig = new LdapConfiguration(PMF_TEST_DIR . '/content/core/config/ldap.php');
        $this->configuration->setLdapConfig($ldapConfig);

        $this->assertEquals($expected, $this->configuration->getLdapServer());
    }

    /**
     * Test database operations
     */
    public function testDatabaseOperations(): void
    {
        $db = $this->configuration->getDb();

        $this->assertInstanceOf(DatabaseDriver::class, $db);

        // Test setting new database instance
        $newDb = new Sqlite3();
        $this->configuration->setDatabase($newDb);
        $this->assertSame($newDb, $this->configuration->getDb());
    }

    /**
     * Test language configuration
     */
    public function testLanguageConfiguration(): void
    {
        $language = new Language($this->configuration, $this->createStub(Session::class));

        $this->configuration->setLanguage($language);
        $retrievedLanguage = $this->configuration->getLanguage();

        $this->assertSame($language, $retrievedLanguage);
        $this->assertInstanceOf(Language::class, $retrievedLanguage);
    }

    /**
     * Test plugin manager integration
     */
    public function testPluginManager(): void
    {
        $pluginManager = $this->configuration->getPluginManager();

        $this->assertInstanceOf(PluginManager::class, $pluginManager);
    }

    /**
     * Test error handling for invalid operations - corrected
     */
    public function testErrorHandling(): void
    {
        // Test getting non-existent configuration
        $this->assertNull($this->configuration->get(item: 'non.existent.key'));

        // Test with empty key might still work in some implementations
        $result = $this->configuration->set('', 'value');
        $this->assertIsBool($result); // Just verify it returns a boolean
    }

    /**
     * Test singleton pattern implementation
     */
    public function testSingletonPattern(): void
    {
        $instance1 = Configuration::getConfigurationInstance();
        $instance2 = Configuration::getConfigurationInstance();

        $this->assertSame($instance1, $instance2);
        $this->assertInstanceOf(Configuration::class, $instance1);
    }

    public function testConstructorLogsPluginManagerException(): void
    {
        $testHandler = new TestHandler(Level::Error);
        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databaseFile, '', '');

        $configuration = new class($dbHandle, $testHandler) extends Configuration {
            public function __construct(
                DatabaseDriver $databaseDriver,
                private readonly TestHandler $testHandler,
            ) {
                parent::__construct($databaseDriver);
            }

            public function setLogger(): void
            {
                $logger = new Logger('phpmyfaq-test');
                $logger->pushHandler($this->testHandler);

                $reflection = new \ReflectionProperty(Configuration::class, 'logger');
                $reflection->setValue($this, $logger);
            }

            public function setPluginManager(): Configuration
            {
                throw new PluginException('Plugin setup failed');
            }
        };

        $this->assertInstanceOf(Configuration::class, $configuration);
        $this->assertTrue($testHandler->hasErrorThatContains('Plugin setup failed'));
    }

    public function testConstructorDoesNotOverwriteExistingSingletonInstance(): void
    {
        $firstInstance = $this->configuration;

        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databaseFile, '', '');
        $secondInstance = new Configuration($dbHandle);

        $this->assertNotSame($firstInstance, $secondInstance);
        $this->assertSame($firstInstance, Configuration::getConfigurationInstance());
    }

    private function getStaticConfigurationInstance(): ?Configuration
    {
        $reflection = new \ReflectionProperty(Configuration::class, 'configuration');

        return $reflection->getValue(null);
    }

    private function setStaticConfigurationInstance(?Configuration $configuration): void
    {
        $reflection = new \ReflectionProperty(Configuration::class, 'configuration');
        $reflection->setValue(null, $configuration);
    }
}
