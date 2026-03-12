<?php

declare(strict_types=1);

namespace phpMyFAQ\Helper;

use phpMyFAQ\Translation;
use PHPUnit\Framework\TestCase;

class LanguageHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();
    }

    public function testRenderLanguageOptionsSupportsSelectedLanguageAndFileValues(): void
    {
        $result = LanguageHelper::renderLanguageOptions('en', false, true);

        $this->assertStringContainsString('value="language_en.php"', $result);
        $this->assertStringContainsString('selected="selected"', $result);
        $this->assertStringContainsString('English', $result);
    }

    public function testRenderLanguageOptionsCanRestrictToSingleLanguage(): void
    {
        $result = LanguageHelper::renderLanguageOptions('de', true, false);

        $this->assertStringContainsString('value="de"', $result);
        $this->assertStringContainsString('Deutsch', $result);
        $this->assertStringNotContainsString('value="en"', $result);
    }
}
