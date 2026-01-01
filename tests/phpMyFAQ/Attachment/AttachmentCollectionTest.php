<?php

namespace phpMyFAQ\Attachment;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AttachmentCollectionTest extends TestCase
{
    /**
     * @var Configuration
     */
    protected $mockConfiguration;

    /**
     * @var DatabaseDriver
     */
    protected $mockDatabase;

    /**
     * @var AttachmentCollection
     */
    protected $attachmentCollection;

    protected function setUp(): void
    {
        // Mock the Configuration object
        $this->mockConfiguration = $this->createStub(Configuration::class);

        // Mock the Database object
        $this->mockDatabase = $this->createStub(DatabaseDriver::class);

        // Set up the configuration to return the mock Database
        $this->mockConfiguration->method('getDb')->willReturn($this->mockDatabase);

        // Initialize the AttachmentCollection with the mocked Configuration
        $this->attachmentCollection = new AttachmentCollection($this->mockConfiguration);
    }

    public function testGetBreadcrumbsReturnsDataWhenQuerySucceeds(): void
    {
        // Sample data returned by the mocked fetchAll
        $expectedResult = [
            [
                'id' => 1,
                'record_id' => 101,
                'record_lang' => 'en',
                'filename' => 'file1.pdf',
                'filesize' => 123456,
                'mime_type' => 'application/pdf',
                'thema' => 'General',
            ],
            [
                'id' => 2,
                'record_id' => 102,
                'record_lang' => 'fr',
                'filename' => 'file2.jpg',
                'filesize' => 78910,
                'mime_type' => 'image/jpeg',
                'thema' => 'Media',
            ],
        ];

        // Mock the query method to return a non-false result
        $this->mockDatabase->method('query')->willReturn('mock_result');

        // Mock fetchAll to return the expected result
        $this->mockDatabase
            ->method('fetchAll')
            ->with('mock_result')
            ->willReturn($expectedResult);

        // Call the method being tested
        $result = $this->attachmentCollection->getBreadcrumbs();

        // Assert that the result matches the expected output
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetBreadcrumbsReturnsEmptyArrayWhenQueryFails(): void
    {
        // Mock the query method to return false (indicating a failure)
        $this->mockDatabase->method('query')->willReturn(false);

        // Call the method being tested
        $result = $this->attachmentCollection->getBreadcrumbs();

        // Assert that the result is an empty array
        $this->assertEquals([], $result);
    }
}
