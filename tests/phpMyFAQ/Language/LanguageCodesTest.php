<?php

/**
 * Unit tests for LanguageCodes class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-01-01
 */

namespace phpMyFAQ\Language;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
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
        $result = LanguageCodes::getAll();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('en', $result);
        $this->assertArrayHasKey('de', $result);
        $this->assertArrayHasKey('fr', $result);
        $this->assertEquals('English', $result['en']);
        $this->assertEquals('German', $result['de']);
        $this->assertEquals('French', $result['fr']);
    }

    public function testGetAllSortedReturnsArraySortedByValue(): void
    {
        $result = LanguageCodes::getAllSorted();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        $values = array_values($result);
        $sortedValues = $values;
        sort($sortedValues);

        $this->assertEquals($sortedValues, $values);
    }

    public function testGetWithValidLanguageCode(): void
    {
        $this->assertEquals('English', LanguageCodes::get('en'));
        $this->assertEquals('German', LanguageCodes::get('de'));
        $this->assertEquals('French', LanguageCodes::get('fr'));
        $this->assertEquals('Spanish', LanguageCodes::get('es'));
        $this->assertEquals('Chinese', LanguageCodes::get('zh'));
    }

    public function testGetWithValidLanguageCodeCaseInsensitive(): void
    {
        $this->assertEquals('English', LanguageCodes::get('EN'));
        $this->assertEquals('German', LanguageCodes::get('DE'));
        $this->assertEquals('French', LanguageCodes::get('FR'));
    }

    public function testGetWithInvalidLanguageCode(): void
    {
        $this->assertNull(LanguageCodes::get('xyz'));
        $this->assertNull(LanguageCodes::get('invalid'));
        $this->assertNull(LanguageCodes::get(''));
    }

    public function testGetSupportedWithValidLanguageCode(): void
    {
        $this->assertEquals('English', LanguageCodes::getSupported('en'));
        $this->assertEquals('German', LanguageCodes::getSupported('de'));
        $this->assertEquals('French', LanguageCodes::getSupported('fr'));
        $this->assertEquals('Portuguese (Brazil)', LanguageCodes::getSupported('pt_br'));
    }

    public function testGetSupportedWithValidLanguageCodeCaseInsensitive(): void
    {
        $this->assertEquals('English', LanguageCodes::getSupported('EN'));
        $this->assertEquals('German', LanguageCodes::getSupported('DE'));
        $this->assertEquals('French', LanguageCodes::getSupported('FR'));
    }

    public function testGetSupportedWithInvalidLanguageCode(): void
    {
        $this->assertNull(LanguageCodes::getSupported('xyz'));
        $this->assertNull(LanguageCodes::getSupported('invalid'));
        $this->assertNull(LanguageCodes::getSupported(''));
        $this->assertNull(LanguageCodes::getSupported('af'));
    }

    public function testGetKeyWithValidLanguageName(): void
    {
        $this->assertEquals('en', LanguageCodes::getKey('English'));
        $this->assertEquals('de', LanguageCodes::getKey('German'));
        $this->assertEquals('fr', LanguageCodes::getKey('French'));
        $this->assertEquals('es', LanguageCodes::getKey('Spanish'));
    }

    public function testGetKeyWithInvalidLanguageName(): void
    {
        $this->assertEmpty(LanguageCodes::getKey('Invalid Language'));
        $this->assertEmpty(LanguageCodes::getKey('NonExistent'));

        $result = LanguageCodes::getKey('');
        $this->assertTrue($result === false || is_string($result));
    }

    public function testGetAllSupportedReturnsArray(): void
    {
        $result = LanguageCodes::getAllSupported();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        $this->assertArrayHasKey('en', $result);
        $this->assertArrayHasKey('de', $result);
        $this->assertArrayHasKey('fr', $result);
        $this->assertArrayHasKey('pt_br', $result);

        $this->assertEquals('English', $result['en']);
        $this->assertEquals('German', $result['de']);
        $this->assertEquals('Portuguese (Brazil)', $result['pt_br']);
    }

    public function testGetAllSupportedContainsSubsetOfGetAll(): void
    {
        $allCodes = LanguageCodes::getAll();
        $supportedCodes = LanguageCodes::getAllSupported();

        foreach ($supportedCodes as $code => $name) {
            $this->assertArrayHasKey($code, $allCodes);
            $this->assertNotEmpty($allCodes[$code]);
        }
    }

    public function testLanguageCodeConsistency(): void
    {
        $allCodes = LanguageCodes::getAll();

        $expectedCodes = [
            'aa' => 'Afar',
            'ab' => 'Abkhazian',
            'af' => 'Afrikaans',
            'ar' => 'Arabic',
            'zh_cn' => 'Chinese (China)',
            'zh_tw' => 'Chinese (Taiwan)',
            'en_us' => 'English (United States)',
            'en_gb' => 'English (United Kingdom)',
            'es_mx' => 'Spanish (Mexico)',
            'fr_ca' => 'French (Canada)',
            'pt_br' => 'Portuguese (Brazil)',
        ];

        foreach ($expectedCodes as $code => $expectedName) {
            $this->assertArrayHasKey($code, $allCodes);
            $this->assertEquals($expectedName, $allCodes[$code]);
        }
    }

    public function testGetWithRegionalVariants(): void
    {
        $this->assertEquals('Arabic (Egypt)', LanguageCodes::get('ar_eg'));
        $this->assertEquals('English (United States)', LanguageCodes::get('en_us'));
        $this->assertEquals('Spanish (Mexico)', LanguageCodes::get('es_mx'));
        $this->assertEquals('French (Canada)', LanguageCodes::get('fr_ca'));
        $this->assertEquals('Chinese (China)', LanguageCodes::get('zh_cn'));
    }

    public function testArrayStructureIsValid(): void
    {
        $allCodes = LanguageCodes::getAll();

        foreach ($allCodes as $code => $name) {
            $this->assertIsString($code);
            $this->assertIsString($name);
            $this->assertNotEmpty($code);
            $this->assertNotEmpty($name);
        }

        $supportedCodes = LanguageCodes::getAllSupported();

        foreach ($supportedCodes as $code => $name) {
            $this->assertIsString($code);
            $this->assertIsString($name);
            $this->assertNotEmpty($code);
            $this->assertNotEmpty($name);
        }
    }
}
