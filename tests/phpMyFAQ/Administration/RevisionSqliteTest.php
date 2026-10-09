<?php

declare(strict_types=1);

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;

/**
 * Runs Revision against the SQLite test database so the INSERT ... SELECT that copies a FAQ
 * record into the revisions table is checked against the real table layout. A column mismatch
 * between faqdata and faqdata_revisions surfaces as an HTTP 500 when saving a FAQ with
 * "Create new revision?" set to yes, which is what GitHub issue #3385 reported.
 */
class RevisionSqliteTest extends TestCase
{
    private const int FAQ_ID = 987_654;

    private Sqlite3 $db;

    private Revision $revision;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = new Sqlite3();
        $this->db->connect(PMF_TEST_DIR . '/test.db', '', '');

        $this->revision = new Revision(new Configuration($this->db));

        $this->cleanUp();
        $this->db->query(sprintf(
            "INSERT INTO faqdata (id, lang, solution_id, revision_id, active, sticky, keywords, thema, content, author,
                email, comment, updated, date_start, date_end, created, notes, sticky_order)
            VALUES (%d, 'en', 1234, 0, 'yes', 1, 'keyword', 'Question', 'Answer', 'Author', 'author@example.org',
                'n', '20261009120000', '00000000000000', '99991231235959', '2026-10-09 12:00:00', 'Notes', 3)",
            self::FAQ_ID,
        ));
    }

    protected function tearDown(): void
    {
        $this->cleanUp();

        parent::tearDown();
    }

    public function testCreateCopiesTheCurrentRecordAsNextRevision(): void
    {
        static::assertTrue($this->revision->create(self::FAQ_ID, 'en'));

        $result = $this->db->query(sprintf(
            "SELECT * FROM faqdata_revisions WHERE id = %d AND lang = 'en'",
            self::FAQ_ID,
        ));
        $rows = $this->db->fetchAll($result);

        static::assertCount(1, $rows);
        $row = $rows[0];
        static::assertSame(1, (int) $row->revision_id);
        static::assertSame(1234, (int) $row->solution_id);
        static::assertSame('Question', $row->thema);
        static::assertSame('Answer', $row->content);
        static::assertSame('Author', $row->author);
        static::assertSame('n', $row->comment);
        static::assertSame('Notes', $row->notes);
        static::assertSame(3, (int) $row->sticky_order);
    }

    public function testCreateCanBeRepeatedOnceTheRecordAdvanced(): void
    {
        static::assertTrue($this->revision->create(self::FAQ_ID, 'en'));
        $this->db->query(sprintf('UPDATE faqdata SET revision_id = 1 WHERE id = %d', self::FAQ_ID));
        static::assertTrue($this->revision->create(self::FAQ_ID, 'en'));

        $revisions = $this->revision->get(self::FAQ_ID, 'en', 'Author');

        static::assertSame([1, 2], array_map(static fn(array $r): int => (int) $r['revision_id'], $revisions));
    }

    private function cleanUp(): void
    {
        $this->db->query(sprintf('DELETE FROM faqdata WHERE id = %d', self::FAQ_ID));
        $this->db->query(sprintf('DELETE FROM faqdata_revisions WHERE id = %d', self::FAQ_ID));
    }
}
