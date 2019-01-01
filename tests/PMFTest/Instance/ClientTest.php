<?php
/**
 * Test case for Link
 *
 *
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-03-29
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Filesystem;
use phpMyFAQ\Db\Sqlite3;
use phpMyFAQ\Instance;
use phpMyFAQ\Instance\Client;
use phpMyFAQ\Strings;
use PHPUnit\Framework\TestCase;

/**
 * LinkTest
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-03-29
 */
class ClientTest extends TestCase
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

        Strings::init('en');

        $_SERVER['HTTP_HOST'] = 'faq.example.org';

        $this->dbHandle = new Sqlite3();
        $this->PMF_Configuration = new Configuration($this->dbHandle);
        $this->PMF_Configuration->config['security.useSslOnly'] = 'true';
        $this->PMF_Filesystem = new Filesystem(PMF_ROOT_DIR);

        $this->PMF_Instance = new Instance($this->PMF_Configuration);
        $this->PMF_Instance_Client = new Client($this->PMF_Configuration);

        $this->PMF_Instance_Client->setFileSystem($this->PMF_Filesystem);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        $this->PMF_Instance_Client = null;
        @unlink(PMF_TEST_DIR . '/constants.test.php');

        if (is_dir(PMF_TEST_DIR . '/assets/themes/default/')) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(PMF_TEST_DIR . '/assets/themes/default/'),
                \RecursiveIteratorIterator::SELF_FIRST
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

    public function testCopyTemplateFolder()
    {
        $this->PMF_Instance_Client->copyTemplateFolder(PMF_TEST_DIR);

        $this->assertFileExists(PMF_TEST_DIR . '/assets/themes/default/templates/index.html');
    }
}
