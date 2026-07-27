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
}
