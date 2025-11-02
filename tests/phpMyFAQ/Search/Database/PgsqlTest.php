<?php

namespace phpMyFAQ\Search\Database;

use phpMyFAQ\Configuration;
use phpMyFAQ\Strings;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Class PgsqlTest
 *
 * Tests for PostgreSQL search database class
 */
class PgsqlTest extends TestCase
{
    private Pgsql $pgsqlSearch;
    private Configuration $configuration;
    private array $originalConfig = [];

    protected function setUp(): void
    {
        parent::setUp();

        Strings::init('en');

        $this->configuration = Configuration::getConfigurationInstance();
        
        // Save original config values and set up test configuration using reflection
        $reflection = new ReflectionClass($this->configuration);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($this->configuration);
        
        // Save original values
        $this->originalConfig['search.relevance'] = $config['search.relevance'] ?? null;
        $this->originalConfig['search.enableRelevance'] = $config['search.enableRelevance'] ?? null;
        
        // Set test values
        $config['search.relevance'] = 'thema,content,keywords';
        $config['search.enableRelevance'] = true;
        $configProperty->setValue($this->configuration, $config);
        
        $this->pgsqlSearch = new Pgsql($this->configuration);
    }

    protected function tearDown(): void
    {
        // Restore original config values
        $reflection = new ReflectionClass($this->configuration);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($this->configuration);
        
        foreach ($this->originalConfig as $key => $value) {
            if ($value === null) {
                unset($config[$key]);
            } else {
                $config[$key] = $value;
            }
        }
        $configProperty->setValue($this->configuration, $config);
        
        $this->pgsqlSearch = null;
        parent::tearDown();
    }

    /**
     * Test that getMatchingOrder only includes columns that were added to SELECT
     */
    public function testGetMatchingOrderOnlyIncludesAddedColumns(): void
    {
        // Set matching columns to only include 'keywords' - missing 'thema' and 'content'
        $this->pgsqlSearch->setMatchingColumns(['fd.keywords']);
        
        // Generate the SELECT columns
        $resultColumns = $this->pgsqlSearch->getMatchingColumnsAsResult();
        
        // Verify that only relevance_keywords is in the result
        $this->assertStringContainsString('relevance_keywords', $resultColumns);
        $this->assertStringNotContainsString('relevance_thema', $resultColumns);
        $this->assertStringNotContainsString('relevance_content', $resultColumns);
        
        // Generate the ORDER BY clause
        $orderBy = $this->pgsqlSearch->getMatchingOrder();
        
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
        $this->pgsqlSearch->setMatchingColumns(['fd.thema', 'fd.content', 'fd.keywords']);
        
        // Generate the SELECT columns
        $resultColumns = $this->pgsqlSearch->getMatchingColumnsAsResult();
        
        // Verify that all relevance columns are in the result
        $this->assertStringContainsString('relevance_thema', $resultColumns);
        $this->assertStringContainsString('relevance_content', $resultColumns);
        $this->assertStringContainsString('relevance_keywords', $resultColumns);
        
        // Generate the ORDER BY clause
        $orderBy = $this->pgsqlSearch->getMatchingOrder();
        
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
        // Set a different order in configuration using reflection
        $reflection = new ReflectionClass($this->configuration);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($this->configuration);
        $config['search.relevance'] = 'keywords,content,thema';
        $configProperty->setValue($this->configuration, $config);
        
        // Set matching columns
        $this->pgsqlSearch->setMatchingColumns(['fd.thema', 'fd.content', 'fd.keywords']);
        
        // Generate the SELECT and ORDER BY
        $this->pgsqlSearch->getMatchingColumnsAsResult();
        $orderBy = $this->pgsqlSearch->getMatchingOrder();
        
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
        $this->pgsqlSearch->setMatchingColumns(['fd.thema', 'fd.keywords']);
        
        // Generate the SELECT columns
        $resultColumns = $this->pgsqlSearch->getMatchingColumnsAsResult();
        
        // Verify content is not in the result
        $this->assertStringNotContainsString('relevance_content', $resultColumns);
        
        // Generate the ORDER BY clause
        $orderBy = $this->pgsqlSearch->getMatchingOrder();
        
        // Verify ORDER BY doesn't include content but includes others
        $this->assertStringContainsString('relevance_thema', $orderBy);
        $this->assertStringNotContainsString('relevance_content', $orderBy);
        $this->assertStringContainsString('relevance_keywords', $orderBy);
    }
}
