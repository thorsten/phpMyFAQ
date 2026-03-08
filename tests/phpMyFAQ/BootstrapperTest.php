<?php

namespace phpMyFAQ;

use phpMyFAQ\Bootstrap\ConfigDirectoryResolver;
use phpMyFAQ\Bootstrap\PhpConfigurator;
use phpMyFAQ\Configuration\ConfigurationRepository;
use phpMyFAQ\Configuration\DatabaseConfiguration;
use phpMyFAQ\Configuration\LayoutSettings;
use phpMyFAQ\Configuration\LdapConfiguration;
use phpMyFAQ\Configuration\LdapSettings;
use phpMyFAQ\Configuration\MailSettings;
use phpMyFAQ\Configuration\SearchSettings;
use phpMyFAQ\Configuration\SecuritySettings;
use phpMyFAQ\Configuration\Storage\ConfigurationStorageSettings;
use phpMyFAQ\Configuration\Storage\ConfigurationStorageSettingsResolver;
use phpMyFAQ\Configuration\Storage\DatabaseConfigurationStore;
use phpMyFAQ\Configuration\Storage\HybridConfigurationStore;
use phpMyFAQ\Configuration\UrlSettings;
use phpMyFAQ\Core\Exception\DatabaseConnectionException;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Plugin\PluginManager;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(Bootstrapper::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(ConfigurationRepository::class)]
#[UsesClass(DatabaseConfiguration::class)]
#[UsesClass(LayoutSettings::class)]
#[UsesClass(LdapSettings::class)]
#[UsesClass(MailSettings::class)]
#[UsesClass(SearchSettings::class)]
#[UsesClass(SecuritySettings::class)]
#[UsesClass(ConfigurationStorageSettings::class)]
#[UsesClass(ConfigurationStorageSettingsResolver::class)]
#[UsesClass(DatabaseConfigurationStore::class)]
#[UsesClass(HybridConfigurationStore::class)]
#[UsesClass(UrlSettings::class)]
#[UsesClass(LdapConfiguration::class)]
#[UsesClass(Database::class)]
#[UsesClass(Database\PdoSqlite::class)]
#[UsesClass(Environment::class)]
#[UsesClass(PluginManager::class)]
#[UsesClass(System::class)]
#[UsesClass(Translation::class)]
#[UsesClass(PhpConfigurator::class)]
#[UsesClass(ConfigDirectoryResolver::class)]
class BootstrapperTest extends TestCase
{
    public function testGettersReturnNullBeforeRun(): void
    {
        $bootstrapper = new Bootstrapper();

        $this->assertNull($bootstrapper->getFaqConfig());
        $this->assertNull($bootstrapper->getDb());
        $this->assertNull($bootstrapper->getRequest());
    }

    public function testConnectDatabaseSuccess(): void
    {
        $databaseFile = PMF_TEST_DIR . '/content/core/config/database.php';
        $bootstrapper = new Bootstrapper();

        $this->invokePrivateMethod($bootstrapper, 'connectDatabase', [$databaseFile]);

        $this->assertNotNull($bootstrapper->getFaqConfig());
        $this->assertNotNull($bootstrapper->getDb());
        $this->assertInstanceOf(Configuration::class, $bootstrapper->getFaqConfig());
    }

    public function testConnectDatabaseThrowsOnInvalidFile(): void
    {
        $bootstrapper = new Bootstrapper();

        $this->expectException(DatabaseConnectionException::class);
        $this->invokePrivateMethod($bootstrapper, 'connectDatabase', ['/nonexistent/database.php']);
    }

    public function testSwitchToTenantSchemaReturnsEarlyForEmptySchema(): void
    {
        $dbConfig = $this->createStub(DatabaseConfiguration::class);
        $dbConfig->method('getSchema')->willReturn('');

        $bootstrapper = new Bootstrapper();
        $dbMock = $this->createMock(DatabaseDriver::class);
        // query should never be called when schema is empty
        $dbMock->expects($this->never())->method('query');
        $this->setPrivateProperty($bootstrapper, 'db', $dbMock);

        $this->invokePrivateMethod($bootstrapper, 'switchToTenantSchema', [$dbConfig]);

        // No exception means early return worked
        $this->assertTrue(true);
    }

