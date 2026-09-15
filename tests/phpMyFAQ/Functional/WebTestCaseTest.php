<?php

declare(strict_types=1);

namespace phpMyFAQ\Functional;

use PHPUnit\Framework\Attributes\CoversNothing;
use ReflectionClass;

#[CoversNothing]
final class WebTestCaseTest extends WebTestCase
{
    public function testCreatingAnotherClientRemovesThePreviousDatabaseCopy(): void
    {
        self::createClient();
        $firstDatabase = self::currentDatabasePath();
        self::assertFileExists($firstDatabase);

        self::createClient('api');
        $secondDatabase = self::currentDatabasePath();

        self::assertNotSame($firstDatabase, $secondDatabase);
        self::assertFileDoesNotExist($firstDatabase);
        self::assertFileExists($secondDatabase);
    }

    public function testTearDownRemovesTheDatabaseCopy(): void
    {
        self::createClient();
        $database = self::currentDatabasePath();
        self::assertFileExists($database);

        $this->tearDown();

        self::assertFileDoesNotExist($database);
        self::assertNull(self::databasePathProperty()->getValue());
    }

    private static function currentDatabasePath(): string
    {
        $databasePath = self::databasePathProperty()->getValue();
        self::assertIsString($databasePath);

        return $databasePath;
    }

    private static function databasePathProperty(): \ReflectionProperty
    {
        return new ReflectionClass(WebTestCase::class)->getProperty('databasePath');
    }
}
