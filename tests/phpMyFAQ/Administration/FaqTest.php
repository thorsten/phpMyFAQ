<?php

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Language;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class FaqTest extends TestCase
{
    private Faq $faq;
    private Configuration $mockConfiguration;
    private DatabaseDriver $mockDb;

    protected function setUp(): void
    {
        // Mock Configuration class
        $this->mockConfiguration = $this->createMock(Configuration::class);
        $this->mockConfiguration
            ->method('get')
            ->willReturnCallback(static fn(string $key): ?string => $key === 'security.permLevel' ? 'basic' : null);

        // Mock Database class
        $this->mockDb = $this->createMock(DatabaseDriver::class);

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
        $mockResult = $this->createMock(stdClass::class);
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
        $this->mockDb->method('fetchObject')->willReturn($mockResult, false);

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
            ],
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
        $this->mockDb->expects($this->never())->method('query');

        $this->faq->setLanguage('de');

        $this->assertEquals('de', $this->faq->getLanguage());
    }

    public function testSetStickyFaqOrderWithEmptyArray(): void
    {
        $this->mockDb->expects($this->never())->method('query');

        $result = $this->faq->setStickyFaqOrder([]);

        $this->assertTrue($result);
    }

    public function testSetStickyFaqOrderWithSingleFaq(): void
    {
        $this->mockDb
            ->expects($this->exactly(2))
            ->method('query')
            ->willReturnCallback(function (string $query) {
                static $callCount = 0;

                if ($callCount === 0) {
                    $this->assertStringContainsString('SELECT id FROM faqdata fd WHERE fd.id = 123', $query);
                    $callCount++;
                    return 'permission-result';
                }

                $this->assertSame('UPDATE faqdata SET sticky_order=1 WHERE id=123', $query);
                return true;
            });
        $this->mockDb->expects($this->once())->method('fetchObject')->with('permission-result')->willReturn((object) ['id' => 123]);

        $result = $this->faq->setStickyFaqOrder([123], 42);

        $this->assertTrue($result);
    }

    public function testSetStickyFaqOrderWithMultipleFaqs(): void
    {
        $faqIds = [456, 789, 123];

        $expectedPermissionQueries = [
            'SELECT id FROM faqdata fd WHERE fd.id = 456',
            'SELECT id FROM faqdata fd WHERE fd.id = 789',
            'SELECT id FROM faqdata fd WHERE fd.id = 123',
        ];
        $expectedUpdateQueries = [
            'UPDATE faqdata SET sticky_order=1 WHERE id=456',
            'UPDATE faqdata SET sticky_order=2 WHERE id=789',
            'UPDATE faqdata SET sticky_order=3 WHERE id=123',
        ];
        $callCount = 0;

        $this->mockDb
            ->expects($this->exactly(6))
            ->method('query')
            ->willReturnCallback(function (string $query) use (&$callCount, $expectedPermissionQueries, $expectedUpdateQueries) {
                if ($callCount < 3) {
                    $this->assertStringContainsString($expectedPermissionQueries[$callCount], $query);
                    $callCount++;
                    return 'permission-result-' . $callCount;
                }

                $this->assertEquals($expectedUpdateQueries[$callCount - 3], $query);
                $callCount++;
                return true;
            });
        $this->mockDb
            ->expects($this->exactly(3))
            ->method('fetchObject')
            ->willReturn((object) ['id' => 456], (object) ['id' => 789], (object) ['id' => 123]);

        $result = $this->faq->setStickyFaqOrder($faqIds, 42);

        $this->assertTrue($result);
    }

    public function testSetStickyFaqOrderReturnsFalseWhenUserCannotEditFaq(): void
    {
        $this->mockDb
            ->expects($this->once())
            ->method('query')
            ->willReturnCallback(function (string $query) {
                $this->assertStringContainsString('SELECT id FROM faqdata fd WHERE fd.id = 999', $query);
                return 'permission-result';
            });
        $this->mockDb->expects($this->once())->method('fetchObject')->with('permission-result')->willReturn(false);

        $result = $this->faq->setStickyFaqOrder([999], 42);

        $this->assertFalse($result);
    }

    public function testUpdateRecordFlagForStickyType(): void
    {
        $this->mockDb->method('escape')->willReturn('en');

        // Test setting sticky flag to true - match the actual formatted query
        $expectedQuery = "
                UPDATE 
                    faqdata 
                SET 
                    sticky = 1 
                WHERE 
                    id = 123 
                AND 
                    lang = 'en'";
        $this->mockDb->expects($this->once())->method('query')->with($expectedQuery)->willReturn(true);

        $result = $this->faq->updateRecordFlag(123, 'en', true, 'sticky');

        $this->assertTrue($result);
    }

    public function testUpdateRecordFlagForActiveType(): void
    {
        $this->mockDb->method('escape')->willReturn('de');

        // Test setting active flag to false - match the actual formatted query
        $expectedQuery = "
                UPDATE 
                    faqdata 
                SET 
                    active = 'no' 
                WHERE 
                    id = 456 
                AND 
                    lang = 'de'";
        $this->mockDb->expects($this->once())->method('query')->with($expectedQuery)->willReturn(true);

        $result = $this->faq->updateRecordFlag(456, 'de', false, 'active');

        $this->assertTrue($result);
    }

    public function testGetInactiveFaqsDataReturnsEmptyArrayWhenNoResults(): void
    {
        // Mock the configuration language
        $mockLanguage = $this->createMock(Language::class);
        $mockLanguage->method('getLanguage')->willReturn('en');
        $this->mockConfiguration->method('getLanguage')->willReturn($mockLanguage);

        $this->mockDb->expects($this->once())->method('query');
        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchObject')->willReturn(false);

        $result = $this->faq->getInactiveFaqsData();

        $this->assertEmpty($result);
    }

    public function testGetInactiveFaqsDataReturnsInactiveFaqs(): void
    {
        // Mock the configuration language and default URL
        $mockLanguage = $this->createMock(Language::class);
        $mockLanguage->method('getLanguage')->willReturn('en');
        $this->mockConfiguration->method('getLanguage')->willReturn($mockLanguage);
        $this->mockConfiguration->method('getDefaultUrl')->willReturn('http://example.com/');

        // Mock database result
        $mockResult = new stdClass();
        $mockResult->id = 1;
        $mockResult->lang = 'en';
        $mockResult->thema = 'Inactive FAQ Question';

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchObject')->willReturn($mockResult, false);

        $result = $this->faq->getInactiveFaqsData();

        $expected = [
            [
                'question' => 'Inactive FAQ Question',
                'url' => 'http://example.com/admin/faq/edit/1/en',
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGetOrphanedFaqsReturnsEmptyArrayWhenNoResults(): void
    {
        // Mock the configuration language
        $mockLanguage = $this->createMock(Language::class);
        $mockLanguage->method('getLanguage')->willReturn('en');
        $this->mockConfiguration->method('getLanguage')->willReturn($mockLanguage);

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchObject')->willReturn(false);

        $result = $this->faq->getOrphanedFaqs();

        $this->assertEmpty($result);
    }

    public function testGetOrphanedFaqsReturnsOrphanedFaqs(): void
    {
        // Mock the configuration language and default URL
        $mockLanguage = $this->createMock(Language::class);
        $mockLanguage->method('getLanguage')->willReturn('en');
        $this->mockConfiguration->method('getLanguage')->willReturn($mockLanguage);
        $this->mockConfiguration->method('getDefaultUrl')->willReturn('http://example.com/');

        // Mock database result
        $mockResult = new stdClass();
        $mockResult->id = 42;
        $mockResult->lang = 'en';
        $mockResult->question = 'Orphaned FAQ Question';

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchObject')->willReturn($mockResult, false);

        $result = $this->faq->getOrphanedFaqs();

        $this->assertCount(1, $result);
        $this->assertInstanceOf(stdClass::class, $result[0]);
        $this->assertEquals(42, $result[0]->faqId);
        $this->assertEquals('en', $result[0]->language);
        $this->assertEquals('Orphaned FAQ Question', $result[0]->question);
        $this->assertEquals('http://example.com/admin/faq/edit/42/en', $result[0]->url);
    }
}
