<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;

class LanguageTest extends TestCase
{
    private Language $language;
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        $dbHandle = new Sqlite3();
        $configuration = new Configuration($dbHandle);
        $this->language = new Language($configuration);
    }

    public function testGetLanguageReturnsString(): void
    {
        $language = $this->language->getLanguage();
        $this->assertIsString($language);
    }

    public function testGetLanguageReturnsExpectedValue(): void
    {
        Language::$language = 'en';
        $language = $this->language->getLanguage();
        $this->assertEquals('en', $language);
    }

    public function testIsASupportedLanguageReturnsTrueForSupportedLanguage(): void
    {
        $isSupported = Language::isASupportedLanguage('en');
        $this->assertTrue($isSupported);
    }

    public function testIsASupportedLanguageReturnsFalseForUnsupportedLanguage(): void
    {
        $isSupported = Language::isASupportedLanguage('en-us');
        $this->assertFalse($isSupported);
    }

    public function testIsASupportedLanguageReturnsFalseForNull(): void
    {
        $isSupported = Language::isASupportedLanguage(null);
        $this->assertFalse($isSupported);
    }

    public function testSetLanguageReturnsCorrectLanguageForValidConfigLanguage()
    {
        $language = $this->language->setLanguage(true, 'language_en.php');
        $this->assertEquals('en', $language);
    }

    public function testSetLanguageReturnsCorrectLanguageForValidSessionLanguage()
    {
        $_SESSION['lang'] = 'fr';
        $language = $this->language->setLanguage(true, 'language_en.php');
        $this->assertEquals('fr', $language);
        unset($_SESSION['lang']);
    }

    public function testSetLanguageReturnsFallbackLanguageForInvalidConfigLanguageAndNoOtherDetectedLanguages()
    {
        $language = $this->language->setLanguage(true, 'invalid_language.php');
        $this->assertEquals('en', $language);
    }
}
