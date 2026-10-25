<?php

namespace phpMyFAQ\Language;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class LanguageDetectorTest extends TestCase
{
    private LanguageDetector $languageDetector;

    protected function setUp(): void
    {
        parent::setUp();

        $session = $this->createStub(Session::class);
        $this->languageDetector = new LanguageDetector($session);
    }

    public function testSelectLanguagePrefersBrowserDetectionOverConfig(): void
    {
        $selected = $this->languageDetector->selectLanguage(['config' => 'en', 'detection' => 'de']);

        $this->assertEquals('de', $selected);
    }

    public function testSelectLanguagePrefersExplicitRequestParameterOverDetection(): void
    {
        $selected = $this->languageDetector->selectLanguage([
            'post' => 'fr',
            'detection' => 'de',
            'config' => 'en',
        ]);

        $this->assertEquals('fr', $selected);
    }

    public function testSelectLanguagePrefersSessionOverDetection(): void
    {
        $selected = $this->languageDetector->selectLanguage([
            'session' => 'it',
            'detection' => 'de',
            'config' => 'en',
        ]);

        $this->assertEquals('it', $selected);
    }

    public function testSelectLanguageFallsBackToConfigWithoutDetection(): void
    {
        $selected = $this->languageDetector->selectLanguage(['detection' => null, 'config' => 'de']);

        $this->assertEquals('de', $selected);
    }

    public function testSelectLanguageSkipsUnsupportedLanguages(): void
    {
        $selected = $this->languageDetector->selectLanguage(['post' => 'xx', 'config' => 'de']);

        $this->assertEquals('de', $selected);
    }

    public function testSelectLanguageFallsBackToEnglish(): void
    {
        $this->assertEquals('en', $this->languageDetector->selectLanguage([]));
    }

    public function testDetectAllWithBrowserFallsBackToThePrimarySubtagOfARegionalLanguage(): void
    {
        $previous = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;
        // Symfony reports "de-DE" as "de_DE", which is not a supported phpMyFAQ language code,
        // so the detector has to fall back to the first two letters.
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-DE';

        try {
            $detected = $this->languageDetector->detectAllWithBrowser('language_en.php');
        } finally {
            if ($previous === null) {
                unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            } else {
                $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $previous;
            }
        }

        $this->assertSame('de', $this->languageDetector->getAcceptLanguage());
        $this->assertSame('de', $detected['detection']);
        $this->assertSame('en', $detected['config']);
    }
}
