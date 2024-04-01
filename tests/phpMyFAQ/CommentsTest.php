<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\Comment;
use phpMyFAQ\Entity\CommentType;
use PHPUnit\Framework\TestCase;

class CommentsTest extends TestCase
{
    private Comments $comments;

    private Configuration $configuration;

    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
        $language = new Language($this->configuration);
        $language->setLanguage(false, 'en');
        $this->configuration->setLanguage($language);

        $this->comments = new Comments($this->configuration);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->comments->delete(CommentType::FAQ, 1);
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
        $relation = new \phpMyFAQ\Category\Relation($this->configuration, $category);
        $relation->add([1], 1, 'en');

        $this->assertEquals([1 => 1], $this->comments->getNumberOfCommentsByCategory());

        // Cleanup
        $relation->delete(1, 'en');
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
            ->setDate((string)time())
            ->setHelped(true);

        return $comment;
    }
}
