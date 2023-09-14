<?php

namespace phpMyFAQ;

use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\LdapConfiguration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Strings;
use PHPUnit\Framework\TestCase;

/**
 * Class ConfigurationTest
 */
class ConfigurationTest extends TestCase
{
    /** @var Configuration */
    private $Configuration;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->Configuration = new Configuration($dbHandle);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown(): void
    {
        $this->Configuration = null;
        parent::tearDown();
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

        $this->Configuration->set('ldap.ldap_use_multiple_servers', 'false');

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

        $this->Configuration->setLdapConfig($ldapConfig);

        $this->assertEquals($expected, $this->Configuration->getLdapServer());
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

        $this->Configuration->set('ldap.ldap_use_multiple_servers', 'true');

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
        $this->Configuration->setLdapConfig($ldapConfig);

        $this->assertEquals($expected, $this->Configuration->getLdapServer());
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

        $this->Configuration->set('ldap.ldap_use_multiple_servers', 'false');

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
        $this->Configuration->setLdapConfig($ldapConfig);

        $this->assertEquals($expected, $this->Configuration->getLdapServer());
    }
}
