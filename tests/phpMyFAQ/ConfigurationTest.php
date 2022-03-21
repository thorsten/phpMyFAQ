<?php

namespace phpMyFAQ;

use phpMyFAQ\Configuration;
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

        Strings::init('en');

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

    public function testSetLdapConfigWithSingleServer()
    {
        global $PMF_LDAP;

        // Demo data from config/ldap.php
        $PMF_LDAP["ldap_server"] = 'localhost';
        $PMF_LDAP["ldap_port"] = '389';
        $PMF_LDAP["ldap_user"] = 'admin';
        $PMF_LDAP["ldap_password"] = 'foobar';
        $PMF_LDAP["ldap_base"] = 'DC=foo,DC=bar,DC=baz';
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

        $this->Configuration->setLdapConfig($PMF_LDAP);

        $this->assertEquals($expected, $this->Configuration->getLdapServer());
    }

    public function testSetLdapConfigWithMultipleServers()
    {
        global $PMF_LDAP;

        // Demo data from config/ldap.php:
        $PMF_LDAP["ldap_server"] = 'localhost';
        $PMF_LDAP["ldap_port"] = '389';
        $PMF_LDAP["ldap_user"] = 'admin';
        $PMF_LDAP["ldap_password"] = 'foobar';
        $PMF_LDAP["ldap_base"] = 'DC=foo,DC=bar,DC=baz';
        $this->Configuration->set('ldap.ldap_use_multiple_servers', 'true');

        // Second server
        $PMF_LDAP[1]["ldap_server"] = '::1';
        $PMF_LDAP[1]["ldap_port"] = '389';
        $PMF_LDAP[1]["ldap_user"] = 'root';
        $PMF_LDAP[1]["ldap_password"] = '42';
        $PMF_LDAP[1]["ldap_base"] = 'DC=foo,DC=bar,DC=baz';

        $expected = [
            0 => [
                'ldap_server' => 'localhost',
                'ldap_port' => '389',
                'ldap_user' => 'admin',
                'ldap_password' => 'foobar',
                'ldap_base' => 'DC=foo,DC=bar,DC=baz'
                ],
            1 => [
                'ldap_server' => '::1',
                'ldap_port' => '389',
                'ldap_user' => 'root',
                'ldap_password' => '42',
                'ldap_base' => 'DC=foo,DC=bar,DC=baz'
            ]
        ];

        $this->Configuration->setLdapConfig($PMF_LDAP);

        $this->assertEquals($expected, $this->Configuration->getLdapServer());
    }

    public function testSetLdapConfigWithMultipleServersButDisabled()
    {
        global $PMF_LDAP;

        // Demo data from config/ldap.php:
        $PMF_LDAP["ldap_server"] = 'localhost';
        $PMF_LDAP["ldap_port"] = '389';
        $PMF_LDAP["ldap_user"] = 'admin';
        $PMF_LDAP["ldap_password"] = 'foobar';
        $PMF_LDAP["ldap_base"] = 'DC=foo,DC=bar,DC=baz';
        $this->Configuration->set('ldap.ldap_use_multiple_servers', 'false');
        // Second server
        $PMF_LDAP[1]["ldap_server"] = '::1';
        $PMF_LDAP[1]["ldap_port"] = '389';
        $PMF_LDAP[1]["ldap_user"] = 'root';
        $PMF_LDAP[1]["ldap_password"] = '42';
        $PMF_LDAP[1]["ldap_base"] = 'DC=foo,DC=bar,DC=baz';

        $expected = [
            0 => [
                'ldap_server' => 'localhost',
                'ldap_port' => '389',
                'ldap_user' => 'admin',
                'ldap_password' => 'foobar',
                'ldap_base' => 'DC=foo,DC=bar,DC=baz'
            ]
        ];

        $this->Configuration->setLdapConfig($PMF_LDAP);

        $this->assertEquals($expected, $this->Configuration->getLdapServer());
    }
}
