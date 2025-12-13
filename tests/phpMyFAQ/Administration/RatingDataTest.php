<?php

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Link;
use PHPUnit\Framework\TestCase;
use stdClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class RatingDataTest extends TestCase
{
    private RatingData $ratingData;
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

        // Create the RatingData instance with the mocked configuration
        $this->ratingData = new RatingData($this->mockConfiguration);
    }

    public function testGetAllReturnsEmptyArrayWhenNoResults(): void
    {
        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchObject')->willReturn(false);

        $result = $this->ratingData->getAll();

        $this->assertEmpty($result);
    }

    public function testGetAllReturnsSingleRating(): void
    {
        // Mock configuration default URL
        $this->mockConfiguration->method('getDefaultUrl')->willReturn('http://example.com/');

        // Mock database result
        $mockResult = new stdClass();
        $mockResult->id = 1;
        $mockResult->lang = 'en';
        $mockResult->category_id = 2;
        $mockResult->question = 'Test Question';
        $mockResult->num = 4.5;
        $mockResult->usr = 10;

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchObject')
            ->willReturn($mockResult, false);

        $result = $this->ratingData->getAll();

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals('en', $result[0]['lang']);
        $this->assertEquals(2, $result[0]['category_id']);
        $this->assertEquals('Test Question', $result[0]['question']);
        $this->assertEquals(4.5, $result[0]['number']);
        $this->assertEquals(10, $result[0]['user']);
        $this->assertStringContainsString('index.php?action=faq&cat=2&id=1&artlang=en', $result[0]['url']);
    }

    public function testGetAllReturnsMultipleRatings(): void
    {
        // Mock configuration default URL
        $this->mockConfiguration->method('getDefaultUrl')->willReturn('http://example.com/');

        // Mock database results
        $mockResult1 = new stdClass();
        $mockResult1->id = 1;
        $mockResult1->lang = 'en';
        $mockResult1->category_id = 2;
        $mockResult1->question = 'First Question';
        $mockResult1->num = 4.5;
        $mockResult1->usr = 10;

        $mockResult2 = new stdClass();
        $mockResult2->id = 2;
        $mockResult2->lang = 'de';
        $mockResult2->category_id = 3;
        $mockResult2->question = 'Second Question';
        $mockResult2->num = 3.8;
        $mockResult2->usr = 5;

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchObject')
            ->willReturn($mockResult1, $mockResult2, false);

        $result = $this->ratingData->getAll();

        $this->assertCount(2, $result);

        // First rating
        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals('First Question', $result[0]['question']);

        // Second rating
        $this->assertEquals(2, $result[1]['id']);
        $this->assertEquals('Second Question', $result[1]['question']);
    }

    public function testGetAllEscapesHtmlInQuestions(): void
    {
        // Mock configuration default URL
        $this->mockConfiguration->method('getDefaultUrl')->willReturn('http://example.com/');

        // Mock database result with HTML content
        $mockResult = new stdClass();
        $mockResult->id = 1;
        $mockResult->lang = 'en';
        $mockResult->category_id = 2;
        $mockResult->question = '<script>alert("test")</script>Test Question';
        $mockResult->num = 4.5;
        $mockResult->usr = 10;

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchObject')
            ->willReturn($mockResult, false);

        $result = $this->ratingData->getAll();

        $this->assertCount(1, $result);
        // Corrected expectation based on actual escaping behavior
        $this->assertEquals('&lt;script&gt;alert("test")&lt;/script&gt;Test Question', $result[0]['question']);
    }

    public function testGetAllHandlesWhitespaceInQuestions(): void
    {
        // Mock configuration default URL
        $this->mockConfiguration->method('getDefaultUrl')->willReturn('http://example.com/');

        // Mock database result with whitespace
        $mockResult = new stdClass();
        $mockResult->id = 1;
        $mockResult->lang = 'en';
        $mockResult->category_id = 2;
        $mockResult->question = '   Test Question with whitespace   ';
        $mockResult->num = 4.5;
        $mockResult->usr = 10;

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchObject')
            ->willReturn($mockResult, false);

        $result = $this->ratingData->getAll();

        $this->assertCount(1, $result);
        $this->assertEquals('Test Question with whitespace', $result[0]['question']);
    }

    public function testGetAllGeneratesCorrectUrl(): void
    {
        // Mock configuration default URL
        $this->mockConfiguration->method('getDefaultUrl')->willReturn('https://faq.example.com/');

        // Mock database result
        $mockResult = new stdClass();
        $mockResult->id = 42;
        $mockResult->lang = 'de';
        $mockResult->category_id = 15;
        $mockResult->question = 'German Question';
        $mockResult->num = 3.2;
        $mockResult->usr = 7;

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchObject')
            ->willReturn($mockResult, false);

        $result = $this->ratingData->getAll();

        $this->assertCount(1, $result);
        $expectedUrl = 'https://faq.example.com/index.php?action=faq&cat=15&id=42&artlang=de';
        $this->assertStringContainsString($expectedUrl, $result[0]['url']);
    }

    public function testGetAllVerifiesQueryStructure(): void
    {
        // Mock configuration default URL
        $this->mockConfiguration->method('getDefaultUrl')->willReturn('http://example.com/');

        // Verify that query method is called with a SQL string containing expected elements
        $this->mockDb->expects($this->once())
            ->method('query')
            ->willReturnCallback(function($query) {
                // Verify the query contains expected SQL components
                $this->assertStringContainsString('SELECT', $query);
                $this->assertStringContainsString('faqvoting', $query);
                $this->assertStringContainsString('faqdata', $query);
                $this->assertStringContainsString('faqcategoryrelations', $query);
                $this->assertStringContainsString('ORDER BY', $query);
                return true;
            });

        $this->mockDb->method('fetchObject')->willReturn(false);

        $result = $this->ratingData->getAll();

        $this->assertEmpty($result);
    }

    public function testGetAllHandlesNumericTypes(): void
    {
        // Mock configuration default URL
        $this->mockConfiguration->method('getDefaultUrl')->willReturn('http://example.com/');

        // Mock database result with various numeric types
        $mockResult = new stdClass();
        $mockResult->id = 5;
        $mockResult->lang = 'en';
        $mockResult->category_id = 8;
        $mockResult->question = 'Numeric Test Question';
        $mockResult->num = 2.75; // float rating
        $mockResult->usr = 12; // integer user count

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchObject')
            ->willReturn($mockResult, false);

        $result = $this->ratingData->getAll();

        $this->assertCount(1, $result);
        $this->assertIsFloat($result[0]['number']);
        $this->assertIsInt($result[0]['user']);
        $this->assertEquals(2.75, $result[0]['number']);
        $this->assertEquals(12, $result[0]['user']);
    }
}
