<?php

namespace phpMyFAQ;

use phpMyFAQ\Category\Relation;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\Comment;
use phpMyFAQ\Entity\CommentType;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class CommentsTest extends TestCase
{
    private Comments $comments;

    private Configuration $configuration;
    private Sqlite3 $dbHandle;
    private string $databaseFile;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseFile = tempnam(sys_get_temp_dir(), 'phpmyfaq-comments-test-');
        copy(PMF_TEST_DIR . '/test.db', $this->databaseFile);

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databaseFile, '', '');
        $this->configuration = new Configuration($this->dbHandle);
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);

        // Ensure clean category relations for record 1 / en to avoid leakage from other tests
        $category = new Category($this->configuration);
        $category->setLanguage('en');
        $relation = new Relation($this->configuration, $category);
        $relation->deleteByFAQ(1, 'en');

        $this->comments = new Comments($this->configuration);
    }

    protected function tearDown(): void
    {
        $this->comments->delete(CommentType::FAQ, 1);
        // Cleanup any category relations created for this record during tests
        $category = new Category($this->configuration);
        $category->setLanguage('en');
        $relation = new Relation($this->configuration, $category);
        $relation->deleteByFAQ(1, 'en');

        $this->dbHandle->close();
        @unlink($this->databaseFile);

        parent::tearDown();
    }

    public function testCreate(): void
    {
        $comment = $this->getComment();

        $this->assertTrue($this->comments->create($comment));
    }

    public function testGetCommentsData(): void
    {
        $comment = $this->getComment();
        $this->comments->create($comment);

        $this->assertCount(1, $this->comments->getCommentsData(1, CommentType::FAQ));
    }

    public function testDelete(): void
    {
        $comment = $this->getComment();
        $this->comments->create($comment);

        $this->assertTrue($this->comments->delete(CommentType::FAQ, 1));
    }

    public function testGetNumberOfComments(): void
    {
        $comment = $this->getComment();
        $this->comments->create($comment);

        $this->assertSame([1 => 1], $this->comments->getNumberOfComments());
    }

    public function testGetCommentsDataPaginatedReturnsSortedSlice(): void
    {
        $this->createCommentWithData(1, 'Alpha comment', 'alpha@example.org', 'alphaUser', 100);
        $this->createCommentWithData(1, 'Beta comment', 'beta@example.org', 'betaUser', 200);
        $this->createCommentWithData(1, 'Gamma comment', 'gamma@example.org', 'gammaUser', 300);

        $comments = $this->comments->getCommentsDataPaginated(1, CommentType::FAQ, 2, 1, 'datum', 'DESC');

        $this->assertCount(2, $comments);
        $this->assertSame('Beta comment', $comments[0]->getComment());
        $this->assertSame('Alpha comment', $comments[1]->getComment());
        $this->assertSame('faq', $comments[0]->getType());
    }

    public function testGetCommentsDataPaginatedFallsBackToSafeSortOptions(): void
    {
        $this->createCommentWithData(1, 'First comment', 'first@example.org', 'firstUser', 100);
        $this->createCommentWithData(1, 'Second comment', 'second@example.org', 'secondUser', 200);

        $comments = $this->comments->getCommentsDataPaginated(1, CommentType::FAQ, 10, 0, 'invalid_field', 'sideways');

        $this->assertCount(2, $comments);
        $this->assertSame('First comment', $comments[0]->getComment());
        $this->assertSame('Second comment', $comments[1]->getComment());
    }

    public function testCountCommentsReturnsTotalForReferenceAndType(): void
    {
        $this->createCommentWithData(1, 'FAQ comment', 'faq@example.org', 'faqUser', 100, CommentType::FAQ);
        $this->createCommentWithData(1, 'News comment', 'news@example.org', 'newsUser', 200, CommentType::NEWS);
        $this->createCommentWithData(2, 'Other FAQ comment', 'other@example.org', 'otherUser', 300, CommentType::FAQ);

        $this->assertSame(1, $this->comments->countComments(1, CommentType::FAQ));
        $this->assertSame(1, $this->comments->countComments(1, CommentType::NEWS));
        $this->assertSame(1, $this->comments->countComments(2, CommentType::FAQ));
        $this->assertSame(0, $this->comments->countComments(999, CommentType::FAQ));
    }

    public function testGetNumberOfCommentsByCategory(): void
    {
        $comment = $this->getComment();
        $this->comments->create($comment);

        $category = new Category($this->configuration);
        $category->setLanguage('en');
        $relation = new Relation($this->configuration, $category);
        $relation->add([1], 1, 'en');

        $this->assertEquals([1 => 1], $this->comments->getNumberOfCommentsByCategory());

        // Cleanup
        $relation->deleteByFAQ(1, 'en');
    }

    public function testGetAllComments(): void
    {
        $comment = $this->getComment();
        $this->comments->create($comment);

        $this->assertCount(1, $this->comments->getAllComments());
    }

    private function getComment(): Comment
    {
        $comment = new Comment();
        $comment
            ->setRecordId(1)
            ->setType(CommentType::FAQ)
            ->setUsername('testUser')
            ->setEmail('test@example.org')
            ->setComment('This is a test comment')
            ->setDate(time())
            ->setHelped(true);

        return $comment;
    }

    private function createCommentWithData(
        int $recordId,
        string $commentText,
        string $email,
        string $username,
        int $date,
        string $type = CommentType::FAQ,
    ): void {
        $comment = new Comment();
        $comment
            ->setRecordId($recordId)
            ->setType($type)
            ->setUsername($username)
            ->setEmail($email)
            ->setComment($commentText)
            ->setDate($date)
            ->setHelped(true);

        $this->assertTrue($this->comments->create($comment));
    }
}
