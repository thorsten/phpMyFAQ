<?php

declare(strict_types=1);

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class TranslationStatisticsTest extends TestCase
{
    private TranslationStatistics $translation;

    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($dbHandle);

        $this->translation = new TranslationStatistics($configuration);
    }

    public function testGetStatistics(): void
    {
        $statistics = $this->translation->getStatistics();

        $this->assertIsArray($statistics);
        $this->assertNotEmpty($statistics);

        // Verify that English exists and has 100% completion
        $this->assertArrayHasKey('en', $statistics);
        $this->assertEquals(100.0, $statistics['en']['completion_percentage']);

        // Verify all language entries have required keys
        foreach ($statistics as $language => $stats) {
            $this->assertIsString($language);
            $this->assertArrayHasKey('language_code', $stats);
            $this->assertArrayHasKey('total_keys', $stats);
            $this->assertArrayHasKey('translated_keys', $stats);
            $this->assertArrayHasKey('missing_keys', $stats);
            $this->assertArrayHasKey('completion_percentage', $stats);

            $this->assertIsInt($stats['total_keys']);
            $this->assertIsInt($stats['translated_keys']);
            $this->assertIsInt($stats['missing_keys']);
            $this->assertIsFloat($stats['completion_percentage']);

            // Verify percentage is between 0 and 100
            $this->assertGreaterThanOrEqual(0.0, $stats['completion_percentage']);
            $this->assertLessThanOrEqual(100.0, $stats['completion_percentage']);
        }
    }

    public function testGetLanguageStatistics(): void
    {
        $enStats = $this->translation->getLanguageStatistics('en');

        $this->assertIsArray($enStats);
        $this->assertEquals('en', $enStats['language_code']);
        $this->assertEquals(100.0, $enStats['completion_percentage']);
        $this->assertEquals(0, $enStats['missing_keys']);
    }

    public function testGetLanguageStatisticsForNonExistentLanguage(): void
    {
        $stats = $this->translation->getLanguageStatistics('nonexistent');

        $this->assertNull($stats);
    }

    public function testGetMissingKeys(): void
    {
        $missingKeys = $this->translation->getMissingKeys('en');

        // English is the reference, so it should have no missing keys
        $this->assertIsArray($missingKeys);
        $this->assertEmpty($missingKeys);
    }

    public function testGetMissingKeysForPartialTranslation(): void
    {
        // Test with all languages to ensure getMissingKeys() works correctly
        $statistics = $this->translation->getStatistics();

        // Test at least one non-English language
        $testedLanguages = 0;
        foreach ($statistics as $lang => $stats) {
            if ($lang === 'en') {
                continue; // Skip English as it's already tested in testGetMissingKeys
            }

            $missingKeys = $this->translation->getMissingKeys($lang);

            $this->assertIsArray($missingKeys);

            // The count of missing keys should match what statistics reports
            $this->assertCount($stats['missing_keys'], $missingKeys);

            // If there are missing keys, verify they are all strings
            if ($stats['missing_keys'] > 0) {
                $this->assertNotEmpty($missingKeys);
                foreach ($missingKeys as $key) {
                    $this->assertIsString($key);
                }
            } else {
                // If no missing keys, the array should be empty
                $this->assertEmpty($missingKeys);
            }

            $testedLanguages++;
            // Test first 5 languages to keep test fast
            if ($testedLanguages >= 5) {
                break;
            }
        }

        // Ensure we tested at least one non-English language
        $this->assertGreaterThan(0, $testedLanguages, 'No non-English languages available to test');
    }

    public function testStatisticsAreSortedByCompletionPercentage(): void
    {
        $statistics = $this->translation->getStatistics();
        $percentages = array_column($statistics, 'completion_percentage');

        // Verify that the array is sorted in descending order
        $sortedPercentages = $percentages;
        rsort($sortedPercentages);

        $this->assertEquals($sortedPercentages, $percentages);
    }

    public function testAllLanguagesHaveValidData(): void
    {
        $statistics = $this->translation->getStatistics();

        foreach ($statistics as $language => $stats) {
            // Total keys should be positive
            $this->assertGreaterThan(0, $stats['total_keys'], "Language $language has no translation keys");

            // Translated keys should not exceed total keys
            $this->assertLessThanOrEqual(
                $stats['total_keys'],
                $stats['translated_keys'],
                "Language $language has more translated keys than total keys",
            );

            // Missing keys + translated keys should equal total reference keys for non-English
            if ($language !== 'en') {
                $enStats = $this->translation->getLanguageStatistics('en');
                $this->assertEquals(
                    $enStats['total_keys'],
                    $stats['translated_keys'] + $stats['missing_keys'],
                    "Language $language has inconsistent key counts",
                );
            }
        }
    }
}
