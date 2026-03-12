<?php

declare(strict_types=1);

namespace phpMyFAQ\Glossary;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Strings;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(GlossaryRepository::class)]
#[UsesClass(Database::class)]
#[UsesClass(Strings::class)]
final class GlossaryRepositoryTest extends TestCase
{
    public function testFetchAllReturnsEmptyArrayAndLogsErrorWhenQueryFails(): void
    {
        $databaseDriver = $this->createMock(DatabaseDriver::class);
        $databaseDriver->expects($this->once())->method('escape')->with('en')->willReturn('en');
        $databaseDriver->expects($this->once())->method('query')->willReturn(false);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with('Glossary fetchAll query failed', ['language' => 'en']);

        $repository = new GlossaryRepository($this->createConfiguration($databaseDriver), $logger);

        $this->assertSame([], $repository->fetchAll('en'));
    }

    public function testFetchReturnsEmptyArrayAndLogsWarningWhenQueryFails(): void
    {
        $databaseDriver = $this->createMock(DatabaseDriver::class);
        $databaseDriver->expects($this->once())->method('escape')->with('en')->willReturn('en');
        $databaseDriver->expects($this->once())->method('query')->willReturn(false);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('warning')
            ->with('Glossary fetch failed', ['id' => 42, 'language' => 'en']);

        $repository = new GlossaryRepository($this->createConfiguration($databaseDriver), $logger);

        $this->assertSame([], $repository->fetch(42, 'en'));
    }

    public function testCreateReturnsFalseAndLogsErrorWhenInsertFails(): void
    {
        $databaseDriver = $this->createMock(DatabaseDriver::class);
        $databaseDriver->method('escape')->willReturnCallback(static fn(string $value): string => $value);
        $databaseDriver->expects($this->once())->method('nextId')->willReturn(100);
        $databaseDriver->expects($this->once())->method('query')->willReturn(false);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Glossary create failed', ['language' => 'en', 'item' => 'Item']);

        $repository = new GlossaryRepository($this->createConfiguration($databaseDriver), $logger);

        $this->assertFalse($repository->create('en', 'Item', 'Definition'));
    }

    public function testUpdateReturnsFalseAndLogsErrorWhenUpdateFails(): void
    {
        $databaseDriver = $this->createMock(DatabaseDriver::class);
        $databaseDriver->method('escape')->willReturnCallback(static fn(string $value): string => $value);
        $databaseDriver->expects($this->once())->method('query')->willReturn(false);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Glossary update failed', ['id' => 100, 'language' => 'en']);

        $repository = new GlossaryRepository($this->createConfiguration($databaseDriver), $logger);

        $this->assertFalse($repository->update(100, 'en', 'Item', 'Definition'));
    }

    public function testDeleteReturnsFalseAndLogsWarningWhenDeleteFails(): void
    {
        $databaseDriver = $this->createMock(DatabaseDriver::class);
        $databaseDriver->expects($this->once())->method('escape')->with('en')->willReturn('en');
        $databaseDriver->expects($this->once())->method('query')->willReturn(false);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('warning')
            ->with('Glossary delete failed', ['id' => 100, 'language' => 'en']);

        $repository = new GlossaryRepository($this->createConfiguration($databaseDriver), $logger);

        $this->assertFalse($repository->delete(100, 'en'));
    }

    private function createConfiguration(DatabaseDriver $databaseDriver): Configuration
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getDb')->willReturn($databaseDriver);

        return $configuration;
    }
}
