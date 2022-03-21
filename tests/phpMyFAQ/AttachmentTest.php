<?php

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
    public function testStorageFileSystem(): void
    {
        $this->assertEquals(0, Attachment::STORAGE_TYPE_FILESYSTEM);
    }

    /**
     * @testdox return 1 for the storage type filesystem
     */
    public function testStorageDatabaseSystem(): void
    {
        $this->assertEquals(1, Attachment::STORAGE_TYPE_DB);
    }
}
