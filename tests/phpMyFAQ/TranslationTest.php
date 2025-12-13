<?php

declare(strict_types=1);

namespace phpMyFAQ;

use FilesystemIterator;
use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class TranslationTest extends TestCase
{
    /**
     * @throws Exception
     */ protected function setUp(): void
    {
        parent::setUp();

        // Prepare a custom translations directory before creating the instance
        $translationsDir = __DIR__ . '/_translations';
        if (!is_dir($translationsDir)) {
            mkdir($translationsDir, 0777, true);
        }

        file_put_contents(
            $translationsDir . '/language_en.php',
            "<?php\n\nreturn [\n" . "    'test.key' => 'Default Label',\n" . "];\n",
        );

        file_put_contents(
            $translationsDir . '/language_de.php',
            "<?php\n\nreturn [\n" . "    'test.key' => '',\n" . "    'test.zero' => '0',\n" . "];\n",
        );

        Translation::resetInstance();

        // Now create and configure the instance so that init() sees our test directory
        Translation::create()
            ->setTranslationsDir($translationsDir)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('de');
    }

    public static function tearDownAfterClass(): void
    {
        $translationsDir = __DIR__ . '/_translations';

        if (!is_dir($translationsDir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($translationsDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDir()) {
                rmdir($fileInfo->getPathname());
            } else {
                unlink($fileInfo->getPathname());
            }
        }

        rmdir($translationsDir);
    }

    public function testGetFallsBackToDefaultWhenCurrentIsEmptyString(): void
    {
        $value = Translation::get('test.key');

        $this->assertSame(
            'Default Label',
            $value,
            'Should fall back to default language when the current language value is an empty string.',
        );
    }

    public function testGetAcceptsZeroStringAndDoesNotFallback(): void
    {
        $value = Translation::get('test.zero');

        $this->assertSame('0', $value, 'String "0" must not be treated as empty and must not trigger a fallback.');
    }

    public function testGetReturnsNullForUnknownKey(): void
    {
        $value = Translation::get('unknown.key');

        $this->assertNull($value, 'Unknown translation keys should return null.');
    }

    public function testHasReturnsTrueForExistingKey(): void
    {
        $this->assertTrue(
            Translation::has('test.key'),
            'has() should return true for keys defined in the current or default language.',
        );
    }

    public function testHasReturnsFalseForUnknownKey(): void
    {
        $this->assertFalse(
            Translation::has('unknown.key'),
            'has() should return false for keys that are not defined in any language.',
        );
    }
}