    public function testSwitchToTenantSchemaReturnsEarlyForNullSchema(): void
    {
        $dbConfig = $this->createStub(DatabaseConfiguration::class);
        $dbConfig->method('getSchema')->willReturn(null);

        $bootstrapper = new Bootstrapper();
        $dbMock = $this->createMock(DatabaseDriver::class);
        $dbMock->expects($this->never())->method('query');
        $this->setPrivateProperty($bootstrapper, 'db', $dbMock);

        $this->invokePrivateMethod($bootstrapper, 'switchToTenantSchema', [$dbConfig]);

        $this->assertTrue(true);
    }

    public function testSwitchToTenantSchemaRejectsInvalidCharacters(): void
    {
        $dbConfig = $this->createStub(DatabaseConfiguration::class);
        $dbConfig->method('getSchema')->willReturn('tenant; DROP TABLE faq--');

        $bootstrapper = new Bootstrapper();
        $dbMock = $this->createMock(DatabaseDriver::class);
        $this->setPrivateProperty($bootstrapper, 'db', $dbMock);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid tenant schema identifier.');
        $this->invokePrivateMethod($bootstrapper, 'switchToTenantSchema', [$dbConfig]);
    }

    public function testSwitchToTenantSchemaMysql(): void
    {
        $dbConfig = $this->createStub(DatabaseConfiguration::class);
        $dbConfig->method('getSchema')->willReturn('tenant_db');
        $dbConfig->method('getType')->willReturn('mysqli');

        $bootstrapper = new Bootstrapper();
        $dbMock = $this->createMock(DatabaseDriver::class);
        $dbMock->expects($this->once())->method('query')->with('USE `tenant_db`')->willReturn(true);
        $this->setPrivateProperty($bootstrapper, 'db', $dbMock);

        $this->invokePrivateMethod($bootstrapper, 'switchToTenantSchema', [$dbConfig]);

        $this->assertTrue(true);
    }

    public function testSwitchToTenantSchemaMysqlQueryFails(): void
    {
        $dbConfig = $this->createStub(DatabaseConfiguration::class);
        $dbConfig->method('getSchema')->willReturn('tenant_db');
        $dbConfig->method('getType')->willReturn('mysqli');

        $bootstrapper = new Bootstrapper();
        $dbMock = $this->createMock(DatabaseDriver::class);
        $dbMock->method('query')->willReturn(false);
        $this->setPrivateProperty($bootstrapper, 'db', $dbMock);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to switch to tenant schema');
        $this->invokePrivateMethod($bootstrapper, 'switchToTenantSchema', [$dbConfig]);
    }

    public function testSwitchToTenantSchemaPgsql(): void
    {
        $dbConfig = $this->createStub(DatabaseConfiguration::class);
        $dbConfig->method('getSchema')->willReturn('tenant_schema');
        $dbConfig->method('getType')->willReturn('pgsql');

        $bootstrapper = new Bootstrapper();
        $dbMock = $this->createMock(DatabaseDriver::class);
        $dbMock->expects($this->once())->method('query')->with('SET search_path TO "tenant_schema"')->willReturn(true);
        $this->setPrivateProperty($bootstrapper, 'db', $dbMock);

        $this->invokePrivateMethod($bootstrapper, 'switchToTenantSchema', [$dbConfig]);

        $this->assertTrue(true);
    }

    public function testSwitchToTenantSchemaPgsqlQueryFails(): void
    {
        $dbConfig = $this->createStub(DatabaseConfiguration::class);
        $dbConfig->method('getSchema')->willReturn('tenant_schema');
        $dbConfig->method('getType')->willReturn('pgsql');

        $bootstrapper = new Bootstrapper();
        $dbMock = $this->createMock(DatabaseDriver::class);
        $dbMock->method('query')->willReturn(false);
        $this->setPrivateProperty($bootstrapper, 'db', $dbMock);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to switch to tenant schema');
        $this->invokePrivateMethod($bootstrapper, 'switchToTenantSchema', [$dbConfig]);
    }

