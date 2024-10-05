<?php

namespace phpMyFAQ\Instance;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Filesystem;
use phpMyFAQ\Strings;
use PHPUnit\Framework\TestCase;
use RecursiveIteratorIterator;

/**
 * Class ClientTest
 */
class ClientTest extends TestCase
{
    /** @var Client */
    private Client $Client;

    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        $_SERVER['HTTP_HOST'] = 'example.com';

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');

        $Configuration = new Configuration($dbHandle);
        $Configuration->set('security.useSslOnly', 'true');
        $Filesystem = new Filesystem(PMF_ROOT_DIR);

        $this->Client = new Client($Configuration);

        $this->Client->setFileSystem($Filesystem);
    }

    protected function tearDown(): void
    {
        @unlink(PMF_TEST_DIR . '/constants.test.php');

        if (is_dir(PMF_TEST_DIR . '/assets/templates/default/')) {
            $files = new RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(PMF_TEST_DIR . '/assets/templates/default/'),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($files as $file) {
                @unlink($file->getPathname());
            }
        }

        parent::tearDown();
    }

    /**
     * @throws Exception
     */
    public function testCopyConstantsFile(): void
    {
        $return = $this->Client->copyConstantsFile(PMF_TEST_DIR . '/constants.test.php');

        $this->assertTrue($return);
        $this->assertFileExists(PMF_TEST_DIR . '/constants.test.php');
    }

    /**
     * @throws Exception
     */
    public function testCopyTemplateFolder(): void
    {
        $this->Client->copyTemplateFolder(PMF_TEST_DIR);

        $this->assertFileExists(PMF_TEST_DIR . '/assets/templates/default/index.twig');
    }

}
