<?php

declare(strict_types=1);

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\TestDatabaseTrait;
use PHPUnit\Framework\TestCase;

/**
 * Runs the rating statistics query against the SQLite test database: faqvoting.vote and
 * faqvoting.usr are integer columns, and SQLite (like PostgreSQL and SQL Server) divides two
 * integers as integers, so the average lost its decimals.
 */
class RatingStatisticsSqliteTest extends TestCase
{
    use TestDatabaseTrait;

    private const int FAQ_ID = 987_655;

    public function testGetAllKeepsTheDecimalsOfTheAverage(): void
    {
        $db = $this->connectToTestDatabaseCopy(new Sqlite3());
        Database::factory('sqlite3');
        Database::setTablePrefix('');

        $db->query('DELETE FROM faqvoting');
        $db->query(sprintf(
            'INSERT INTO faqdata (id, lang, solution_id, revision_id, status, sticky, thema, author, email, updated) '
            . "VALUES (%d, 'en', 4321, 0, 'published', 0, 'Question', 'Author', 'author@example.org', '20261009120000')",
            self::FAQ_ID,
        ));
        $db->query(sprintf("INSERT INTO faqvoting VALUES (1, %d, 9, 2, '1700000000', '127.0.0.1')", self::FAQ_ID));

        $ratings = new RatingStatistics(new Configuration($db))->getAll();

        static::assertCount(1, $ratings);
        static::assertEqualsWithDelta(4.5, (float) $ratings[0]['number'], 0.0001);
        static::assertSame(2, (int) $ratings[0]['user']);
    }
}
