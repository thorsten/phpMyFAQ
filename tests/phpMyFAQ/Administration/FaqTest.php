<?php

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\TestCase;
use stdClass;

class FaqTest extends TestCase
{
    private Faq $faq;
    private Configuration $mockConfiguration;
    private DatabaseDriver $mockDb;

    protected function setUp(): void
    {
        // Mock Configuration class
        $this->mockConfiguration = $this->getMockBuilder(Configuration::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Mock Database class
        $this->mockDb = $this->getMockBuilder(DatabaseDriver::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Stub the getDb method of Configuration to return the mockDb object
        $this->mockConfiguration->method('getDb')->willReturn($this->mockDb);

        // Create the Faq instance with the mocked configuration
        $this->faq = new Faq($this->mockConfiguration);
    }

    public function testGetAllFaqsByCategoryReturnsEmptyArrayWhenNoResults(): void
    {
        $this->faq->setLanguage('en');

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('numRows')->willReturn(0);

        $result = $this->faq->getAllFaqsByCategory(1);

        $this->assertEmpty($result);
    }

    public function testGetAllFaqsByCategoryReturnsFaqData(): void
    {
        // Simulate a single database result object (row)
        $mockResult = $this->getMockBuilder(stdClass::class)->getMock();
        $mockResult->id = 1;
        $mockResult->lang = 'en';
        $mockResult->solution_id = 101;
        $mockResult->active = 'yes';
        $mockResult->sticky = 1;
        $mockResult->category_id = 1;
        $mockResult->question = 'What is PHP?';
        $mockResult->updated = '2023-01-01 12:00:00';
        $mockResult->visits = 100;
        $mockResult->created = '2022-01-01 12:00:00';

        // Mock database interactions
        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('numRows')->willReturn(1);

        // Simulate fetchObject being called twice: once returning the mock result, then false to stop
        $this->mockDb->method('fetchObject')
            ->willReturn($mockResult, false);

        // Set language for the FAQ
        $this->faq->setLanguage('en');

        // Call the method and assert the result
        $result = $this->faq->getAllFaqsByCategory(1);

        // Expected output
        $expected = [
            [
                'id' => 1,
                'language' => 'en',
                'solution_id' => 101,
                'active' => 'yes',
                'sticky' => 'yes',
                'category_id' => 1,
                'question' => 'What is PHP?',
                'updated' => '2023-01-01 12:00:00',
                'visits' => 100,
                'created' => '2022-01-01 12:00:00',
            ]
        ];

        // Assert the expected result
        $this->assertEquals($expected, $result);
    }

    public function testUpdateRecordFlagReturnsFalseForInvalidType(): void
    {
        $this->mockDb->expects($this->never())->method('query');

        $result = $this->faq->updateRecordFlag(1, 'en', true, 'invalid_type');

        $this->assertFalse($result);
    }

    public function testSetAndGetLanguage(): void
    {
        $this->faq->setLanguage('de');

        $this->assertEquals('de', $this->faq->getLanguage());
    }
}
