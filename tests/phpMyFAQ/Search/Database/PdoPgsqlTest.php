<?php

namespace phpMyFAQ\Search\Database;

use phpMyFAQ\Configuration;
use phpMyFAQ\Strings;
use PHPUnit\Framework\TestCase;

/**
 * Class PdoPgsqlTest
 *
 * Tests for PDO PostgreSQL search database class
 */
class PdoPgsqlTest extends TestCase
{
    private PdoPgsql $pdoPgsqlSearch;

    protected function setUp(): void
    {
        parent::setUp();

        Strings::init('en');

        // Create a mock configuration
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('get')
            ->willReturnCallback(function ($key) {
                return match ($key) {
                    'search.relevance' => 'thema,content,keywords',
                    'search.enableRelevance' => true,
                    default => null,
                };
            });
        
        $this->pdoPgsqlSearch = new PdoPgsql($configuration);
    }

    protected function tearDown(): void
    {
        $this->pdoPgsqlSearch = null;
        parent::tearDown();
    }

    /**
     * Test that getMatchingOrder only includes columns that were added to SELECT
     */
    public function testGetMatchingOrderOnlyIncludesAddedColumns(): void
    {
        // Set matching columns to only include 'keywords' - missing 'thema' and 'content'
        $this->pdoPgsqlSearch->setMatchingColumns(['fd.keywords']);
        
        // Generate the SELECT columns
        $resultColumns = $this->pdoPgsqlSearch->getMatchingColumnsAsResult();
        
        // Verify that only relevance_keywords is in the result
        $this->assertStringContainsString('relevance_keywords', $resultColumns);
        $this->assertStringNotContainsString('relevance_thema', $resultColumns);
        $this->assertStringNotContainsString('relevance_content', $resultColumns);
        
        // Generate the ORDER BY clause
        $orderBy = $this->pdoPgsqlSearch->getMatchingOrder();
        
        // Verify that ORDER BY only includes relevance_keywords, not the missing columns
        $this->assertStringContainsString('relevance_keywords', $orderBy);
        $this->assertStringNotContainsString('relevance_thema', $orderBy);
        $this->assertStringNotContainsString('relevance_content', $orderBy);
    }

    /**
     * Test that all columns are included when all matching columns are present
     */
    public function testGetMatchingOrderIncludesAllColumnsWhenPresent(): void
    {
        // Set matching columns to include all three
        $this->pdoPgsqlSearch->setMatchingColumns(['fd.thema', 'fd.content', 'fd.keywords']);
        
        // Generate the SELECT columns
        $resultColumns = $this->pdoPgsqlSearch->getMatchingColumnsAsResult();
        
        // Verify that all relevance columns are in the result
        $this->assertStringContainsString('relevance_thema', $resultColumns);
        $this->assertStringContainsString('relevance_content', $resultColumns);
        $this->assertStringContainsString('relevance_keywords', $resultColumns);
        
        // Generate the ORDER BY clause
        $orderBy = $this->pdoPgsqlSearch->getMatchingOrder();
        
        // Verify that ORDER BY includes all relevance columns
        $this->assertStringContainsString('relevance_thema', $orderBy);
        $this->assertStringContainsString('relevance_content', $orderBy);
        $this->assertStringContainsString('relevance_keywords', $orderBy);
    }

    /**
     * Test that ORDER BY respects the order from configuration
     */
    public function testGetMatchingOrderRespectsConfigOrder(): void
    {
        // Create a new instance with different config order
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('get')
            ->willReturnCallback(function ($key) {
                return match ($key) {
                    'search.relevance' => 'keywords,content,thema',
                    'search.enableRelevance' => true,
                    default => null,
                };
            });
        
        $pdoPgsqlSearch = new PdoPgsql($configuration);
        
        // Set matching columns
        $pdoPgsqlSearch->setMatchingColumns(['fd.thema', 'fd.content', 'fd.keywords']);
        
        // Generate the SELECT and ORDER BY
        $pdoPgsqlSearch->getMatchingColumnsAsResult();
        $orderBy = $pdoPgsqlSearch->getMatchingOrder();
        
        // Check that keywords comes before content, and content before thema
        $keywordsPos = strpos($orderBy, 'relevance_keywords');
        $contentPos = strpos($orderBy, 'relevance_content');
        $themaPos = strpos($orderBy, 'relevance_thema');
        
        $this->assertNotFalse($keywordsPos);
        $this->assertNotFalse($contentPos);
        $this->assertNotFalse($themaPos);
        $this->assertLessThan($contentPos, $keywordsPos);
        $this->assertLessThan($themaPos, $contentPos);
    }

    /**
     * Test with partial matching columns
     */
    public function testGetMatchingOrderWithPartialMatchingColumns(): void
    {
        // Only include thema and keywords, skip content
        $this->pdoPgsqlSearch->setMatchingColumns(['fd.thema', 'fd.keywords']);
        
        // Generate the SELECT columns
        $resultColumns = $this->pdoPgsqlSearch->getMatchingColumnsAsResult();
        
        // Verify content is not in the result
        $this->assertStringNotContainsString('relevance_content', $resultColumns);
        
        // Generate the ORDER BY clause
        $orderBy = $this->pdoPgsqlSearch->getMatchingOrder();
        
        // Verify ORDER BY doesn't include content but includes others
        $this->assertStringContainsString('relevance_thema', $orderBy);
        $this->assertStringNotContainsString('relevance_content', $orderBy);
        $this->assertStringContainsString('relevance_keywords', $orderBy);
    }
}
