<?php

namespace phpMyFAQ\Language;

use PHPUnit\Framework\TestCase;

class LanguageCodesTest extends TestCase
{
    public function testGetSupportedReturnsNullForUnknownKey(): void
    {
        $language = LanguageCodes::getSupported('en-us');
        $this->assertNull($language);
    }

    public function testGetSupportedReturnsExpectedValue(): void
    {
        $language = LanguageCodes::getSupported('en');
        $this->assertEquals('English', $language);
    }

    public function testGetSupportedIsCaseInsensitive(): void
    {
        $language = LanguageCodes::getSupported('FR');
        $this->assertEquals('French', $language);
    }


    public function testGetAllReturnsArray(): void
    {
        $languages = LanguageCodes::getAll();
        $this->assertIsArray($languages);
    }

    public function testGetReturnsNullForUnknownKey(): void
    {
        $language = LanguageCodes::get('xxx');
        $this->assertNull($language);
    }

    public function testGetReturnsExpectedValue(): void
    {
        $language = LanguageCodes::get('en');
        $this->assertEquals('English', $language);
    }

    public function testGetIsCaseInsensitive(): void
    {
        $language = LanguageCodes::get('FR');
        $this->assertEquals('French', $language);
    }
}
