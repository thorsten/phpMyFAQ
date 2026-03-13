<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language\LanguageDetector;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

#[AllowMockObjectsWithoutExpectations]
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

        $this->session = $this->createStub(Session::class);

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($this->dbHandle);
        $this->language = new Language($configuration, $this->session);

        // Clean up any leftover test data - both test IDs
        $this->dbHandle->query('DELETE FROM faqdata WHERE id IN (1, 999)');
    }

    protected function tearDown(): void
    {
        // Ensure cleanup after each test
        $this->dbHandle->query('DELETE FROM faqdata WHERE id IN (1, 999)');
        Language::$language = '';
        parent::tearDown();
    }

    public function testIsLanguageAvailableWithId(): void
    {
        $this->dbHandle->query(
            'INSERT INTO faqdata (id, lang, solution_id, active, sticky, thema, author, email, updated) VALUES'
            . '(999, "en", 1001, 1, 1, "Test", "Author", "test@example.org", DATETIME("now", "localtime"))',
        );
        $result = $this->language->isLanguageAvailable(999);

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
        $language = $this->language->setLanguageWithDetection('language_en.php');
        $this->assertEquals('en', $language);
    }

    public function testSetLanguageReturnsCorrectLanguageForValidSessionLanguage(): void
    {
        $this->session->method('get')->willReturn('en');
        $language = $this->language->setLanguageWithDetection('language_en.php');
        $this->assertEquals('en', $language);
    }

    public function testSetLanguageReturnsFallbackLanguageForInvalidConfigLanguageAndNoOtherDetectedLanguages(): void
    {
        $language = $this->language->setLanguageWithDetection('invalid_language.php');
        $this->assertEquals('en', $language);
    }

    public function testSetLanguageFromConfigurationReturnsDetectedConfigurationLanguage(): void
    {
        $language = $this->language->setLanguageFromConfiguration('language_en.php');

        $this->assertEquals('en', $language);
    }

    public function testSetLanguageByAcceptLanguageUsesDetectorValue(): void
    {
        $session = $this->createStub(SessionInterface::class);
        $configuration = $this->createMock(Configuration::class);
        $language = new Language($configuration, $session);

        $detector = $this->createMock(LanguageDetector::class);
        $detector->expects($this->once())->method('detectAllWithBrowser')->with('')->willReturn([]);
        $detector->expects($this->once())->method('getAcceptLanguage')->willReturn('de');

        $property = new \ReflectionProperty(Language::class, 'languageDetector');
        $property->setValue($language, $detector);

        Language::$language = '';

        $this->assertSame('de', $language->setLanguageByAcceptLanguage());
    }

    public function testGetLanguageReturnsSupportedSessionLanguageWhenUnset(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())->method('get')->with('lang')->willReturn('de');

        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->never())->method('getDefaultLanguage');

        $language = new Language($configuration, $session);
        Language::$language = '';

        $this->assertSame('de', $language->getLanguage());
    }

    public function testGetLanguageFallsBackToConfiguredDefaultWhenSessionLanguageIsInvalid(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())->method('get')->with('lang')->willReturn('invalid');

        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->once())->method('getDefaultLanguage')->willReturn('fr');

        $language = new Language($configuration, $session);
        Language::$language = '';

        $this->assertSame('fr', $language->getLanguage());
    }

    public function testGetLanguageFallsBackToEnglishWhenConfigurationThrows(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())->method('get')->with('lang')->willReturn(null);

        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->once())->method('getDefaultLanguage')->willThrowException(
            new \RuntimeException('configuration unavailable'),
        );

        $language = new Language($configuration, $session);
        Language::$language = '';

        $this->assertSame('en', $language->getLanguage());
    }
}
