<?php

/**
 * Attachment Tests
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2021-03-14
 */

namespace phpMyFAQ;

use PHPUnit\Framework\TestCase;

/**
 * Class AttachmentTest
 *
 * @testdox Attachments should
 * @package phpMyFAQ
 */
class AttachmentTest extends TestCase
{
    /**
     * @testdox return 0 for the storage type filesystem
     */
    public function testStorageFileSystem()
    {
        $this->assertEquals(0, Attachment::STORAGE_TYPE_FILESYSTEM);
    }

    /**
     * @testdox return 1 for the storage type filesystem
     */
    public function testStorageDatabaseSystem()
    {
        $this->assertEquals(1, Attachment::STORAGE_TYPE_DB);
    }
}
