<?php

declare(strict_types=1);

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReportRepository::class)]
#[UsesNamespace('phpMyFAQ')]
final class ReportRepositoryTest extends TestCase
{
    private string $databaseFile;

    private Sqlite3 $dbHandle;

    private ReportRepository $repository;

    protected function setUp(): void
    {
        $this->databaseFile = tempnam(sys_get_temp_dir(), 'phpmyfaq-report-repository-');
        copy(PMF_TEST_DIR . '/test.db', $this->databaseFile);

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databaseFile, '', '');

        $this->repository = new ReportRepository(new Configuration($this->dbHandle));
    }

    protected function tearDown(): void
    {
        if (file_exists($this->databaseFile)) {
            @unlink($this->databaseFile);
        }
    }

    private function insertFaq(int $id, string $lang, string $question, string $author, int $sticky = 0): void
    {
        $this->dbHandle->query(sprintf(
            "INSERT INTO %sfaqdata (id, lang, solution_id, revision_id, status, sticky, keywords, thema, content, author, email, "
            . "comment, updated, date_start, date_end, notes) VALUES (%d, '%s', %d, 0, 'published', %d, '', '%s', 'content', '%s', "
            . "'author@example.org', 'y', '20250101120000', '00000000000000', '99991231235959', '')",
            Database::getTablePrefix(),
            $id,
            $lang,
            1000 + $id,
            $sticky,
            $this->dbHandle->escape($question),
            $this->dbHandle->escape($author),
        ));
    }

    private function insertCategory(int $id, string $lang, int $parentId, string $name): void
    {
        $this->dbHandle->query(sprintf(
            "INSERT INTO %sfaqcategories (id, lang, parent_id, name, description, user_id, group_id, active, show_home, image) "
            . "VALUES (%d, '%s', %d, '%s', '', 1, -1, 1, 1, '')",
            Database::getTablePrefix(),
            $id,
            $lang,
            $parentId,
            $this->dbHandle->escape($name),
        ));
    }

    private function relate(int $categoryId, int $faqId, string $lang): void
    {
        $this->dbHandle->query(sprintf(
            "INSERT INTO %sfaqcategoryrelations (category_id, category_lang, record_id, record_lang) VALUES (%d, '%s', %d, '%s')",
            Database::getTablePrefix(),
            $categoryId,
            $lang,
            $faqId,
            $lang,
        ));
    }

    private function insertVisits(int $faqId, string $lang, int $visits): void
    {
        $this->dbHandle->query(sprintf(
            "INSERT INTO %sfaqvisits (id, lang, visits, last_visit) VALUES (%d, '%s', %d, %d)",
            Database::getTablePrefix(),
            $faqId,
            $lang,
            $visits,
            time(),
        ));
    }

    public function testReturnsEmptyArrayWithoutFaqs(): void
    {
        $this->assertSame([], $this->repository->fetchAllReportData());
    }

    public function testReturnsOneRowPerFaqOrderedById(): void
    {
        $this->insertFaq(2, 'en', 'Second', 'Bob');
        $this->insertFaq(1, 'en', 'First', 'Alice', sticky: 1);

        $rows = $this->repository->fetchAllReportData();

        $this->assertCount(2, $rows);
        $this->assertSame([1, 2], array_map(static fn(\stdClass $row): int => (int) $row->id, $rows));
        $this->assertSame('First', $rows[0]->question);
        $this->assertSame('Alice', $rows[0]->original_author);
        $this->assertSame(1, (int) $rows[0]->sticky);
        $this->assertSame('en', $rows[0]->lang);
        $this->assertSame('20250101120000', $rows[0]->updated);
    }

    public function testJoinsCategoryAndVisitInformation(): void
    {
        $this->insertCategory(10, 'en', 0, 'Root');
        $this->insertCategory(11, 'en', 10, 'Child');
        $this->insertFaq(1, 'en', 'Categorised', 'Alice');
        $this->relate(11, 1, 'en');
        $this->insertVisits(1, 'en', 17);

        $rows = $this->repository->fetchAllReportData();

        $this->assertCount(1, $rows);
        $this->assertSame(11, (int) $rows[0]->category_id);
        $this->assertSame('Child', $rows[0]->category_name);
        $this->assertSame(10, (int) $rows[0]->parent_id);
        $this->assertSame(17, (int) $rows[0]->visits);
    }

    public function testFaqWithoutRelationsHasNullJoinColumns(): void
    {
        $this->insertFaq(1, 'en', 'Orphan', 'Alice');

        $row = $this->repository->fetchAllReportData()[0];

        $this->assertNull($row->category_id);
        $this->assertNull($row->category_name);
        $this->assertNull($row->visits);
        $this->assertNull($row->last_author);
    }

    public function testFaqInSeveralCategoriesAppearsOncePerCategory(): void
    {
        $this->insertCategory(10, 'en', 0, 'A');
        $this->insertCategory(11, 'en', 0, 'B');
        $this->insertFaq(1, 'en', 'Shared', 'Alice');
        $this->relate(10, 1, 'en');
        $this->relate(11, 1, 'en');

        $rows = $this->repository->fetchAllReportData();

        $this->assertCount(2, $rows);
        $this->assertEqualsCanonicalizing(['A', 'B'], array_column($rows, 'category_name'));
    }
}
