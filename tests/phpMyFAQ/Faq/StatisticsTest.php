<?php

namespace phpMyFAQ\Faq;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

/**
 * Class StatisticsTest
 *
 * @package phpMyFAQ\Faq
 */
#[AllowMockObjectsWithoutExpectations]
class StatisticsTest extends TestCase
{
    private Statistics $statistics;
    private Configuration $configurationMock;
    private Sqlite3 $dbMock;

    protected function setUp(): void
    {
        parent::setUp();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        // Create database mock
        $this->dbMock = $this->createMock(Sqlite3::class);

        // Create configuration mock
        $this->configurationMock = $this->createMock(Configuration::class);
        $this->configurationMock->method('getDb')->willReturn($this->dbMock);

        // Default to basic security level
        $this->configurationMock
            ->method('get')
            ->with('security.permLevel')
            ->willReturn('basic');
    }

    public function testConstructorWithBasicSecurityLevel(): void
    {
        $this->configurationMock
            ->expects($this->once())
            ->method('get')
            ->with('security.permLevel')
            ->willReturn('basic');

        $statistics = new Statistics($this->configurationMock);

        $this->assertInstanceOf(Statistics::class, $statistics);

        $reflection = new ReflectionClass($statistics);

        $groupSupportProperty = $reflection->getProperty('groupSupport');
        $this->assertFalse($groupSupportProperty->getValue($statistics));

        $pluralsProperty = $reflection->getProperty('plurals');
        $this->assertInstanceOf(Plurals::class, $pluralsProperty->getValue($statistics));
    }

    public function testConstructorWithAdvancedSecurityLevel(): void
    {
        $configMock = $this->createMock(Configuration::class);
        $configMock->method('getDb')->willReturn($this->dbMock);
        $configMock->expects($this->once())->method('get')->with('security.permLevel')->willReturn('medium');

        $statistics = new Statistics($configMock);

        $this->assertInstanceOf(Statistics::class, $statistics);

        $reflection = new ReflectionClass($statistics);
        $groupSupportProperty = $reflection->getProperty('groupSupport');
        $this->assertTrue($groupSupportProperty->getValue($statistics));
    }

    public function testConstructorInitializesPlurals(): void
    {
        $statistics = new Statistics($this->configurationMock);

        $reflection = new ReflectionClass($statistics);
        $pluralsProperty = $reflection->getProperty('plurals');
        $plurals = $pluralsProperty->getValue($statistics);

        $this->assertInstanceOf(Plurals::class, $plurals);
    }

    public function testSetUserWithValidUserId(): void
    {
        $this->configurationMock->method('get')->willReturn('basic');
        $statistics = new Statistics($this->configurationMock);

        $result = $statistics->setUser(123);

        $this->assertInstanceOf(Statistics::class, $result);
        $this->assertSame($statistics, $result);

        $reflection = new ReflectionClass($statistics);
        $userProperty = $reflection->getProperty('user');
        $this->assertEquals(123, $userProperty->getValue($statistics));
    }

    public function testSetUserWithNegativeOne(): void
    {
        $this->configurationMock->method('get')->willReturn('basic');
        $statistics = new Statistics($this->configurationMock);

        $statistics->setUser(-1);

        $reflection = new ReflectionClass($statistics);
        $userProperty = $reflection->getProperty('user');
        $this->assertEquals(-1, $userProperty->getValue($statistics));
    }

    public function testSetUserFluentInterface(): void
    {
        $this->configurationMock->method('get')->willReturn('basic');
        $statistics = new Statistics($this->configurationMock);

        $result = $statistics->setUser(456)->setUser(789);

        $this->assertInstanceOf(Statistics::class, $result);

        $reflection = new ReflectionClass($statistics);
        $userProperty = $reflection->getProperty('user');
        $this->assertEquals(789, $userProperty->getValue($statistics));
    }

    public function testSetGroupsWithValidArray(): void
    {
        $this->configurationMock->method('get')->willReturn('basic');
        $statistics = new Statistics($this->configurationMock);

        $groups = [1, 2, 3, 5, 8];
        $result = $statistics->setGroups($groups);

        $this->assertInstanceOf(Statistics::class, $result);
        $this->assertSame($statistics, $result);

        $reflection = new ReflectionClass($statistics);
        $groupsProperty = $reflection->getProperty('groups');
        $this->assertEquals($groups, $groupsProperty->getValue($statistics));
    }

