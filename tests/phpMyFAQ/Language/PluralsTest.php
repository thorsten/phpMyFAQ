<?php

namespace phpMyFAQ\Language;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class PluralsTest extends TestCase
{
    private Plurals $plurals;
    private ReflectionMethod $pluralMethod;

    protected function setUp(): void
    {
        // Mock Translation class behavior for testing
        if (!class_exists('\phpMyFAQ\Translation')) {
            $this->markTestSkipped('Translation class not available');
        }

        $this->plurals = new Plurals();

        // Use reflection to access a private plural method for testing
        $reflection = new ReflectionClass(Plurals::class);
        $this->pluralMethod = $reflection->getMethod('plural');
    }

    /**
     * @throws \ReflectionException
     */
    public function testPluralFormsEnglish(): void
    {
        // English: n != 1
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'en', 0));
        $this->assertEquals(0, $this->pluralMethod->invoke($this->plurals, 'en', 1));
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'en', 2));
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'en', 5));
    }

    /**
     * @throws \ReflectionException
     */
    public function testPluralFormsGerman(): void
    {
        // German: n != 1
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'de', 0));
        $this->assertEquals(0, $this->pluralMethod->invoke($this->plurals, 'de', 1));
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'de', 2));
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'de', 10));
    }

    /**
     * @throws \ReflectionException
     */
    public function testPluralFormsFrench(): void
    {
        // French: n > 1
        $this->assertEquals(0, $this->pluralMethod->invoke($this->plurals, 'fr', 0));
        $this->assertEquals(0, $this->pluralMethod->invoke($this->plurals, 'fr', 1));
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'fr', 2));
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'fr', 5));
    }

    /**
     * @throws \ReflectionException
     */
    public function testPluralFormsPolish(): void
    {
        // Polish: complex plural rules
        $this->assertEquals(0, $this->pluralMethod->invoke($this->plurals, 'pl', 1));
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'pl', 2));
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'pl', 3));
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'pl', 4));
        $this->assertEquals(2, $this->pluralMethod->invoke($this->plurals, 'pl', 5));
        $this->assertEquals(2, $this->pluralMethod->invoke($this->plurals, 'pl', 0));
        $this->assertEquals(2, $this->pluralMethod->invoke($this->plurals, 'pl', 10));
        $this->assertEquals(2, $this->pluralMethod->invoke($this->plurals, 'pl', 11));
    }

    /**
     * @throws \ReflectionException
     */
    public function testPluralFormsRussian(): void
    {
        // Russian: complex plural rules
        $this->assertEquals(0, $this->pluralMethod->invoke($this->plurals, 'ru', 1));
        $this->assertEquals(0, $this->pluralMethod->invoke($this->plurals, 'ru', 21));
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'ru', 2));
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'ru', 3));
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'ru', 4));
        $this->assertEquals(2, $this->pluralMethod->invoke($this->plurals, 'ru', 5));
        $this->assertEquals(2, $this->pluralMethod->invoke($this->plurals, 'ru', 0));
        $this->assertEquals(2, $this->pluralMethod->invoke($this->plurals, 'ru', 11));
    }

    /**
     * @throws \ReflectionException
     */
    public function testPluralFormsArabic(): void
    {
        // Arabic: most complex plural rules (6 forms)
        $this->assertEquals(0, $this->pluralMethod->invoke($this->plurals, 'ar', 0));
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'ar', 1));
        $this->assertEquals(2, $this->pluralMethod->invoke($this->plurals, 'ar', 2));
        $this->assertEquals(3, $this->pluralMethod->invoke($this->plurals, 'ar', 3));
        $this->assertEquals(3, $this->pluralMethod->invoke($this->plurals, 'ar', 10));
        $this->assertEquals(4, $this->pluralMethod->invoke($this->plurals, 'ar', 11));
        $this->assertEquals(4, $this->pluralMethod->invoke($this->plurals, 'ar', 99));
        $this->assertEquals(5, $this->pluralMethod->invoke($this->plurals, 'ar', 100));
    }

    /**
     * @throws \ReflectionException
     */
    public function testPluralFormsCzech(): void
    {
        // Czech: n == 1 ? 0 : (n >= 2 && n <= 4) ? 1 : 2
        $this->assertEquals(0, $this->pluralMethod->invoke($this->plurals, 'cs', 1));
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'cs', 2));
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'cs', 3));
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'cs', 4));
        $this->assertEquals(2, $this->pluralMethod->invoke($this->plurals, 'cs', 0));
        $this->assertEquals(2, $this->pluralMethod->invoke($this->plurals, 'cs', 5));
    }

    /**
     * @throws \ReflectionException
     */
    public function testPluralFormsWelsh(): void
    {
        // Welsh: complex rules with special cases for 8 and 11
        $this->assertEquals(0, $this->pluralMethod->invoke($this->plurals, 'cy', 1));
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'cy', 2));
        $this->assertEquals(2, $this->pluralMethod->invoke($this->plurals, 'cy', 3));
        $this->assertEquals(3, $this->pluralMethod->invoke($this->plurals, 'cy', 8));
        $this->assertEquals(3, $this->pluralMethod->invoke($this->plurals, 'cy', 11));
    }

    /**
     * @throws \ReflectionException
     */
    public function testPluralFormsLithuanian(): void
    {
        // Lithuanian: complex rules based on last digit and last two digits
        $this->assertEquals(0, $this->pluralMethod->invoke($this->plurals, 'lt', 1));
        $this->assertEquals(0, $this->pluralMethod->invoke($this->plurals, 'lt', 21));
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'lt', 2));
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'lt', 22));
        $this->assertEquals(2, $this->pluralMethod->invoke($this->plurals, 'lt', 11));
        $this->assertEquals(2, $this->pluralMethod->invoke($this->plurals, 'lt', 0));
    }

    /**
     * @throws \ReflectionException
     */
    public function testPluralFormsLatvian(): void
    {
        // Latvian: special rules
        $this->assertEquals(0, $this->pluralMethod->invoke($this->plurals, 'lv', 1));
        $this->assertEquals(0, $this->pluralMethod->invoke($this->plurals, 'lv', 21));
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'lv', 2));
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'lv', 5));
        $this->assertEquals(2, $this->pluralMethod->invoke($this->plurals, 'lv', 0));
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'lv', 11));
    }

    /**
     * @throws \ReflectionException
     */
    public function testPluralFormsSlovenian(): void
    {
        // Slovenian: 4 forms based on modulo 100
        $this->assertEquals(0, $this->pluralMethod->invoke($this->plurals, 'sl', 1));
        $this->assertEquals(0, $this->pluralMethod->invoke($this->plurals, 'sl', 101));
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'sl', 2));
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'sl', 102));
        $this->assertEquals(2, $this->pluralMethod->invoke($this->plurals, 'sl', 3));
        $this->assertEquals(2, $this->pluralMethod->invoke($this->plurals, 'sl', 4));
        $this->assertEquals(3, $this->pluralMethod->invoke($this->plurals, 'sl', 5));
    }

    /**
     * @throws \ReflectionException
     */
    public function testPluralFormsRomanian(): void
    {
        // Romanian: special rules for 0 and numbers ending in 01-19
        $this->assertEquals(0, $this->pluralMethod->invoke($this->plurals, 'ro', 1));
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'ro', 0));
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'ro', 2));
        $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, 'ro', 19));
        $this->assertEquals(2, $this->pluralMethod->invoke($this->plurals, 'ro', 20));
        $this->assertEquals(2, $this->pluralMethod->invoke($this->plurals, 'ro', 100));
    }

    /**
     * @throws \ReflectionException
     */
    public function testPluralFormsNoPlural(): void
    {
        // Languages with no plural forms (always return 0)
        $noPluralLanguages = ['bn', 'he', 'hi', 'id', 'ja', 'ko', 'th', 'tr', 'tw', 'vi', 'zh'];

        foreach ($noPluralLanguages as $lang) {
            $this->assertEquals(0, $this->pluralMethod->invoke($this->plurals, $lang, 0));
            $this->assertEquals(0, $this->pluralMethod->invoke($this->plurals, $lang, 1));
            $this->assertEquals(0, $this->pluralMethod->invoke($this->plurals, $lang, 2));
            $this->assertEquals(0, $this->pluralMethod->invoke($this->plurals, $lang, 100));
        }
    }

    /**
     * @throws \ReflectionException
     */
    public function testPluralFormsUnsupportedLanguage(): void
    {
        // Unsupported language should return -1
        $this->assertEquals(-1, $this->pluralMethod->invoke($this->plurals, 'xx', 1));
        $this->assertEquals(-1, $this->pluralMethod->invoke($this->plurals, 'invalid', 5));
        $this->assertEquals(-1, $this->pluralMethod->invoke($this->plurals, '', 10));
    }

    public function testGetMsgMethodExists(): void
    {
        $this->assertTrue(method_exists($this->plurals, 'getMsg'));
    }

    public function testConstructorSetsProperties(): void
    {
        $reflection = new ReflectionClass($this->plurals);

        // Test that properties are set during construction
        $nPluralsProperty = $reflection->getProperty('nPlurals');
        $nPlurals = $nPluralsProperty->getValue($this->plurals);
        $this->assertIsInt($nPlurals);

        $langProperty = $reflection->getProperty('lang');
        $lang = $langProperty->getValue($this->plurals);
        $this->assertNotNull($lang);

        $useDefaultProperty = $reflection->getProperty('useDefaultPluralForm');
        $useDefault = $useDefaultProperty->getValue($this->plurals);
        $this->assertIsBool($useDefault);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSerbianAndUkrainianSameAsRussian(): void
    {
        // Serbian and Ukrainian should follow the same rules as Russian
        $testNumbers = [0, 1, 2, 3, 4, 5, 11, 21, 22, 25];

        foreach ($testNumbers as $n) {
            $russianResult = $this->pluralMethod->invoke($this->plurals, 'ru', $n);
            $serbianResult = $this->pluralMethod->invoke($this->plurals, 'sr', $n);
            $ukrainianResult = $this->pluralMethod->invoke($this->plurals, 'uk', $n);

            $this->assertEquals($russianResult, $serbianResult, "Serbian differs from Russian for number $n");
            $this->assertEquals($russianResult, $ukrainianResult, "Ukrainian differs from Russian for number $n");
        }
    }

    /**
     * @throws \ReflectionException
     */
    public function testPortugueseBrazilSameAsFrench(): void
    {
        // Portuguese (Brazil) should follow the same rules as French
        $testNumbers = [0, 1, 2, 5, 10];

        foreach ($testNumbers as $n) {
            $frenchResult = $this->pluralMethod->invoke($this->plurals, 'fr', $n);
            $ptBrResult = $this->pluralMethod->invoke($this->plurals, 'pt_br', $n);

            $this->assertEquals($frenchResult, $ptBrResult, "Portuguese (Brazil) differs from French for number $n");
        }
    }

    /**
     * @throws \ReflectionException
     */
    public function testMultipleGermanicLanguages(): void
    {
        // Multiple Germanic languages should follow the same pattern (n != 1)
        $germanicLanguages = ['da', 'de', 'el', 'en', 'es', 'eu', 'fa', 'fi', 'it', 'nb', 'nl', 'hu', 'pt', 'sv'];

        foreach ($germanicLanguages as $lang) {
            $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, $lang, 0), "Language $lang failed for 0");
            $this->assertEquals(0, $this->pluralMethod->invoke($this->plurals, $lang, 1), "Language $lang failed for 1");
            $this->assertEquals(1, $this->pluralMethod->invoke($this->plurals, $lang, 2), "Language $lang failed for 2");
        }
    }

    /**
     * @throws \ReflectionException
     */
    public function testPluralReturnTypeIsIntForRepresentativeLanguages(): void
    {
        $languages = ['en', 'de', 'fr', 'pt_br', 'es', 'it', 'nb', 'nl', 'pt', 'sv'];
        $numbers = [0, 1, 2, 5, 10];

        foreach ($languages as $lang) {
            foreach ($numbers as $n) {
                $result = $this->pluralMethod->invoke($this->plurals, $lang, $n);
                $this->assertIsInt($result, "plural() must return int for {$lang} with n={$n}");
            }
        }
    }
}
