<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Plugin\PluginException;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

class LanguageTest extends TestCase
{
    private Language $language;

    private Sqlite3 $dbHandle;

    private Session $session;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        $this->session = $this->createMock(Session::class);

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($this->dbHandle);
        $this->language = new Language($configuration, $this->session);
    }

    public function testIsLanguageAvailableWithId(): void
    {
        $this->dbHandle->query(
            'INSERT INTO faqdata (id, lang, solution_id, active, sticky, thema, author, email, updated) VALUES' .
            '(1, "en", 1001, 1, 1, "Test", "Author", "test@example.org", DATETIME("now", "localtime"))'
        );
        $result = $this->language->isLanguageAvailable(1);

        $this->assertEquals(['en'], $result);

        $this->dbHandle->query('DELETE FROM faqdata');
    }

    public function testIsLanguageAvailableNoResults(): void
    {
        $result = $this->language->isLanguageAvailable(1);
        $this->assertEquals([], $result);
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

    public function testSetLanguageReturnsCorrectLanguageForValidConfigLanguage(): void
    {
        $language = $this->language->setLanguage(true, 'language_en.php');
        $this->assertEquals('en', $language);
    }

    public function testSetLanguageReturnsCorrectLanguageForValidSessionLanguage(): void
    {
        $this->session->method('get')->willReturn('en');
        $language = $this->language->setLanguage(true, 'language_en.php');
        $this->assertEquals('en', $language);
    }

    public function testSetLanguageReturnsFallbackLanguageForInvalidConfigLanguageAndNoOtherDetectedLanguages(): void
    {
        $language = $this->language->setLanguage(true, 'invalid_language.php');
        $this->assertEquals('en', $language);
    }
}
