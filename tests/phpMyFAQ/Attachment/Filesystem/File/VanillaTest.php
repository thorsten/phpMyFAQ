<?php
/**
 * Test case for phpMyFAQ\Attachment\Filesystem\File\Vanilla
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2022 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link https://www.phpmyfaq.de
 * @since 2012-12-31
 */

use phpMyFAQ\Attachment\Filesystem\File\VanillaFile;
use PHPUnit\Framework\TestCase;

/**
 * Class VanillaTest
 */
class VanillaTest extends TestCase
{
    /** @var VanillaFile*/
    private $instance;

    public function testDelete()
    {
        copy(PMF_TEST_DIR . '/fixtures/path/foo.bar', PMF_TEST_DIR . '/fixtures/path-to-delete/foo.bar.baz');

        $this->assertTrue($this->instance->delete());
    }

    public function testDeleteDir()
    {
        copy(PMF_TEST_DIR . '/fixtures/path/foo.bar', PMF_TEST_DIR . '/fixtures/path-to-delete/foo.bar');

        $this->assertTrue(
            $this->instance->deleteDir(
                PMF_TEST_DIR . '/fixtures/path-to-delete/'
            )
        );
    }

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (!file_exists(PMF_TEST_DIR . '/fixtures/path-to-delete/')) {
            mkdir(PMF_TEST_DIR . '/fixtures/path-to-delete/');
        }
        copy(PMF_TEST_DIR . '/fixtures/path/foo.bar', PMF_TEST_DIR . '/fixtures/path-to-delete/foo.bar.baz');

        $this->instance = new VanillaFile(
            PMF_TEST_DIR . '/fixtures/path-to-delete/foo.bar.baz'
        );
    }

}
