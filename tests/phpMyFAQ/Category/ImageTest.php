<?php

namespace phpMyFAQ\Category;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;

/**
 * Class ImageTest
 */
class ImageTest extends TestCase
{
    /** @var Image */
    private Image $instance;

    protected function setUp(): void
    {
        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $pmfConfig = new Configuration($dbHandle);
        $pmfConfig->set('records.maxAttachmentSize', 1234567890);
        $this->instance = new Image($pmfConfig);
    }

    public function testNoUploadGetFileName(): void
    {
        $categoryId = 1;
        $categoryName = 'de';
        $uploadedFile = [
            'name' => '',
            'type' => '',
            'tmp_name' => '',
            'error' => 4,
            'size' => 0
        ];

        $this->instance->setUploadedFile($uploadedFile);

        $this->assertEquals('', $this->instance->getFileName($categoryId, $categoryName));
    }

    public function testUploadedGetFileName(): void
    {
        $categoryId = 1;
        $categoryName = 'de';
        $uploadedFile = [
            'name' => 'Foobar.png',
            'type' => 'image/png',
            'tmp_name' => '/private/var/tmp/phpSgODqb',
            'error' => 0,
            'size' => 1336915
        ];

        $this->instance->setUploadedFile($uploadedFile);

        $this->assertEquals('category-1-de.png', $this->instance->getFileName($categoryId, $categoryName));
    }
}