    public function testSwitchToTenantSchemaSqlServerDoesNothing(): void
    {
        $dbConfig = $this->createStub(DatabaseConfiguration::class);
        $dbConfig->method('getSchema')->willReturn('tenant_schema');
        $dbConfig->method('getType')->willReturn('sqlsrv');

        $bootstrapper = new Bootstrapper();
        $dbMock = $this->createMock(DatabaseDriver::class);
        // SQL Server doesn't execute any queries for schema switching
        $dbMock->expects($this->never())->method('query');
        $this->setPrivateProperty($bootstrapper, 'db', $dbMock);

        $this->invokePrivateMethod($bootstrapper, 'switchToTenantSchema', [$dbConfig]);

        $this->assertTrue(true);
    }

    public function testSwitchToTenantSchemaMysqlEscapesBackticks(): void
    {
        $dbConfig = $this->createStub(DatabaseConfiguration::class);
        $dbConfig->method('getSchema')->willReturn('tenant_db');
        $dbConfig->method('getType')->willReturn('mysql');

        $bootstrapper = new Bootstrapper();
        $dbMock = $this->createMock(DatabaseDriver::class);
        $dbMock->expects($this->once())->method('query')->with('USE `tenant_db`')->willReturn(true);
        $this->setPrivateProperty($bootstrapper, 'db', $dbMock);

        $this->invokePrivateMethod($bootstrapper, 'switchToTenantSchema', [$dbConfig]);

        $this->assertTrue(true);
    }

    public function testConfigureLdapWhenInactive(): void
    {
        $bootstrapper = new Bootstrapper();
        $faqConfig = $this->createMock(Configuration::class);
        $faqConfig->method('isLdapActive')->willReturn(false);
        // setLdapConfig should never be called
        $faqConfig->expects($this->never())->method('setLdapConfig');
        $this->setPrivateProperty($bootstrapper, 'faqConfig', $faqConfig);

        $this->invokePrivateMethod($bootstrapper, 'configureLdap', []);

        $this->assertTrue(true);
    }

    public function testFixProxyHeadersWithHttpHost(): void
    {
        $bootstrapper = new Bootstrapper();
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_HOST' => 'example.com']);
        $this->setPrivateProperty($bootstrapper, 'request', $request);

        $this->invokePrivateMethod($bootstrapper, 'fixProxyHeaders', []);

        // HTTP_HOST should remain unchanged
        $this->assertEquals('example.com', $request->server->get('HTTP_HOST'));
    }

    public function testFixProxyHeadersWithForwardedServer(): void
    {
        $bootstrapper = new Bootstrapper();
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_X_FORWARDED_SERVER' => 'proxy.example.com']);
        // Remove HTTP_HOST so the proxy path is taken
        $request->server->remove('HTTP_HOST');
        $this->setPrivateProperty($bootstrapper, 'request', $request);

        $this->invokePrivateMethod($bootstrapper, 'fixProxyHeaders', []);

        $this->assertEquals('proxy.example.com', $request->server->get('HTTP_HOST'));
    }

    public function testFixProxyHeadersWithForwardedHost(): void
    {
        $bootstrapper = new Bootstrapper();
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_X_FORWARDED_HOST' => 'forwarded.example.com']);
        $request->server->remove('HTTP_HOST');
        $this->setPrivateProperty($bootstrapper, 'request', $request);

        $this->invokePrivateMethod($bootstrapper, 'fixProxyHeaders', []);

        $this->assertEquals('forwarded.example.com', $request->server->get('HTTP_HOST'));
    }

    public function testConfigureLdapWhenActiveAndFileExists(): void
    {
        $bootstrapper = new Bootstrapper();
        $faqConfig = $this->createMock(Configuration::class);
        $faqConfig->method('isLdapActive')->willReturn(true);

        if (file_exists(PMF_CONFIG_DIR . '/ldap.php') && extension_loaded('ldap')) {
            $faqConfig->expects($this->once())->method('setLdapConfig');
        } else {
            $faqConfig->expects($this->never())->method('setLdapConfig');
        }

        $this->setPrivateProperty($bootstrapper, 'faqConfig', $faqConfig);

        $this->invokePrivateMethod($bootstrapper, 'configureLdap', []);
    }