    public function testSetGroupsWithEmptyArray(): void
    {
        $this->configurationMock->method('get')->willReturn('basic');
        $statistics = new Statistics($this->configurationMock);

        $statistics->setGroups([]);

        $reflection = new ReflectionClass($statistics);
        $groupsProperty = $reflection->getProperty('groups');
        $this->assertEquals([], $groupsProperty->getValue($statistics));
    }

    public function testSetGroupsWithDefaultMinusOne(): void
    {
        $this->configurationMock->method('get')->willReturn('basic');
        $statistics = new Statistics($this->configurationMock);

        $statistics->setGroups([-1]);

        $reflection = new ReflectionClass($statistics);
        $groupsProperty = $reflection->getProperty('groups');
        $this->assertEquals([-1], $groupsProperty->getValue($statistics));
    }

    public function testSetGroupsFluentInterface(): void
    {
        $this->configurationMock->method('get')->willReturn('basic');
        $statistics = new Statistics($this->configurationMock);

        $result = $statistics->setGroups([1, 2])->setGroups([3, 4, 5]);

        $this->assertInstanceOf(Statistics::class, $result);

        $reflection = new ReflectionClass($statistics);
        $groupsProperty = $reflection->getProperty('groups');
        $this->assertEquals([3, 4, 5], $groupsProperty->getValue($statistics));
    }

    public function testDefaultUserAndGroupsValues(): void
    {
        $this->configurationMock->method('get')->willReturn('basic');
        $statistics = new Statistics($this->configurationMock);

        $reflection = new ReflectionClass($statistics);

        $userProperty = $reflection->getProperty('user');
        $this->assertEquals(-1, $userProperty->getValue($statistics));

        $groupsProperty = $reflection->getProperty('groups');
        $this->assertEquals([-1], $groupsProperty->getValue($statistics));
    }

    public function testFluentInterfaceChaining(): void
    {
        $this->configurationMock->method('get')->willReturn('basic');
        $statistics = new Statistics($this->configurationMock);

        $result = $statistics->setUser(999)->setGroups([10, 20, 30])->setUser(888);

        $this->assertInstanceOf(Statistics::class, $result);

        $reflection = new ReflectionClass($statistics);

        $userProperty = $reflection->getProperty('user');
        $this->assertEquals(888, $userProperty->getValue($result));

        $groupsProperty = $reflection->getProperty('groups');
        $this->assertEquals([10, 20, 30], $groupsProperty->getValue($result));
    }

    public function testTotalFaqsWithoutLanguage(): void
    {
        $this->configurationMock->method('get')->willReturn('basic');
        $statistics = new Statistics($this->configurationMock);

        $expectedQuery =
            "SELECT id FROM faqdata WHERE active = 'yes'  AND date_start <= '"
            . date(format: 'YmdHis')
            . "' AND date_end >= '"
            . date(format: 'YmdHis')
            . "'";

        $resultMock = $this->createStub(\SQLite3Result::class);

        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains("SELECT id FROM faqdata WHERE active = 'yes'"))
            ->willReturn($resultMock);

        $this->dbMock
            ->expects($this->once())
            ->method('numRows')
            ->with($resultMock)
            ->willReturn(5);

        $result = $statistics->totalFaqs();

