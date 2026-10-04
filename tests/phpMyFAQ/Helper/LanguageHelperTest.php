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

    public function testRenderSelectLanguageMarksTheDefaultAndCanSubmitOnChange(): void
    {
        $plain = LanguageHelper::renderSelectLanguage('de');
        $this->assertStringStartsWith('<select class="form-select" name="language" aria-label="Language" id="language" >', $plain);
        $this->assertStringContainsString('<option value="de" selected>Deutsch</option>', $plain);
        $this->assertStringContainsString('<option value="en" >English</option>', $plain);
        $this->assertStringEndsWith('</select>', $plain);

        $submitting = LanguageHelper::renderSelectLanguage('en', true, [], 'content-language');
        $this->assertStringContainsString('name="content-language"', $submitting);
        $this->assertStringContainsString('aria-label="Content-language"', $submitting);
        $this->assertStringContainsString('onchange="this.form.submit();"', $submitting);
    }

    public function testRenderSelectLanguageLeavesOutExcludedLanguages(): void
    {
        $result = LanguageHelper::renderSelectLanguage('en', false, ['de', 'fr']);

        $this->assertStringContainsString('value="en"', $result);
        $this->assertStringNotContainsString('value="de"', $result);
        $this->assertStringNotContainsString('value="fr"', $result);
    }
}
