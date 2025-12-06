<?php

namespace phpMyFAQ\Faq;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Translation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Class StatisticsTest
 *
 * @package phpMyFAQ\Faq
 */
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
}
