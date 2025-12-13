<?php

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\TestCase;
use stdClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class RevisionTest extends TestCase
{
    private Revision $revision;
    private Configuration $mockConfiguration;
    private DatabaseDriver $mockDb;

    protected function setUp(): void
    {
        // Mock Configuration class
        $this->mockConfiguration = $this->createStub(Configuration::class);

        // Mock Database class
        $this->mockDb = $this->getMockBuilder(DatabaseDriver::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Stub the getDb method of Configuration to return the mockDb object
        $this->mockConfiguration->method('getDb')->willReturn($this->mockDb);

        // Create the Revision instance with the mocked configuration
        $this->revision = new Revision($this->mockConfiguration);
    }

    public function testCreateReturnsTrue(): void
    {
        $this->mockDb->method('escape')->willReturn('en');
        $this->mockDb->method('query')->willReturn(true);

        $result = $this->revision->create(123, 'en');

        $this->assertTrue($result);
    }

    public function testCreateGeneratesCorrectQuery(): void
    {
        $this->mockDb->method('escape')->willReturn('de');

        // Verify the SQL query structure
        $this->mockDb->expects($this->once())
            ->method('query')
            ->willReturnCallback(function($query) {
                // Verify the query contains expected SQL components
                $this->assertStringContainsString('INSERT INTO', $query);
                $this->assertStringContainsString('faqdata_revisions', $query);
                $this->assertStringContainsString('SELECT', $query);
                $this->assertStringContainsString('revision_id + 1', $query);
                $this->assertStringContainsString('faqdata', $query);
                $this->assertStringContainsString('WHERE', $query);
                $this->assertStringContainsString('id = 123', $query);
                $this->assertStringContainsString("lang = 'de'", $query);
                return true;
            });

        $result = $this->revision->create(123, 'de');

        $this->assertTrue($result);
    }

    public function testCreateEscapesFaqLanguage(): void
    {
        // Test SQL injection protection
        $dangerousInput = "'; DROP TABLE faqdata; --";
        $escapedInput = "\\'; DROP TABLE faqdata; --";

        $this->mockDb->method('escape')->willReturn($escapedInput);
        $this->mockDb->method('query')->willReturn(true);

        $this->mockDb->expects($this->once())
            ->method('escape')
            ->with($dangerousInput)
            ->willReturn($escapedInput);

        $result = $this->revision->create(456, $dangerousInput);

        $this->assertTrue($result);
    }

    public function testGetReturnsEmptyArrayWhenNoResults(): void
    {
        $this->mockDb->method('escape')->willReturn('en');
        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('numRows')->willReturn(0);

        $result = $this->revision->get(123, 'en', 'John Doe');

        $this->assertEmpty($result);
    }

    public function testGetReturnsSingleRevision(): void
    {
        $this->mockDb->method('escape')->willReturn('en');
        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('numRows')->willReturn(1);

        // Mock database result
        $mockResult = new stdClass();
        $mockResult->revision_id = 1;
        $mockResult->updated = '20250804120000';
        $mockResult->author = 'Jane Smith';

        $this->mockDb->method('fetchObject')
            ->willReturn($mockResult, false);

        $result = $this->revision->get(123, 'en', 'John Doe');

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]['revision_id']);
        $this->assertEquals('20250804120000', $result[0]['updated']);
        $this->assertEquals('Jane Smith', $result[0]['author']);
    }

    public function testGetReturnsMultipleRevisions(): void
    {
        $this->mockDb->method('escape')->willReturn('de');
        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('numRows')->willReturn(2);

        // Mock database results
        $mockResult1 = new stdClass();
        $mockResult1->revision_id = 1;
        $mockResult1->updated = '20250804120000';
        $mockResult1->author = 'Author One';

        $mockResult2 = new stdClass();
        $mockResult2->revision_id = 2;
        $mockResult2->updated = '20250804140000';
        $mockResult2->author = 'Author Two';

        $this->mockDb->method('fetchObject')
            ->willReturn($mockResult1, $mockResult2, false);

        $result = $this->revision->get(456, 'de', 'Fallback Author');

        $this->assertCount(2, $result);

        // First revision
        $this->assertEquals(1, $result[0]['revision_id']);
        $this->assertEquals('20250804120000', $result[0]['updated']);
        $this->assertEquals('Author One', $result[0]['author']);

        // Second revision
        $this->assertEquals(2, $result[1]['revision_id']);
        $this->assertEquals('20250804140000', $result[1]['updated']);
        $this->assertEquals('Author Two', $result[1]['author']);
    }

    public function testGetHandlesFaqIdZeroWithCurrentTimestamp(): void
    {
        $this->mockDb->method('escape')->willReturn('en');
        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('numRows')->willReturn(1);

        // Mock database result
        $mockResult = new stdClass();
        $mockResult->revision_id = 1;
        $mockResult->updated = '20250804120000'; // This should be ignored for FAQ ID 0
        $mockResult->author = 'Database Author'; // This should be ignored for FAQ ID 0

        $this->mockDb->method('fetchObject')
            ->willReturn($mockResult, false);

        $result = $this->revision->get(0, 'en', 'john doe');

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]['revision_id']);

        // Should use current timestamp (format: YmdHis)
        $this->assertMatchesRegularExpression('/^\d{14}$/', $result[0]['updated']);

        // Should use ucfirst of provided author
        $this->assertEquals('John doe', $result[0]['author']);
    }

    public function testGetGeneratesCorrectQuery(): void
    {
        $this->mockDb->method('escape')->willReturn('fr');
        $this->mockDb->method('numRows')->willReturn(0);

        // Verify the SQL query structure
        $this->mockDb->expects($this->once())
            ->method('query')
            ->willReturnCallback(function($query) {
                // Verify the query contains expected SQL components
                $this->assertStringContainsString('SELECT', $query);
                $this->assertStringContainsString('revision_id, updated, author', $query);
                $this->assertStringContainsString('faqdata_revisions', $query);
                $this->assertStringContainsString('WHERE', $query);
                $this->assertStringContainsString('id = 789', $query);
                $this->assertStringContainsString("lang = 'fr'", $query);
                $this->assertStringContainsString('ORDER BY', $query);
                $this->assertStringContainsString('revision_id', $query);
                return true;
            });

        $result = $this->revision->get(789, 'fr', 'Test Author');

        $this->assertEmpty($result);
    }

    public function testGetEscapesFaqLanguage(): void
    {
        $dangerousInput = "'; DROP TABLE faqdata_revisions; --";
        $escapedInput = "\\'; DROP TABLE faqdata_revisions; --";

        $this->mockDb->method('escape')->willReturn($escapedInput);
        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('numRows')->willReturn(0);

        $this->mockDb->expects($this->once())
            ->method('escape')
            ->with($dangerousInput)
            ->willReturn($escapedInput);

        $result = $this->revision->get(123, $dangerousInput, 'Author');

        $this->assertEmpty($result);
    }

    public function testDeleteReturnsTrueOnSuccess(): void
    {
        $this->mockDb->method('escape')->willReturn('en');
        $this->mockDb->method('query')->willReturn(true);

        $result = $this->revision->delete(123, 'en');

        $this->assertTrue($result);
    }

    public function testDeleteReturnsFalseOnFailure(): void
    {
        $this->mockDb->method('escape')->willReturn('en');
        $this->mockDb->method('query')->willReturn(false);

        $result = $this->revision->delete(123, 'en');

        $this->assertFalse($result);
    }

    public function testDeleteGeneratesCorrectQuery(): void
    {
        $this->mockDb->method('escape')->willReturn('it');

        // Verify the SQL query structure
        $this->mockDb->expects($this->once())
            ->method('query')
            ->willReturnCallback(function($query) {
                // Verify the query contains expected SQL components
                $this->assertStringContainsString('DELETE FROM', $query);
                $this->assertStringContainsString('faqdata_revisions', $query);
                $this->assertStringContainsString('WHERE', $query);
                $this->assertStringContainsString('id = 456', $query);
                $this->assertStringContainsString("lang = 'it'", $query);
                return true;
            });

        $result = $this->revision->delete(456, 'it');

        $this->assertTrue($result);
    }

    public function testDeleteEscapesFaqLanguage(): void
    {
        $dangerousInput = "'; DROP TABLE faqdata; --";
        $escapedInput = "\\'; DROP TABLE faqdata; --";

        $this->mockDb->method('escape')->willReturn($escapedInput);
        $this->mockDb->method('query')->willReturn(true);

        $this->mockDb->expects($this->once())
            ->method('escape')
            ->with($dangerousInput)
            ->willReturn($escapedInput);

        $result = $this->revision->delete(789, $dangerousInput);

        $this->assertTrue($result);
    }

    public function testDeleteWithNullResult(): void
    {
        $this->mockDb->method('escape')->willReturn('en');
        $this->mockDb->method('query')->willReturn(null);

        $result = $this->revision->delete(123, 'en');

        $this->assertFalse($result);
    }

    public function testAllMethodsWithSpecialCharacters(): void
    {
        $specialLanguage = 'zh-cn';
        $this->mockDb->method('escape')->willReturn($specialLanguage);
        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('numRows')->willReturn(0);

        // Test create with special characters
        $createResult = $this->revision->create(100, $specialLanguage);
        $this->assertTrue($createResult);

        // Test get with special characters
        $getResult = $this->revision->get(100, $specialLanguage, 'special author');
        $this->assertEmpty($getResult);

        // Test delete with special characters
        $deleteResult = $this->revision->delete(100, $specialLanguage);
        $this->assertTrue($deleteResult);
    }
}
