<?php
/**
 * Test case for PMF_Link
 *
 * PHP Version 5.4
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-03-29
 */

/**
 * PMF_LinkTest
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-03-29
 */
class PMFTest_Instance_ClientTest extends PHPUnit_Framework_TestCase
{
    private $dbHandle;
    private $PMF_Filesystem;
    private $PMF_Instance;
    private $PMF_Instance_Client;
    private $PMF_Configuration;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();

        PMF_String::init('en');

        $_SERVER['HTTP_HOST'] = 'faq.example.org';

        $this->dbHandle = new PMF_DB_Sqlite3();
        $this->PMF_Configuration = new PMF_Configuration($this->dbHandle);
        $this->PMF_Configuration->config['security.useSslOnly'] = 'true';
        $this->PMF_Filesystem = new PMF_Filesystem(PMF_ROOT_DIR);

        $this->PMF_Instance = new PMF_Instance($this->PMF_Configuration);
        $this->PMF_Instance_Client = new PMF_Instance_Client($this->PMF_Configuration);

        $this->PMF_Instance_Client->setFileSystem($this->PMF_Filesystem);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        $this->PMF_Instance_Client = null;
        @unlink(PMF_TEST_DIR . '/constants.test.php');
        @unlink(PMF_TEST_DIR . '/constants_ldap.test.php');
        
        if (is_dir(PMF_TEST_DIR . '/assets/template/default/')) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(PMF_TEST_DIR . '/assets/template/default/'),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($files as $file) {
                @unlink($file->getPathname());
            }
        }
        
        parent::tearDown();
    }

    public function testCopyConstantsFile()
    {
        $return = $this->PMF_Instance_Client->copyConstantsFile(PMF_TEST_DIR . '/constants.test.php');

        $this->assertTrue($return);
        $this->assertFileExists(PMF_TEST_DIR . '/constants.test.php');
    }

    public function testCopyLdapConstantsFile()
    {
        $return = $this->PMF_Instance_Client->copyLdapConstantsFile(PMF_TEST_DIR . '/constants_ldap.test.php');

        $this->assertTrue($return);
        $this->assertFileExists(PMF_TEST_DIR . '/constants_ldap.test.php');
    }

    public function testCopyTemplateFolder()
    {
        $this->PMF_Instance_Client->copyTemplateFolder(PMF_TEST_DIR);

        $this->assertFileExists(PMF_TEST_DIR . '/assets/template/default/index.tpl');
    }
}
