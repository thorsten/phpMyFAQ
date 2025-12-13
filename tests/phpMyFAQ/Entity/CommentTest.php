<?php

/**
 * Test case for Comment Entity
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 */

namespace phpMyFAQ\Entity;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

/**
 * Class CommentTest
 */
#[AllowMockObjectsWithoutExpectations]
class CommentTest extends TestCase
{
    private Comment $comment;

    protected function setUp(): void
    {
        $this->comment = new Comment();
    }

    /**
     * Test Comment entity instantiation
     */
    public function testCommentInstantiation(): void
    {
        $this->assertInstanceOf(Comment::class, $this->comment);
    }

    /**
     * Test id getter and setter
     */
    public function testIdGetterAndSetter(): void
    {
        $id = 123;
        $result = $this->comment->setId($id);

        $this->assertInstanceOf(Comment::class, $result); // Test fluent interface
        $this->assertEquals($id, $this->comment->getId());
    }

    /**
     * Test recordId getter and setter
     */
    public function testRecordIdGetterAndSetter(): void
    {
        $recordId = 456;
        $result = $this->comment->setRecordId($recordId);

        $this->assertInstanceOf(Comment::class, $result);
        $this->assertEquals($recordId, $this->comment->getRecordId());
    }

    /**
     * Test categoryId getter and setter
     */
    public function testCategoryIdGetterAndSetter(): void
    {
        $categoryId = 789;
        $result = $this->comment->setCategoryId($categoryId);

        $this->assertInstanceOf(Comment::class, $result);
        $this->assertEquals($categoryId, $this->comment->getCategoryId());
    }

    /**
     * Test type getter and setter
     */
    public function testTypeGetterAndSetter(): void
    {
        $type = 'faq';
        $result = $this->comment->setType($type);

        $this->assertInstanceOf(Comment::class, $result);
        $this->assertEquals($type, $this->comment->getType());
    }

    /**
     * Test username getter and setter
     */
    public function testUsernameGetterAndSetter(): void
    {
        $username = 'john_doe';
        $result = $this->comment->setUsername($username);

        $this->assertInstanceOf(Comment::class, $result);
        $this->assertEquals($username, $this->comment->getUsername());
    }

    /**
     * Test email getter and setter
     */
    public function testEmailGetterAndSetter(): void
    {
        $email = 'john@example.com';
        $result = $this->comment->setEmail($email);

        $this->assertInstanceOf(Comment::class, $result);
        $this->assertEquals($email, $this->comment->getEmail());
    }

    /**
     * Test comment getter and setter
     */
    public function testCommentGetterAndSetter(): void
    {
        $commentText = 'This is a test comment with useful information.';
        $result = $this->comment->setComment($commentText);

        $this->assertInstanceOf(Comment::class, $result);
        $this->assertEquals($commentText, $this->comment->getComment());
    }

    /**
     * Test date getter and setter
     */
    public function testDateGetterAndSetter(): void
    {
        $date = '2025-08-09 15:30:00';
        $result = $this->comment->setDate($date);

        $this->assertInstanceOf(Comment::class, $result);
        $this->assertEquals($date, $this->comment->getDate());
    }

    /**
     * Test helped getter and setter
     */
    public function testHelpedGetterAndSetter(): void
    {
        // Test true value
        $result = $this->comment->setHelped(true);
        $this->assertInstanceOf(Comment::class, $result);
        $this->assertTrue($this->comment->hasHelped());

        // Test false value
        $this->comment->setHelped(false);
        $this->assertFalse($this->comment->hasHelped());

        // Note: setHelped requires bool, so we can't test null directly
        // This tests the nullable property behavior through hasHelped()
    }

    /**
     * Test fluent interface with method chaining
     */
    public function testFluentInterface(): void
    {
        $result = $this->comment
            ->setId(1)
            ->setRecordId(100)
            ->setCategoryId(5)
            ->setType('news')
            ->setUsername('testuser')
            ->setEmail('test@example.com')
            ->setComment('Test comment text')
            ->setDate('2025-08-09 12:00:00')
            ->setHelped(true);

        $this->assertInstanceOf(Comment::class, $result);
        $this->assertEquals(1, $this->comment->getId());
        $this->assertEquals(100, $this->comment->getRecordId());
        $this->assertEquals(5, $this->comment->getCategoryId());
        $this->assertEquals('news', $this->comment->getType());
        $this->assertEquals('testuser', $this->comment->getUsername());
        $this->assertEquals('test@example.com', $this->comment->getEmail());
        $this->assertEquals('Test comment text', $this->comment->getComment());
        $this->assertEquals('2025-08-09 12:00:00', $this->comment->getDate());
        $this->assertTrue($this->comment->hasHelped());
    }

    /**
     * Test comment with various email formats
     */
    public function testCommentWithVariousEmailFormats(): void
    {
        $emails = [
            'simple@example.com',
            'test.email+tag@domain.co.uk',
            'user123@sub.domain.org',
            'test@localhost',
            'long.email.address@very.long.domain.name.com'
        ];

        foreach ($emails as $email) {
            $this->comment->setEmail($email);
            $this->assertEquals($email, $this->comment->getEmail());
        }
    }

