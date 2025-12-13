<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\Comment;
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Category\Relation;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class CommentsTest extends TestCase
{
    private Comments $comments;

    private Configuration $configuration;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
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
        parent::tearDown();

        $this->comments->delete(CommentType::FAQ, 1);
        // Cleanup any category relations created for this record during tests
        $category = new Category($this->configuration);
        $category->setLanguage('en');
        $relation = new Relation($this->configuration, $category);
        $relation->deleteByFAQ(1, 'en');
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
}
