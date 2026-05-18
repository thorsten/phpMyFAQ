<?php

declare(strict_types=1);

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DashboardLayoutTest extends TestCase
{
    private DashboardLayout $dashboardLayout;

    private DatabaseDriver $databaseMock;

    private ?string $originalPrefix = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalPrefix = Database::getTablePrefix();
        Database::setTablePrefix('');

        $this->databaseMock = $this->createMock(DatabaseDriver::class);
        $configurationMock = $this->createStub(Configuration::class);
        $configurationMock->method('getDb')->willReturn($this->databaseMock);

        $this->dashboardLayout = new DashboardLayout($configurationMock);
    }

    protected function tearDown(): void
    {
        Database::setTablePrefix($this->originalPrefix ?? '');

        parent::tearDown();
    }

    public function testGetReturnsEmptyArrayWhenNoRowExists(): void
    {
        $this->databaseMock->method('query')->willReturn(new \stdClass());
        $this->databaseMock->method('fetchObject')->willReturn(false);

        self::assertSame([], $this->dashboardLayout->get(42));
    }

    public function testGetReturnsDecodedConfig(): void
    {
        $row = new \stdClass();
        $row->config = '[{"key":"content-health","position":0,"visible":true}]';

        $this->databaseMock->method('query')->willReturn(new \stdClass());
        $this->databaseMock->method('fetchObject')->willReturn($row);

        $config = $this->dashboardLayout->get(42);

        self::assertCount(1, $config);
        self::assertSame('content-health', $config[0]['key']);
        self::assertTrue($config[0]['visible']);
    }

    public function testGetReturnsEmptyArrayOnInvalidJson(): void
    {
        $row = new \stdClass();
        $row->config = '{not valid json';

        $this->databaseMock->method('query')->willReturn(new \stdClass());
        $this->databaseMock->method('fetchObject')->willReturn($row);

        self::assertSame([], $this->dashboardLayout->get(42));
    }

    public function testSaveInsertsWhenNoRowExists(): void
    {
        $this->databaseMock->method('escape')->willReturnArgument(0);
        $this->databaseMock->method('fetchObject')->willReturn(null);

        $queries = [];
        $this->databaseMock
            ->method('query')
            ->willReturnCallback(function (string $query) use (&$queries): \stdClass {
                $queries[] = $query;
                return new \stdClass();
            });

        $result = $this->dashboardLayout->save(7, [
            ['key' => 'support', 'position' => 0, 'visible' => false],
        ]);

        self::assertTrue($result);
        self::assertCount(2, $queries);
        self::assertStringStartsWith('SELECT user_id FROM faqadmindashboard', $queries[0]);
        self::assertStringStartsWith('INSERT INTO faqadmindashboard', $queries[1]);
        self::assertStringContainsString('"key":"support"', $queries[1]);
    }

    public function testSaveUpdatesInPlaceWhenRowExists(): void
    {
        $this->databaseMock->method('escape')->willReturnArgument(0);
        $this->databaseMock->method('fetchObject')->willReturn((object) ['user_id' => 7]);

        $queries = [];
        $this->databaseMock
            ->method('query')
            ->willReturnCallback(function (string $query) use (&$queries): \stdClass {
                $queries[] = $query;
                return new \stdClass();
            });

        $result = $this->dashboardLayout->save(7, [
            ['key' => 'support', 'position' => 0, 'visible' => false],
        ]);

        self::assertTrue($result);
        self::assertCount(2, $queries);
        self::assertStringStartsWith('SELECT user_id FROM faqadmindashboard', $queries[0]);
        self::assertStringStartsWith('UPDATE faqadmindashboard', $queries[1]);
        self::assertStringContainsString('"key":"support"', $queries[1]);
        // No DELETE is issued — the existing row is replaced atomically by the UPDATE
        self::assertStringNotContainsString('DELETE', $queries[1]);
    }

    public function testResetDeletesTheRow(): void
    {
        $this->databaseMock
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('DELETE FROM faqadmindashboard WHERE user_id = 7'))
            ->willReturn(new \stdClass());

        self::assertTrue($this->dashboardLayout->reset(7));
    }
}
