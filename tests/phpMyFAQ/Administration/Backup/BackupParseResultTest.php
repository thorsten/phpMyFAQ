<?php

namespace phpMyFAQ\Administration\Backup;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Class BackupParseResultTest
 *
 * @package phpMyFAQ\Administration\Backup
 */
#[AllowMockObjectsWithoutExpectations]
class BackupParseResultTest extends TestCase
{
    public function testConstructorWithMatchingVersions(): void
    {
        $queries = ['SELECT * FROM faqconfig', 'SELECT * FROM faqdata'];
        $result = new BackupParseResult(
            versionMatches: true,
            versionFound: '-- pmf4.0',
            versionExpected: '-- pmf4.0',
            queries: $queries,
            tablePrefix: 'pmf_',
        );

        $this->assertTrue($result->versionMatches);
        $this->assertEquals('-- pmf4.0', $result->versionFound);
        $this->assertEquals('-- pmf4.0', $result->versionExpected);
        $this->assertEquals($queries, $result->queries);
        $this->assertEquals('pmf_', $result->tablePrefix);
    }

    public function testConstructorWithNonMatchingVersions(): void
    {
        $queries = ['SELECT * FROM faqconfig'];
        $result = new BackupParseResult(
            versionMatches: false,
            versionFound: '-- pmf3.2',
            versionExpected: '-- pmf4.0',
            queries: $queries,
            tablePrefix: '',
        );

        $this->assertFalse($result->versionMatches);
        $this->assertEquals('-- pmf3.2', $result->versionFound);
        $this->assertEquals('-- pmf4.0', $result->versionExpected);
        $this->assertEquals($queries, $result->queries);
        $this->assertEquals('', $result->tablePrefix);
    }

    public function testConstructorWithEmptyQueries(): void
    {
        $result = new BackupParseResult(
            versionMatches: true,
            versionFound: '-- pmf4.0',
            versionExpected: '-- pmf4.0',
            queries: [],
            tablePrefix: 'pmf_',
        );

        $this->assertTrue($result->versionMatches);
        $this->assertEmpty($result->queries);
        $this->assertEquals('pmf_', $result->tablePrefix);
    }

    public function testConstructorWithEmptyTablePrefix(): void
    {
        $queries = ['INSERT INTO faqconfig VALUES (1)'];
        $result = new BackupParseResult(
            versionMatches: true,
            versionFound: '-- pmf4.0',
            versionExpected: '-- pmf4.0',
            queries: $queries,
            tablePrefix: '',
        );

        $this->assertEquals('', $result->tablePrefix);
        $this->assertNotEmpty($result->queries);
    }

    public function testConstructorWithMultipleQueries(): void
    {
        $queries = [
            'DELETE FROM faqconfig',
            'DELETE FROM faqdata',
            'INSERT INTO faqconfig VALUES (1, "test")',
            'INSERT INTO faqdata VALUES (2, "data")',
        ];

        $result = new BackupParseResult(
            versionMatches: true,
            versionFound: '-- pmf4.0',
            versionExpected: '-- pmf4.0',
            queries: $queries,
            tablePrefix: 'pmf_',
        );

        $this->assertCount(4, $result->queries);
        $this->assertEquals($queries, $result->queries);
    }

    public function testConstructorWithComplexTablePrefix(): void
    {
        $queries = ['SELECT * FROM prefix_faqconfig'];
        $result = new BackupParseResult(
            versionMatches: true,
            versionFound: '-- pmf4.0',
            versionExpected: '-- pmf4.0',
            queries: $queries,
            tablePrefix: 'my_custom_prefix_',
        );

        $this->assertEquals('my_custom_prefix_', $result->tablePrefix);
    }

    public function testReadonlyProperties(): void
    {
        $queries = ['SELECT 1'];
        $result = new BackupParseResult(
            versionMatches: true,
            versionFound: '-- pmf4.0',
            versionExpected: '-- pmf4.0',
            queries: $queries,
            tablePrefix: 'test_',
        );

        // Verify all properties are accessible
        $this->assertIsBool($result->versionMatches);
        $this->assertIsString($result->versionFound);
        $this->assertIsString($result->versionExpected);
        $this->assertIsArray($result->queries);
        $this->assertIsString($result->tablePrefix);
    }

    public function testConstructorWithDifferentVersionFormats(): void
    {
        $result = new BackupParseResult(
            versionMatches: false,
            versionFound: '-- pmf4.1',
            versionExpected: '-- pmf4.0',
            queries: [],
            tablePrefix: '',
        );

        $this->assertFalse($result->versionMatches);
        $this->assertNotEquals($result->versionFound, $result->versionExpected);
    }

    public function testConstructorWithDeleteQueries(): void
    {
        $queries = [
            'DELETE FROM faqconfig',
            'DELETE FROM faqdata',
            'DELETE FROM faqcategories',
        ];

        $result = new BackupParseResult(
            versionMatches: true,
            versionFound: '-- pmf4.0',
            versionExpected: '-- pmf4.0',
            queries: $queries,
            tablePrefix: '',
        );

        foreach ($result->queries as $query) {
            $this->assertStringStartsWith('DELETE FROM', $query);
        }
    }

    public function testConstructorWithInsertQueries(): void
    {
        $queries = [
            'INSERT INTO faqconfig (id, meta_key, meta_value) VALUES (1, "key", "value")',
            'INSERT INTO faqdata (id, lang, thema) VALUES (1, "en", "Test")',
        ];

        $result = new BackupParseResult(
            versionMatches: true,
            versionFound: '-- pmf4.0',
            versionExpected: '-- pmf4.0',
            queries: $queries,
            tablePrefix: 'pmf_',
        );

        foreach ($result->queries as $query) {
            $this->assertStringStartsWith('INSERT INTO', $query);
        }
    }

    public function testConstructorWithMixedQueries(): void
    {
        $queries = [
            'DELETE FROM faqconfig',
            'INSERT INTO faqconfig VALUES (1)',
            'UPDATE faqconfig SET meta_value = "test" WHERE id = 1',
        ];

        $result = new BackupParseResult(
            versionMatches: true,
            versionFound: '-- pmf4.0',
            versionExpected: '-- pmf4.0',
            queries: $queries,
            tablePrefix: '',
        );

        $this->assertCount(3, $result->queries);
        $this->assertStringContainsString('DELETE', $result->queries[0]);
        $this->assertStringContainsString('INSERT', $result->queries[1]);
        $this->assertStringContainsString('UPDATE', $result->queries[2]);
    }
}
