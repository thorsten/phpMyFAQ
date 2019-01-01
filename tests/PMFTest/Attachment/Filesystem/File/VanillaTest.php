<?php
/**
 * Test case for phpMyFAQ\Attachment\Filesystem\File\Vanilla
 *
 * PHP Version 5.3
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      https://www.phpmyfaq.de
 * @since     2012-12-31
 */

use PHPUnit\Framework\TestCase;
use phpMyFAQ\Attachment\Filesystem\File\Vanilla;

/**
 * PMF_Attachment_File test case
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      https://www.phpmyfaq.de
 * @since     2012-12-31
 */
class VanillaTest extends TestCase
{
    /**
     * @var Vanilla
     */
    private $instance;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();

        if (!file_exists(PMF_TEST_DIR . '/fixtures/path-to-delete/')) {
            mkdir(PMF_TEST_DIR . '/fixtures/path-to-delete/');
        }
        copy(PMF_TEST_DIR . '/fixtures/path/foo.bar', PMF_TEST_DIR . '/fixtures/path-to-delete/foo.bar.baz');

        $this->instance = new Vanilla(
            PMF_TEST_DIR . '/fixtures/path-to-delete/foo.bar.baz'
        );
    }

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

}