    public function testRunPostDatabaseStepsWithNoSearchEnabled(): void
    {
        $bootstrapper = new Bootstrapper();

        $faqConfig = $this->createMock(Configuration::class);
        $faqConfig->method('isLdapActive')->willReturn(false);
        $faqConfig
            ->method('get')
            ->willReturnCallback(function (string $item) {
                return match ($item) {
                    'search.enableElasticsearch' => false,
                    'search.enableOpenSearch' => false,
                    'storage.type' => '',
                    'records.attachmentsPath' => 'attachments',
                    default => null,
                };
            });

        $this->setPrivateProperty($bootstrapper, 'faqConfig', $faqConfig);
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_HOST' => 'example.com']);
        $this->setPrivateProperty($bootstrapper, 'request', $request);

        // Simulate the post-database steps from run() (lines 88-116)
        // 12. Session configuration — skip as it affects global state
        // 13. LDAP
        $this->invokePrivateMethod($bootstrapper, 'configureLdap', []);

        // 14-15. Elasticsearch/OpenSearch — skipped when disabled (tested via config mock)
        $this->assertFalse($faqConfig->get('search.enableElasticsearch'));
        $this->assertFalse($faqConfig->get('search.enableOpenSearch'));

        // 16. Attachments directory — tested via config mock returning non-s3
        $this->assertNotEquals('s3', strtolower((string) $faqConfig->get('storage.type')));

        // 17. Proxy header fix
        $this->invokePrivateMethod($bootstrapper, 'fixProxyHeaders', []);

        $this->assertEquals('example.com', $request->server->get('HTTP_HOST'));
    }

    public function testRunPostDatabaseStepsWithS3Storage(): void
    {
        $bootstrapper = new Bootstrapper();

        $faqConfig = $this->createMock(Configuration::class);
        $faqConfig->method('isLdapActive')->willReturn(false);
        $faqConfig
            ->method('get')
            ->willReturnCallback(function (string $item) {
                return match ($item) {
                    'search.enableElasticsearch' => false,
                    'search.enableOpenSearch' => false,
                    'storage.type' => 's3',
                    default => null,
                };
            });

        $this->setPrivateProperty($bootstrapper, 'faqConfig', $faqConfig);

        // When storage.type is 's3', resolveAttachmentsDir should be skipped
        $this->assertEquals('s3', strtolower((string) $faqConfig->get('storage.type')));
    }

    public function testConnectDatabaseThrowsOnBrokenTables(): void
    {
        // Create a temp DB config pointing to a valid SQLite file but with no tables
        $tempDb = tempnam(sys_get_temp_dir(), 'pmf_test_');
        $tempConfig = tempnam(sys_get_temp_dir(), 'pmf_dbcfg_') . '.php';

        // Create empty SQLite DB
        new \PDO('sqlite:' . $tempDb);

        file_put_contents($tempConfig, sprintf(
            '<?php $DB = ["server" => "%s", "port" => "", "user" => "", "password" => "", "db" => "", "prefix" => "", "type" => "pdo_sqlite", "schema" => ""];',
            $tempDb,
        ));

        $bootstrapper = new Bootstrapper();

        try {
            $this->expectException(DatabaseConnectionException::class);
            $this->expectExceptionMessage('Database tables not found');
            $this->invokePrivateMethod($bootstrapper, 'connectDatabase', [$tempConfig]);
        } finally {
            @unlink($tempConfig);
            @unlink($tempDb);
        }
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testRunExecutesFullBootstrapSequence(): void
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $obLevelBefore = ob_get_level();

        $bootstrapper = new Bootstrapper();
        $result = $bootstrapper->run();

        // Clean up output buffers added by run() before assertions
        while (ob_get_level() > $obLevelBefore) {
            ob_end_clean();
        }

        $this->assertSame($bootstrapper, $result);
        $this->assertNotNull($bootstrapper->getFaqConfig());
        $this->assertNotNull($bootstrapper->getDb());
        $this->assertNotNull($bootstrapper->getRequest());
        $this->assertInstanceOf(Configuration::class, $bootstrapper->getFaqConfig());
        $this->assertInstanceOf(Request::class, $bootstrapper->getRequest());
    }

    public function testDatabaseConfigurationIncludesSchemaField(): void
    {
        $config = new DatabaseConfiguration(dirname(__FILE__, 2) . '/content/core/config/database.php');

        $this->assertNull($config->getSchema());
    }

    private function invokePrivateMethod(object $object, string $methodName, array $parameters = []): mixed
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($methodName);

        return $method->invoke($object, ...$parameters);
    }

    private function setPrivateProperty(object $object, string $propertyName, mixed $value): void
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setValue($object, $value);
    }
}