    /**
     * Test comment with different types
     */
    public function testCommentWithDifferentTypes(): void
    {
        $types = ['faq', 'news', 'article', 'question'];

        foreach ($types as $type) {
            $this->comment->setType($type);
            $this->assertEquals($type, $this->comment->getType());
        }
    }

    /**
     * Test comment with various usernames
     */
    public function testCommentWithVariousUsernames(): void
    {
        $usernames = [
            'admin',
            'john_doe',
            'user123',
            'test-user',
            'CamelCaseUser',
            'user.with.dots'
        ];

        foreach ($usernames as $username) {
            $this->comment->setUsername($username);
            $this->assertEquals($username, $this->comment->getUsername());
        }
    }

    /**
     * Test comment with long text
     */
    public function testCommentWithLongText(): void
    {
        $longComment = str_repeat('This is a very long comment text. ', 100);
        $this->comment->setComment($longComment);
        $this->assertEquals($longComment, $this->comment->getComment());
    }

    /**
     * Test comment with special characters
     */
    public function testCommentWithSpecialCharacters(): void
    {
        $specialComment = 'Comment with special chars: äöü ßñ 中文 🙂 & < > " \'';
        $this->comment->setComment($specialComment);
        $this->assertEquals($specialComment, $this->comment->getComment());
    }

    /**
     * Test comment with HTML content
     */
    public function testCommentWithHtmlContent(): void
    {
        $htmlComment = 'Comment with <strong>HTML</strong> and <em>formatting</em>.';
        $this->comment->setComment($htmlComment);
        $this->assertEquals($htmlComment, $this->comment->getComment());
    }

    /**
     * Test date with different formats
     */
    public function testDateWithDifferentFormats(): void
    {
        $dates = [
            '2025-08-09 15:30:00',
            '2025-12-31 23:59:59',
            '1970-01-01 00:00:00',
            '2025-02-29 12:00:00' // Leap year date
        ];

        foreach ($dates as $date) {
            $this->comment->setDate($date);
            $this->assertEquals($date, $this->comment->getDate());
        }
    }

    /**
     * Test zero and negative values for numeric fields
     */
    public function testZeroAndNegativeValues(): void
    {
        $this->comment
            ->setId(0)
            ->setRecordId(-1)
            ->setCategoryId(0);

        $this->assertEquals(0, $this->comment->getId());
        $this->assertEquals(-1, $this->comment->getRecordId());
        $this->assertEquals(0, $this->comment->getCategoryId());
    }

    /**
     * Test empty string values
     */
    public function testEmptyStringValues(): void
    {
        $this->comment
            ->setType('')
            ->setUsername('')
            ->setEmail('')
            ->setComment('')
            ->setDate('');

        $this->assertEquals('', $this->comment->getType());
        $this->assertEquals('', $this->comment->getUsername());
        $this->assertEquals('', $this->comment->getEmail());
        $this->assertEquals('', $this->comment->getComment());
        $this->assertEquals('', $this->comment->getDate());
    }

    /**
     * Test complete comment scenario
     */
    public function testCompleteCommentScenario(): void
    {
        $commentData = [
            'id' => 42,
            'recordId' => 123,
            'categoryId' => 5,
            'type' => 'faq',
            'username' => 'jane_doe',
            'email' => 'jane@example.com',
            'comment' => 'This FAQ was very helpful! Thank you for the detailed explanation.',
            'date' => '2025-08-09 16:45:30',
            'helped' => true
        ];

        $this->comment
            ->setId($commentData['id'])
            ->setRecordId($commentData['recordId'])
            ->setCategoryId($commentData['categoryId'])
            ->setType($commentData['type'])
            ->setUsername($commentData['username'])
            ->setEmail($commentData['email'])
            ->setComment($commentData['comment'])
            ->setDate($commentData['date'])
            ->setHelped($commentData['helped']);

        // Verify all values
        $this->assertEquals($commentData['id'], $this->comment->getId());
        $this->assertEquals($commentData['recordId'], $this->comment->getRecordId());
        $this->assertEquals($commentData['categoryId'], $this->comment->getCategoryId());
        $this->assertEquals($commentData['type'], $this->comment->getType());
        $this->assertEquals($commentData['username'], $this->comment->getUsername());
        $this->assertEquals($commentData['email'], $this->comment->getEmail());
        $this->assertEquals($commentData['comment'], $this->comment->getComment());
        $this->assertEquals($commentData['date'], $this->comment->getDate());
        $this->assertEquals($commentData['helped'], $this->comment->hasHelped());
    }

    /**
     * Test object independence
     */
    public function testObjectIndependence(): void
    {
        $comment1 = new Comment();
        $comment1->setId(1)->setUsername('user1');

        $comment2 = new Comment();
        $comment2->setId(2)->setUsername('user2');

        // Verify independence
        $this->assertEquals(1, $comment1->getId());
        $this->assertEquals('user1', $comment1->getUsername());
        $this->assertEquals(2, $comment2->getId());
        $this->assertEquals('user2', $comment2->getUsername());
    }
}
