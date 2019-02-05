<?php
/**
 * Test suite for PMF_Configuration
 *
 *
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @package   PMF_Tests
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2013 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2013-12-30
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Db\Sqlite3;
use phpMyFAQ\Strings;
use PHPUnit\Framework\TestCase;

/**
 * Configuration_AllTests
 *
 * @package phpMyFAQ
 * @package   PMF_Tests
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2013 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2013-12-30
 */
class ConfigurationTest extends TestCase
{
    private $pmfConfig;
    private $dbHandle;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();

        Strings::init('en');

        $this->dbHandle  = new Sqlite3();
        $this->dbHandle->connect(PMF_TEST_DIR.'/test.db', '', '');
        $this->pmfConfig = new Configuration($this->dbHandle);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        $this->pmfConfig = null;
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
        $this->pmfConfig->set('ldap.ldap_use_multiple_servers', 'false');

        $expected = [
            0 => [
                'ldap_server' => 'localhost',
                'ldap_port' => '389',
                'ldap_user' => 'admin',
                'ldap_password' => 'foobar',
                'ldap_base' => 'DC=foo,DC=bar,DC=baz'
            ]
        ];

        $this->pmfConfig->setLdapConfig($PMF_LDAP);

        $this->assertEquals($expected, $this->pmfConfig->getLdapServer());
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
        $this->pmfConfig->set('ldap.ldap_use_multiple_servers', 'true');

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

        $this->pmfConfig->setLdapConfig($PMF_LDAP);

        $this->assertEquals($expected, $this->pmfConfig->getLdapServer());
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
        $this->pmfConfig->set('ldap.ldap_use_multiple_servers', 'false');
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

        $this->pmfConfig->setLdapConfig($PMF_LDAP);

        $this->assertEquals($expected, $this->pmfConfig->getLdapServer());
    }
}
