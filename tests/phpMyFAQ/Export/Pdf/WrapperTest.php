<?php

namespace phpMyFAQ\Export\Pdf;
use phpMyFAQ\Translation;
use PHPUnit\Framework\TestCase;

class WrapperTest extends TestCase
{
    private Wrapper $wrapper;

    protected function setUp(): void
    {
        parent::setUp();

        Translation::create()
            ->setLanguagesDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->wrapper = new Wrapper();
    }

    public function testConcatenatePathsWithUnixPaths(): void
    {
        $path = '/var/www/phpmyfaq';

        $file = '/content/user/images/test.jpg';
        $expected = '/var/www/phpmyfaq/content/user/images/test.jpg';
        $this->assertEquals($expected, $this->wrapper->concatenatePaths($path, $file));
    }

    public function testConcatenatePathsWithWindowsPaths(): void
    {
        $path = 'C:\\xampp\\htdocs\\phpmyfaq';

        $file = '/content/user/images/test.jpg';
        $expected = 'C:/xampp/htdocs/phpmyfaq/content/user/images/test.jpg';
        $this->assertEquals($expected, $this->wrapper->concatenatePaths($path, $file));
    }

    public function testConcatenatePathsWithMixedPaths(): void
    {
        $path = 'C:\\xampp\\htdocs\\phpmyfaq';

        $file = '/content/user/images/test.jpg';
        $expected = 'C:/xampp/htdocs/phpmyfaq/content/user/images/test.jpg';
        $this->assertEquals($expected, $this->wrapper->concatenatePaths($path, $file));
    }

    public function testConcatenatePathsWithDuplicateRoot(): void
    {
        $path = 'C:\\xampp\\htdocs\\phpmyfaq';

        $file = '/phpmyfaq/content/user/images/test.jpg';
        $expected = 'C:/xampp/htdocs/phpmyfaq/content/user/images/test.jpg';
        $this->assertEquals($expected, $this->wrapper->concatenatePaths($path, $file));
    }
}
