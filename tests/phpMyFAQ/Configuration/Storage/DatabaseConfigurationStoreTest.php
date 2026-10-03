<?php

declare(strict_types=1);

namespace phpMyFAQ\Configuration\Storage;

use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(DatabaseConfigurationStore::class)]
#[UsesClass(Database::class)]
final class DatabaseConfigurationStoreTest extends TestCase
{
    private DatabaseDriver&MockObject $driver;

    private DatabaseConfigurationStore $store;

    protected function setUp(): void
    {
        Database::setTablePrefix('pmf_');

        $this->driver = $this->createMock(DatabaseDriver::class);
        $this->driver->method('escape')->willReturnCallback(static fn(string $value): string => addslashes($value));

        $this->store = new DatabaseConfigurationStore($this->driver);
    }

    protected function tearDown(): void
    {
        Database::setTablePrefix('');
    }

    public function testUpdateConfigValueTrimsEscapesAndUsesTablePrefix(): void
    {
        $this->driver
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains("UPDATE pmf_faqconfig SET config_value = 'O\\'Reilly' WHERE config_name = 'main.title'"))
            ->willReturn(true);

        $this->assertTrue($this->store->updateConfigValue('  main.title ', " O'Reilly "));
    }

    public function testUpdateConfigValueReportsFailure(): void
    {
        $this->driver->method('query')->willReturn(false);

        $this->assertFalse($this->store->updateConfigValue('main.title', 'x'));
    }

    public function testCustomTableNameIsUsed(): void
    {
        $store = new DatabaseConfigurationStore($this->driver, 'tenantconfig');

        $this->driver
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('FROM pmf_tenantconfig'))
            ->willReturn('result');
        $this->driver->method('fetchAll')->willReturn([]);

        $this->assertSame([], $store->fetchAll());
    }

    public function testFetchAllReturnsRows(): void
    {
        $rows = [(object) ['config_name' => 'a', 'config_value' => '1']];

        $this->driver
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('SELECT config_name, config_value FROM pmf_faqconfig'))
            ->willReturn('result');
        $this->driver->expects($this->once())->method('fetchAll')->with('result')->willReturn($rows);

        $this->assertSame($rows, $this->store->fetchAll());
    }

    public function testFetchAllReturnsEmptyArrayWhenDriverReturnsNull(): void
    {
        $this->driver->method('query')->willReturn('result');
        $this->driver->method('fetchAll')->willReturn(null);

        $this->assertSame([], $this->store->fetchAll());
    }

    public function testInsertBuildsInsertStatement(): void
    {
        $this->driver
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains("INSERT INTO pmf_faqconfig (config_name, config_value) VALUES ('new.key', 'value')"))
            ->willReturn(true);

        $this->assertTrue($this->store->insert(' new.key', 'value '));
    }

    public function testDeleteBuildsDeleteStatement(): void
    {
        $this->driver
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains("DELETE FROM pmf_faqconfig WHERE config_name = 'old.key'"))
            ->willReturn(true);

        $this->assertTrue($this->store->delete('old.key'));
    }

    public function testRenameKeyBuildsUpdateStatement(): void
    {
        $this->driver
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains("UPDATE pmf_faqconfig SET config_name = 'new.key' WHERE config_name = 'old.key'"))
            ->willReturn(true);

        $this->assertTrue($this->store->renameKey('old.key', 'new.key'));
    }

    public function testFetchValueReturnsStringValue(): void
    {
        $this->driver
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains("WHERE config_name = 'main.title'"))
            ->willReturn('result');
        $this->driver->expects($this->once())->method('fetchObject')->with('result')->willReturn((object) ['config_value' => 42]);

        $this->assertSame('42', $this->store->fetchValue('main.title'));
    }

    public function testFetchValueReturnsNullForMissingRow(): void
    {
        $this->driver->method('query')->willReturn('result');
        $this->driver->method('fetchObject')->willReturn(false);

        $this->assertNull($this->store->fetchValue('missing'));
    }

    public function testFetchValueReturnsNullWhenRowLacksColumn(): void
    {
        $this->driver->method('query')->willReturn('result');
        $this->driver->method('fetchObject')->willReturn((object) ['other' => 'x']);

        $this->assertNull($this->store->fetchValue('missing'));
    }

    public function testFetchValuesReturnsEmptyArrayWithoutQueryForNoNames(): void
    {
        $this->driver->expects($this->never())->method('query');

        $this->assertSame([], $this->store->fetchValues([]));
    }

    public function testFetchValuesDeduplicatesTrimsAndFillsMissingWithNull(): void
    {
        $this->driver
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains("WHERE config_name IN ('a', 'b', 'c')"))
            ->willReturn('result');
        $this->driver->method('fetchAll')->willReturn([
            (object) ['config_name' => 'a', 'config_value' => '1'],
            (object) ['config_name' => 'c', 'config_value' => 3],
            (object) ['config_name' => 'ignored'], // no config_value column
        ]);

        $this->assertSame(['a' => '1', 'b' => null, 'c' => '3'], $this->store->fetchValues(['a', ' b', 'a ', 'c']));
    }

    public function testFetchValuesReturnsAllNullWhenDriverReturnsNoRows(): void
    {
        $this->driver->method('query')->willReturn('result');
        $this->driver->method('fetchAll')->willReturn(null);

        $this->assertSame(['a' => null, 'b' => null], $this->store->fetchValues(['a', 'b']));
    }
}
