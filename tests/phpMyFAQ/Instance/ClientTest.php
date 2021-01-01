<?php
/**
 * Test case for Instance Client class
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2012-03-29
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Filesystem;
use phpMyFAQ\Instance;
use phpMyFAQ\Instance\Client;
use phpMyFAQ\Strings;
use PHPUnit\Framework\TestCase;

/**
 * Class ClientTest
 */
class ClientTest extends TestCase
{
    /** @var Client */
    private $Client;

    protected function setUp(): void
    {
        parent::setUp();

        Strings::init('en');

        $_SERVER['HTTP_HOST'] = 'faq.example.org';

        $dbHandle = new Sqlite3();
        $Configuration = new Configuration($dbHandle);
        $Configuration->config['security.useSslOnly'] = 'true';
        $Filesystem = new Filesystem(PMF_ROOT_DIR);

        $Instance = new Instance($Configuration);
        $this->Client = new Client($Configuration);

        $this->Client->setFileSystem($Filesystem);
    }

    protected function tearDown(): void
    {
        $this->Client = null;
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
        $return = $this->Client->copyConstantsFile(PMF_TEST_DIR . '/constants.test.php');

        $this->assertTrue($return);
        $this->assertFileExists(PMF_TEST_DIR . '/constants.test.php');
    }

    public function testCopyTemplateFolder()
    {
        $this->Client->copyTemplateFolder(PMF_TEST_DIR);

        $this->assertFileExists(PMF_TEST_DIR . '/assets/themes/default/templates/index.html');
    }

}