        $this->assertEquals(5, $result);
    }

    public function testTotalFaqsWithSpecificLanguage(): void
    {
        $this->configurationMock->method('get')->willReturn('basic');
        $statistics = new Statistics($this->configurationMock);

        $resultMock = $this->createStub(\SQLite3Result::class);

        $this->dbMock
            ->expects($this->once())
            ->method('escape')
            ->with('en')
            ->willReturn('en');

        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains("AND lang = 'en'"))
            ->willReturn($resultMock);

        $this->dbMock
            ->expects($this->once())
            ->method('numRows')
            ->with($resultMock)
            ->willReturn(3);

        $result = $statistics->totalFaqs('en');

        $this->assertEquals(3, $result);
    }

    public function testTotalFaqsWithNoResults(): void
    {
        $this->configurationMock->method('get')->willReturn('basic');
        $statistics = new Statistics($this->configurationMock);

        $resultMock = $this->createStub(\SQLite3Result::class);

        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->willReturn($resultMock);

        $this->dbMock
            ->expects($this->once())
            ->method('numRows')
            ->with($resultMock)
            ->willReturn(0);

        $result = $statistics->totalFaqs();

        $this->assertEquals(0, $result);
    }

    public function testTotalFaqsWithNegativeNumRows(): void
    {
        $this->configurationMock->method('get')->willReturn('basic');
        $statistics = new Statistics($this->configurationMock);

        $resultMock = $this->createStub(\SQLite3Result::class);

        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->willReturn($resultMock);

        $this->dbMock
            ->expects($this->once())
            ->method('numRows')
            ->with($resultMock)
            ->willReturn(-1);

        $result = $statistics->totalFaqs();

        $this->assertEquals(0, $result);
    }

    public function testTotalFaqsQueryContainsDateFilter(): void
    {
        $this->configurationMock->method('get')->willReturn('basic');
        $statistics = new Statistics($this->configurationMock);

        $now = date(format: 'YmdHis');
        $resultMock = $this->createStub(\SQLite3Result::class);

        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with($this->logicalAnd(
                $this->stringContains("date_start <= '$now'"),
                $this->stringContains("date_end >= '$now'"),
            ))
            ->willReturn($resultMock);

        $this->dbMock->method('numRows')->willReturn(0);

        $statistics->totalFaqs();
    }

    public function testTotalFaqsEscapesLanguageParameter(): void
    {
        $this->configurationMock->method('get')->willReturn('basic');
        $statistics = new Statistics($this->configurationMock);

        $maliciousLanguage = "en'; DROP TABLE faqdata; --";
        $escapedLanguage = "en\\'; DROP TABLE faqdata; --";

        $this->dbMock
            ->expects($this->once())
            ->method('escape')
            ->with($maliciousLanguage)
            ->willReturn($escapedLanguage);

        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains($escapedLanguage))
            ->willReturn($this->createMock(\SQLite3Result::class));

        $this->dbMock->method('numRows')->willReturn(0);

        $statistics->totalFaqs($maliciousLanguage);
    }

    // =============================================
    // getLatestData() Tests
    // =============================================

    private function createConfigWithLanguage(): Configuration&MockObject
    {
        Strings::init();

        $configMock = $this->createMock(Configuration::class);
        $configMock->method('getDb')->willReturn($this->dbMock);
        $configMock->method('get')->willReturn('basic');
        $configMock->method('getDefaultUrl')->willReturn('https://example.com/');

        $langMock = $this->createMock(Language::class);
        $langMock->method('getLanguage')->willReturn('en');
        $configMock->method('getLanguage')->willReturn($langMock);

        return $configMock;
    }

    private function createFaqRow(
        int $id,
        string $lang,
        int $categoryId,
        string $question,
        string $content,
        string $updated,
        int $visits,
        int $groupId = -1,
        int $userId = -1,
    ): stdClass {
        $row = new stdClass();
        $row->id = $id;
        $row->lang = $lang;
        $row->language = $lang;
        $row->category_id = $categoryId;
        $row->question = $question;
        $row->content = $content;
        $row->updated = $updated;
        $row->created = $updated;
        $row->visits = $visits;
        $row->last_visit = time();
        $row->group_id = $groupId;
        $row->user_id = $userId;
        // For getTopVotedData
        $row->thema = $question;
        $row->avg = 4.5;
        $row->user = 10;
        $row->answer = $content;
        return $row;
    }

    public function testGetLatestDataWithResults(): void
    {
        $configMock = $this->createConfigWithLanguage();
        $statistics = new Statistics($configMock);

        $resultMock = $this->createStub(\SQLite3Result::class);
        $this->dbMock->method('query')->willReturn($resultMock);
        $this->dbMock->method('escape')->willReturn('en');

        $row = $this->createFaqRow(1, 'en', 1, 'Test Question', 'Answer', '20260101120000', 42);
        $this->dbMock->method('fetchObject')->willReturnOnConsecutiveCalls($row, null);

        $result = $statistics->getLatestData(10, 'en');

        self::assertCount(1, $result);
        self::assertArrayHasKey(1, $result);
        self::assertEquals('Test Question', $result[1]['question']);
        self::assertArrayHasKey('url', $result[1]);
        self::assertArrayHasKey('date', $result[1]);
        self::assertEquals(42, $result[1]['visits']);
    }

    public function testGetLatestDataRespectsCountLimit(): void
    {
        $configMock = $this->createConfigWithLanguage();
        $statistics = new Statistics($configMock);

        $resultMock = $this->createStub(\SQLite3Result::class);
        $this->dbMock->method('query')->willReturn($resultMock);
        $this->dbMock->method('escape')->willReturn('en');

        $row1 = $this->createFaqRow(1, 'en', 1, 'Q1', 'A1', '20260101', 10);
        $row2 = $this->createFaqRow(2, 'en', 1, 'Q2', 'A2', '20260102', 20);
        $row3 = $this->createFaqRow(3, 'en', 1, 'Q3', 'A3', '20260103', 30);
        $this->dbMock->method('fetchObject')->willReturnOnConsecutiveCalls($row1, $row2, $row3, null);

        $result = $statistics->getLatestData(2, 'en');

        self::assertCount(2, $result);
    }

    public function testGetLatestDataWithNoResults(): void
    {
        $configMock = $this->createConfigWithLanguage();
        $statistics = new Statistics($configMock);

        $resultMock = $this->createStub(\SQLite3Result::class);
        $this->dbMock->method('query')->willReturn($resultMock);

        $this->dbMock->method('fetchObject')->willReturn(null);

        $result = $statistics->getLatestData(10);

        self::assertEmpty($result);
    }

    public function testGetLatestDataWithFailedQuery(): void
    {
        $configMock = $this->createConfigWithLanguage();
        $statistics = new Statistics($configMock);

        $this->dbMock->method('query')->willReturn(false);

        $result = $statistics->getLatestData(10);

        self::assertEmpty($result);
    }

    public function testGetLatestDataSkipsUnauthorizedUserWithBasicPerm(): void
    {
        $configMock = $this->createConfigWithLanguage();
        $statistics = new Statistics($configMock);
        $statistics->setUser(5);

        $resultMock = $this->createStub(\SQLite3Result::class);
        $this->dbMock->method('query')->willReturn($resultMock);

        // Row with user_id=99 should be skipped (user is 5, not -1 or 5)
        $row = $this->createFaqRow(1, 'en', 1, 'Q', 'A', '20260101', 1, -1, 99);
        $this->dbMock->method('fetchObject')->willReturnOnConsecutiveCalls($row, null);

        $result = $statistics->getLatestData(10);

        self::assertEmpty($result);
    }

    public function testGetLatestDataWithGroupSupport(): void
    {
        $configMock = $this->createMock(Configuration::class);
        $configMock->method('getDb')->willReturn($this->dbMock);
        $configMock->method('get')->willReturn('medium'); // enables group support
        $configMock->method('getDefaultUrl')->willReturn('https://example.com/');
        $langMock = $this->createMock(Language::class);
        $langMock->method('getLanguage')->willReturn('en');
        $configMock->method('getLanguage')->willReturn($langMock);

        Strings::init();
        $statistics = new Statistics($configMock);
        $statistics->setUser(5)->setGroups([10, 20]);

        $resultMock = $this->createStub(\SQLite3Result::class);
        $this->dbMock->method('query')->willReturn($resultMock);
        $this->dbMock->method('escape')->willReturn('en');

        // Row with matching user but wrong group
        $rowBadGroup = $this->createFaqRow(1, 'en', 1, 'Q1', 'A1', '20260101', 1, 99, 5);
        // Row with matching user and group
        $rowGood = $this->createFaqRow(2, 'en', 1, 'Q2', 'A2', '20260102', 2, 10, 5);
        $this->dbMock->method('fetchObject')->willReturnOnConsecutiveCalls($rowBadGroup, $rowGood, null);

        $result = $statistics->getLatestData(10, 'en');

        // Only the row with matching group should be included
        self::assertCount(1, $result);
        self::assertArrayHasKey(2, $result);
    }

    public function testGetLatestDataWithGroupSupportSkipsBadUser(): void
    {
        $configMock = $this->createMock(Configuration::class);
        $configMock->method('getDb')->willReturn($this->dbMock);
        $configMock->method('get')->willReturn('medium');
        $configMock->method('getDefaultUrl')->willReturn('https://example.com/');
        $langMock = $this->createMock(Language::class);
        $langMock->method('getLanguage')->willReturn('en');
        $configMock->method('getLanguage')->willReturn($langMock);

        Strings::init();
        $statistics = new Statistics($configMock);
        $statistics->setUser(5)->setGroups([10]);

        $resultMock = $this->createStub(\SQLite3Result::class);
        $this->dbMock->method('query')->willReturn($resultMock);

        // Row with wrong user_id (not -1 and not 5)
        $row = $this->createFaqRow(1, 'en', 1, 'Q', 'A', '20260101', 1, 10, 99);
        $this->dbMock->method('fetchObject')->willReturnOnConsecutiveCalls($row, null);

        $result = $statistics->getLatestData(10);

        self::assertEmpty($result);
    }

    // =============================================
    // getTopTenData() Tests
    // =============================================

    public function testGetTopTenDataWithResults(): void
    {
        $configMock = $this->createConfigWithLanguage();
        $statistics = new Statistics($configMock);

        $resultMock = $this->createStub(\SQLite3Result::class);
        $this->dbMock->method('query')->willReturn($resultMock);
        $this->dbMock->method('escape')->willReturn('en');

        $row = $this->createFaqRow(1, 'en', 1, 'Popular FAQ', 'Answer', '20260101120000', 100);
        $this->dbMock->method('fetchObject')->willReturnOnConsecutiveCalls($row, null);

        $result = $statistics->getTopTenData(10, 0, 'en');

        self::assertCount(1, $result);
        $first = reset($result);
        self::assertEquals(100, $first['visits']);
        self::assertEquals('Popular FAQ', $first['question']);
        self::assertArrayHasKey('url', $first);
        self::assertArrayHasKey('last_visit', $first);
    }

    public function testGetTopTenDataWithCategoryFilter(): void
    {
        $configMock = $this->createConfigWithLanguage();
        $statistics = new Statistics($configMock);

        $resultMock = $this->createStub(\SQLite3Result::class);
        $this->dbMock
            ->method('query')
            ->with($this->stringContains("fcr.category_id = '5'"))
            ->willReturn($resultMock);
        $this->dbMock->method('escape')->willReturn('en');
        $this->dbMock->method('fetchObject')->willReturn(null);

        $result = $statistics->getTopTenData(10, 5, 'en');

        self::assertEmpty($result);
    }

    public function testGetTopTenDataRespectsCountLimit(): void
    {
        $configMock = $this->createConfigWithLanguage();
        $statistics = new Statistics($configMock);

        $resultMock = $this->createStub(\SQLite3Result::class);
        $this->dbMock->method('query')->willReturn($resultMock);

        $row1 = $this->createFaqRow(1, 'en', 1, 'Q1', 'A1', '20260101', 100);
        $row2 = $this->createFaqRow(2, 'en', 1, 'Q2', 'A2', '20260102', 90);
        $row3 = $this->createFaqRow(3, 'en', 1, 'Q3', 'A3', '20260103', 80);
        $this->dbMock->method('fetchObject')->willReturnOnConsecutiveCalls($row1, $row2, $row3, null);

        $result = $statistics->getTopTenData(2);

        self::assertCount(2, $result);
    }

    public function testGetTopTenDataWithFailedQuery(): void
    {
        $configMock = $this->createConfigWithLanguage();
        $statistics = new Statistics($configMock);

        $this->dbMock->method('query')->willReturn(false);

        $result = $statistics->getTopTenData(10);

        self::assertEmpty($result);
    }

    // =============================================
    // getTrendingData() Tests
    // =============================================

    public function testGetTrendingDataWithResults(): void
    {
        $configMock = $this->createConfigWithLanguage();
        $statistics = new Statistics($configMock);

        $resultMock = $this->createStub(\SQLite3Result::class);
        $this->dbMock->method('query')->willReturn($resultMock);
        $this->dbMock->method('escape')->willReturn('en');

        $row = $this->createFaqRow(1, 'en', 1, 'Trending FAQ', 'Content', '20260301', 500);
        $this->dbMock->method('fetchObject')->willReturnOnConsecutiveCalls($row, null);

        $result = $statistics->getTrendingData(10, 'en');

        self::assertCount(1, $result);
        self::assertArrayHasKey(1, $result);
        self::assertEquals('Trending FAQ', $result[1]['question']);
        self::assertEquals(500, $result[1]['visits']);
        self::assertArrayHasKey('url', $result[1]);
    }

    public function testGetTrendingDataWithNoResults(): void
    {
        $configMock = $this->createConfigWithLanguage();
        $statistics = new Statistics($configMock);

        $this->dbMock->method('query')->willReturn(false);

        $result = $statistics->getTrendingData(10);

        self::assertEmpty($result);
    }

    public function testGetTrendingDataWithGroupSupport(): void
    {
        $configMock = $this->createMock(Configuration::class);
        $configMock->method('getDb')->willReturn($this->dbMock);
        $configMock->method('get')->willReturn('medium');
        $configMock->method('getDefaultUrl')->willReturn('https://example.com/');
        $langMock = $this->createMock(Language::class);
        $langMock->method('getLanguage')->willReturn('en');
        $configMock->method('getLanguage')->willReturn($langMock);

        Strings::init();
        $statistics = new Statistics($configMock);
        $statistics->setUser(-1)->setGroups([-1]);

        $resultMock = $this->createStub(\SQLite3Result::class);
        $this->dbMock->method('query')->willReturn($resultMock);

        $row = $this->createFaqRow(1, 'en', 1, 'Q', 'A', '20260101', 50, -1, -1);
        $this->dbMock->method('fetchObject')->willReturnOnConsecutiveCalls($row, null);

        $result = $statistics->getTrendingData(10);

        self::assertCount(1, $result);
    }

    // =============================================
    // getTopVotedData() Tests
    // =============================================

    public function testGetTopVotedDataWithResults(): void
    {
        $configMock = $this->createConfigWithLanguage();
        $statistics = new Statistics($configMock);

        $resultMock = $this->createStub(\SQLite3Result::class);
        $this->dbMock->method('query')->willReturn($resultMock);
        $this->dbMock->method('escape')->willReturn('en');

        $row = $this->createFaqRow(1, 'en', 1, 'Voted FAQ', 'Content', '20260101', 0);
        $row->avg = 4.7;
        $row->user = 15;
        $this->dbMock->method('fetchObject')->willReturnOnConsecutiveCalls($row, null);

        $result = $statistics->getTopVotedData(10, 'en');

        self::assertCount(1, $result);
        self::assertEquals(4.7, $result[0]['avg']);
        self::assertEquals('Voted FAQ', $result[0]['question']);
        self::assertEquals(15, $result[0]['user']);
        self::assertArrayHasKey('url', $result[0]);
    }

    public function testGetTopVotedDataSkipsDuplicateIds(): void
    {
        $configMock = $this->createConfigWithLanguage();
        $statistics = new Statistics($configMock);

        $resultMock = $this->createStub(\SQLite3Result::class);
        $this->dbMock->method('query')->willReturn($resultMock);

        $row1 = $this->createFaqRow(1, 'en', 1, 'Q1', 'A1', '20260101', 0);
        $row1dup = $this->createFaqRow(1, 'en', 1, 'Q1', 'A1', '20260101', 0);
        $row2 = $this->createFaqRow(2, 'en', 1, 'Q2', 'A2', '20260102', 0);
        $this->dbMock->method('fetchObject')->willReturnOnConsecutiveCalls($row1, $row1dup, $row2, null);

        $result = $statistics->getTopVotedData(10);

        self::assertCount(2, $result);
    }

    public function testGetTopVotedDataRespectsCountLimit(): void
    {
        $configMock = $this->createConfigWithLanguage();
        $statistics = new Statistics($configMock);

        $resultMock = $this->createStub(\SQLite3Result::class);
        $this->dbMock->method('query')->willReturn($resultMock);

        $row1 = $this->createFaqRow(1, 'en', 1, 'Q1', 'A1', '20260101', 0);
        $row2 = $this->createFaqRow(2, 'en', 1, 'Q2', 'A2', '20260102', 0);
        $row3 = $this->createFaqRow(3, 'en', 1, 'Q3', 'A3', '20260103', 0);
        $this->dbMock->method('fetchObject')->willReturnOnConsecutiveCalls($row1, $row2, $row3, null);

        $result = $statistics->getTopVotedData(2);

        self::assertCount(2, $result);
    }

    // =============================================
    // getLatest() Tests
    // =============================================

    public function testGetLatestReturnsFormattedOutput(): void
    {
        $configMock = $this->createConfigWithLanguage();
        $statistics = new Statistics($configMock);

        $resultMock = $this->createStub(\SQLite3Result::class);
        $this->dbMock->method('query')->willReturn($resultMock);
        $this->dbMock->method('escape')->willReturn('en');

        $row = $this->createFaqRow(1, 'en', 1, 'Latest FAQ Question', 'Content', '20260301120000', 5);
        $this->dbMock->method('fetchObject')->willReturnOnConsecutiveCalls($row, null);

        $result = $statistics->getLatest();

        self::assertCount(1, $result);
        self::assertIsObject($result[0]);
        self::assertObjectHasProperty('url', $result[0]);
        self::assertObjectHasProperty('title', $result[0]);
        self::assertObjectHasProperty('preview', $result[0]);
        self::assertObjectHasProperty('date', $result[0]);
    }

    public function testGetLatestReturnsEmptyForNoData(): void
    {
        $configMock = $this->createConfigWithLanguage();
        $statistics = new Statistics($configMock);

        $this->dbMock->method('query')->willReturn(false);

        $result = $statistics->getLatest();

        self::assertEmpty($result);
    }

    // =============================================
    // getTopTen() Tests
    // =============================================

    public function testGetTopTenWithVisitsType(): void
    {
        $configMock = $this->createConfigWithLanguage();
        $statistics = new Statistics($configMock);

        $resultMock = $this->createStub(\SQLite3Result::class);
        $this->dbMock->method('query')->willReturn($resultMock);
        $this->dbMock->method('escape')->willReturn('en');

        // getTopTen('visits') calls getTopVotedData first, then getTopTenData
        // Both call fetchObject, so we need results for both calls
        $votedRow = $this->createFaqRow(1, 'en', 1, 'Voted FAQ', 'A', '20260101', 0);
        $visitsRow = $this->createFaqRow(2, 'en', 1, 'Top FAQ', 'Answer', '20260101', 100);
        $this->dbMock->method('fetchObject')->willReturnOnConsecutiveCalls($votedRow, null, $visitsRow, null);

        $result = $statistics->getTopTen('visits');

        self::assertCount(1, $result);
        self::assertObjectHasProperty('visits', $result[0]);
        self::assertObjectHasProperty('title', $result[0]);
        self::assertObjectHasProperty('url', $result[0]);
    }

    public function testGetTopTenWithVotedType(): void
    {
        $configMock = $this->createConfigWithLanguage();
        $statistics = new Statistics($configMock);

        $resultMock = $this->createStub(\SQLite3Result::class);
        $this->dbMock->method('query')->willReturn($resultMock);

        $row = $this->createFaqRow(1, 'en', 1, 'Voted FAQ', 'Answer', '20260101', 0);
        $row->avg = 4.5;
        $row->user = 10;
        $this->dbMock->method('fetchObject')->willReturnOnConsecutiveCalls($row, null);

        $result = $statistics->getTopTen('voted');

        self::assertCount(1, $result);
        self::assertObjectHasProperty('voted', $result[0]);
        self::assertStringContainsString('4.5', $result[0]->voted);
    }

    // =============================================
    // getTrending() Tests
    // =============================================

    public function testGetTrendingReturnsFormattedOutput(): void
    {
        $configMock = $this->createConfigWithLanguage();
        $statistics = new Statistics($configMock);

        $resultMock = $this->createStub(\SQLite3Result::class);
        $this->dbMock->method('query')->willReturn($resultMock);
        $this->dbMock->method('escape')->willReturn('en');

        $row = $this->createFaqRow(1, 'en', 1, 'Trending FAQ', 'Content', '20260301', 200);
        $this->dbMock->method('fetchObject')->willReturnOnConsecutiveCalls($row, null);

        $result = $statistics->getTrending();

        self::assertCount(1, $result);
        self::assertObjectHasProperty('url', $result[0]);
        self::assertObjectHasProperty('title', $result[0]);
        self::assertObjectHasProperty('visits', $result[0]);
        self::assertObjectHasProperty('date', $result[0]);
    }

    public function testGetTrendingReturnsEmptyForNoData(): void
    {
        $configMock = $this->createConfigWithLanguage();
        $statistics = new Statistics($configMock);

        $this->dbMock->method('query')->willReturn(false);

        $result = $statistics->getTrending();

        self::assertEmpty($result);
    }
}
