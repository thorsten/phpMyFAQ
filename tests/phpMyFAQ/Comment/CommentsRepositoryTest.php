<?php

namespace phpMyFAQ\Comment;

use phpMyFAQ\Category;
use phpMyFAQ\Category\Relation;use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\Comment as CommentEntity;
use phpMyFAQ\Entity\CommentType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use phpMyFAQ\Language;

class CommentsRepositoryTest extends TestCase
{
    private Configuration $configuration;
    private CommentsRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
        $language = new Language($this->configuration, $this->createMock(Session::class));
        $language->setLanguage(false, 'en');
        $this->configuration->setLanguage($language);

        $this->repository = new CommentsRepository($this->configuration);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Best-effort cleanup: remove comment with id_comment = 1 if exists (first insert in clean DB)
        $this->repository->deleteByTypeAndId(CommentType::FAQ, 1);
        // Remove potential category relation
        $category = new Category($this->configuration);
        $category->setLanguage('en');
        $relation = new Relation($this->configuration, $category);
        $relation->delete(1, 'en');
    }

    private function makeComment(): CommentEntity
    {
        $comment = new CommentEntity();
        $comment
            ->setRecordId(1)
            ->setType(CommentType::FAQ)
            ->setUsername('testUser')
            ->setEmail('test@example.org')
            ->setComment('This is a test comment via repository')
            ->setDate((string) time())
            ->setHelped(true);

        return $comment;
    }

    public function testInsertAndFetchByReferenceIdAndType(): void
    {
        $this->assertTrue($this->repository->insert($this->makeComment()));

        $rows = $this->repository->fetchByReferenceIdAndType(1, CommentType::FAQ);
        $this->assertNotEmpty($rows);
        $row = $rows[0];
        $this->assertSame(1, (int) $row->id);
        $this->assertSame('testUser', $row->usr);
    }

    public function testDeleteByTypeAndId(): void
    {
        $this->repository->insert($this->makeComment());
        $rows = $this->repository->fetchByReferenceIdAndType(1, CommentType::FAQ);
        $this->assertNotEmpty($rows);
        $commentId = (int) $rows[0]->id_comment;

        $this->assertTrue($this->repository->deleteByTypeAndId(CommentType::FAQ, $commentId));
        $this->assertCount(0, $this->repository->fetchByReferenceIdAndType(1, CommentType::FAQ));
    }

    public function testCountByTypeGroupedByRecordId(): void
    {
        $this->repository->insert($this->makeComment());

        $rows = $this->repository->countByTypeGroupedByRecordId(CommentType::FAQ);
        $this->assertNotEmpty($rows);
        $this->assertSame(1, (int) $rows[0]->anz);
        $this->assertSame(1, (int) $rows[0]->id);
    }

    public function testCountByCategoryForFaq(): void
    {
        $this->repository->insert($this->makeComment());

        $category = new Category($this->configuration);
        $category->setLanguage('en');
        $relation = new Relation($this->configuration, $category);
        $relation->add([1], 1, 'en');

        $rows = $this->repository->countByCategoryForFaq();
        $this->assertNotEmpty($rows);
        $this->assertSame(1, (int) $rows[0]->number);
        $this->assertSame(1, (int) $rows[0]->category_id);

        // cleanup relation
        $relation->delete(1, 'en');
    }

    public function testFetchAllWithCategories(): void
    {
        $this->repository->insert($this->makeComment());

        $category = new Category($this->configuration);
        $category->setLanguage('en');
        $relation = new Relation($this->configuration, $category);
        $relation->add([1], 1, 'en');

        $rows = $this->repository->fetchAllWithCategories();
        $this->assertNotEmpty($rows);
        $row = $rows[0];
        $this->assertSame(1, (int) $row->record_id);
        $this->assertSame('testUser', $row->username);
        $this->assertSame(1, (int) $row->category_id);

        // cleanup relation
        $relation->delete(1, 'en');
    }

    public function testIsCommentAllowed(): void
    {
        $prefix = Database::getTablePrefix();
        // Ensure a faqdata row for id=1, lang='en' exists
        $this->configuration->getDb()->query(
            "INSERT OR IGNORE INTO {$prefix}faqdata (id, lang, solution_id, revision_id, active, sticky, thema, author, email, comment, updated, date_start, date_end) VALUES (1, 'en', 1, 0, 'yes', 0, 'Test', 'Admin', 'admin@example.org', 'n', '20200101000000', '00000000000000', '99991231235959')"
        );
        // Set comment flag to 'y'
        $this->configuration->getDb()->query(sprintf(
            "UPDATE %sfaqdata SET comment = 'y' WHERE id = 1 AND lang = 'en'",
            $prefix
        ));

        $this->assertTrue($this->repository->isCommentAllowed(1, 'en', CommentType::FAQ));
    }
}

