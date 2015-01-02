<?php
/**
 * Test case for PMF_Search_Database
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
 * @copyright 2012-2015 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2012-12-31
 */

require_once dirname(dirname(__DIR__)) . '/phpmyfaq/inc/PMF/Exception.php';
require_once dirname(dirname(__DIR__)) . '/phpmyfaq/inc/PMF/Attachment/Exception.php';
require_once dirname(dirname(__DIR__)) . '/phpmyfaq/inc/PMF/Attachment/Filesystem/Entry.php';
require_once dirname(dirname(__DIR__)) . '/phpmyfaq/inc/PMF/Attachment/Filesystem/File.php';
require_once dirname(dirname(__DIR__)) . '/phpmyfaq/inc/PMF/Attachment/Filesystem/File/Vanilla.php';
require_once dirname(dirname(__DIR__)) . '/phpmyfaq/inc/PMF/Attachment/Filesystem/File/Exception.php';


/**
 * PMF_Attachment_File test case
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2015 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2012-12-31
 */
class PMF_Attachment_Filesystem_File_VanillaTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PMF_Attachment_Filesystem_File
     */
    private $PMF_Attachment_Filesystem_File_Vanilla;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();

        if (!file_exists(dirname(__DIR__) . '/fixtures/path-to-delete/')) {
            mkdir(dirname(__DIR__) . '/fixtures/path-to-delete/');
        }
        copy(dirname(__DIR__) . '/fixtures/path/foo.bar', dirname(__DIR__) . '/fixtures/path-to-delete/foo.bar.baz');

        $this->PMF_Attachment_Filesystem_File_Vanilla = new PMF_Attachment_Filesystem_File_Vanilla(
            dirname(__DIR__) . '/fixtures/path-to-delete/foo.bar.baz'
        );
    }

    public function testDelete()
    {
        copy(dirname(__DIR__) . '/fixtures/path/foo.bar', dirname(__DIR__) . '/fixtures/path-to-delete/foo.bar.baz');

        $this->assertTrue($this->PMF_Attachment_Filesystem_File_Vanilla->delete());
    }

    public function testDeleteDir()
    {
        copy(dirname(__DIR__) . '/fixtures/path/foo.bar', dirname(__DIR__) . '/fixtures/path-to-delete/foo.bar');

        $this->assertTrue(
            $this->PMF_Attachment_Filesystem_File_Vanilla->deleteDir(
                dirname(__DIR__) . '/fixtures/path-to-delete/'
            )
        );
    }

}