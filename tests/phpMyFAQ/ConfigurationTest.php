<?php

namespace phpMyFAQ;

use phpMyFAQ\Configuration\LdapConfiguration;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

/**
 * Class ConfigurationTest
 */
class ConfigurationTest extends TestCase
{
    /** @var Configuration */
    private $configuration;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown(): void
    {
        $this->configuration = null;
        parent::tearDown();
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
        $database = $this->createMock(DatabaseDriver::class);

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
        $key = 'test.add';

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
            "<?php\n" .
            "\$PMF_LDAP['ldap_server'] = 'localhost';\n" .
            "\$PMF_LDAP['ldap_port'] = '389';\n" .
            "\$PMF_LDAP['ldap_user'] = 'admin';\n" .
            "\$PMF_LDAP['ldap_password'] = 'foobar';\n" .
            "\$PMF_LDAP['ldap_base'] = 'DC=foo,DC=bar,DC=baz';",
            LOCK_EX
        );

        $this->configuration->set('ldap.ldap_use_multiple_servers', 'false');

        $expected = [
            0 => [
                'ldap_server' => 'localhost',
                'ldap_port' => '389',
                'ldap_user' => 'admin',
                'ldap_password' => 'foobar',
                'ldap_base' => 'DC=foo,DC=bar,DC=baz'
            ]
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
            "<?php\n" .
            "\$PMF_LDAP['ldap_server'] = 'localhost';\n" .
            "\$PMF_LDAP['ldap_port'] = '389';\n" .
            "\$PMF_LDAP['ldap_user'] = 'admin';\n" .
            "\$PMF_LDAP['ldap_password'] = 'foobar';\n" .
            "\$PMF_LDAP['ldap_base'] = 'DC=foo,DC=bar,DC=baz';" .
            "\$PMF_LDAP[1]['ldap_server'] = '::1';\n" .
            "\$PMF_LDAP[1]['ldap_port'] = '389';\n" .
            "\$PMF_LDAP[1]['ldap_user'] = 'root';\n" .
            "\$PMF_LDAP[1]['ldap_password'] = '42';\n" .
            "\$PMF_LDAP[1]['ldap_base'] = 'DC=foo,DC=bar,DC=baz';",
            LOCK_EX
        );

        $this->configuration->set('ldap.ldap_use_multiple_servers', 'true');

        $expected = [
            0 => [
                'ldap_server' => 'localhost',
                'ldap_port' => '389',
                'ldap_user' => 'admin',
                'ldap_password' => 'foobar',
                'ldap_base' => 'DC=foo,DC=bar,DC=baz'
                ],
            1 => [
                'server' => '::1',
                'port' => '389',
                'user' => 'root',
                'password' => '42',
                'base' => 'DC=foo,DC=bar,DC=baz'
            ]
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
            "<?php\n" .
            "\$PMF_LDAP['ldap_server'] = 'localhost';\n" .
            "\$PMF_LDAP['ldap_port'] = '389';\n" .
            "\$PMF_LDAP['ldap_user'] = 'admin';\n" .
            "\$PMF_LDAP['ldap_password'] = 'foobar';\n" .
            "\$PMF_LDAP['ldap_base'] = 'DC=foo,DC=bar,DC=baz';" .
            "\$PMF_LDAP[1]['ldap_server'] = '::1';\n" .
            "\$PMF_LDAP[1]['ldap_port'] = '389';\n" .
            "\$PMF_LDAP[1]['ldap_user'] = 'root';\n" .
            "\$PMF_LDAP[1]['ldap_password'] = '42';\n" .
            "\$PMF_LDAP[1]['ldap_base'] = 'DC=foo,DC=bar,DC=baz';",
            LOCK_EX
        );

        $this->configuration->set('ldap.ldap_use_multiple_servers', 'false');

        $expected = [
            0 => [
                'ldap_server' => 'localhost',
                'ldap_port' => '389',
                'ldap_user' => 'admin',
                'ldap_password' => 'foobar',
                'ldap_base' => 'DC=foo,DC=bar,DC=baz'
            ]
        ];

        $ldapConfig = new LdapConfiguration(PMF_TEST_DIR . '/content/core/config/ldap.php');
        $this->configuration->setLdapConfig($ldapConfig);

        $this->assertEquals($expected, $this->configuration->getLdapServer());
    }

    public function testGetLdapGroupConfig(): void
    {
        // Test default values
        $expected = [
            'use_group_restriction' => 'false',
            'allowed_groups' => [],
            'auto_assign' => 'false',
            'group_mapping' => [],
        ];
        
        $this->assertEquals($expected, $this->configuration->getLdapGroupConfig());
        
        // Test with configured values
        $this->configuration->set('ldap.ldap_use_group_restriction', 'true');
        $this->configuration->set('ldap.ldap_group_allowed_groups', 'Domain Users,Domain Admins');
        $this->configuration->set('ldap.ldap_group_auto_assign', 'true');
        $this->configuration->set('ldap.ldap_group_mapping', '{"Domain Admins": "Administrators"}');
        
        $expected = [
            'use_group_restriction' => 'true',
            'allowed_groups' => ['Domain Users', 'Domain Admins'],
            'auto_assign' => 'true',
            'group_mapping' => ['Domain Admins' => 'Administrators'],
        ];
        
        $this->assertEquals($expected, $this->configuration->getLdapGroupConfig());
    }
}
