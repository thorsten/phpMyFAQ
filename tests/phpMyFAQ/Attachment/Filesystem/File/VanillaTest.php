<?php

namespace phpMyFAQ\Attachment\Filesystem\File;

use PHPUnit\Framework\TestCase;

/**
 * Class VanillaTest
 */
class VanillaTest extends TestCase
{
    /** @var VanillaFile*/
    private VanillaFile $instance;

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

    public function testDelete(): void
    {
        copy(PMF_TEST_DIR . '/fixtures/path/foo.bar', PMF_TEST_DIR . '/fixtures/path-to-delete/foo.bar.baz');

        $this->assertTrue($this->instance->delete());
    }

    public function testDeleteDir(): void
    {
        copy(PMF_TEST_DIR . '/fixtures/path/foo.bar', PMF_TEST_DIR . '/fixtures/path-to-delete/foo.bar');

        $this->assertTrue(
            $this->instance->deleteDir(
                PMF_TEST_DIR . '/fixtures/path-to-delete/'
            )
        );
    }
}
