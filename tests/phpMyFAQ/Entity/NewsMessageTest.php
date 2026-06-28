<?php

namespace phpMyFAQ\Entity;

use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * Class NewsMessageTest
 */
class NewsMessageTest extends TestCase
{
    private NewsMessage $newsMessage;

    protected function setUp(): void
    {
        $this->newsMessage = new NewsMessage();
    }

    /**
     * Test NewsMessage instantiation
     */
    public function testNewsMessageInstantiation(): void
    {
        $this->assertInstanceOf(NewsMessage::class, $this->newsMessage);
    }

    /**
     * Test id getter and setter
     */
    public function testIdGetterAndSetter(): void
    {
        $id = 123;
        $result = $this->newsMessage->setId($id);

        $this->assertInstanceOf(NewsMessage::class, $result); // Test fluent interface
        $this->assertEquals($id, $this->newsMessage->getId());
    }

    /**
     * Test language getter and setter
     */
    public function testLanguageGetterAndSetter(): void
    {
        $language = 'en';
        $result = $this->newsMessage->setLanguage($language);

        $this->assertInstanceOf(NewsMessage::class, $result); // Test fluent interface
        $this->assertEquals($language, $this->newsMessage->getLanguage());
    }

    /**
     * Test header getter and setter
     */
    public function testHeaderGetterAndSetter(): void
    {
        $header = 'Breaking News';
        $result = $this->newsMessage->setHeader($header);

        $this->assertInstanceOf(NewsMessage::class, $result); // Test fluent interface
        $this->assertEquals($header, $this->newsMessage->getHeader());
    }

    /**
     * Test message getter and setter
     */
    public function testMessageGetterAndSetter(): void
    {
        $message = 'This is the news body.';
        $result = $this->newsMessage->setMessage($message);

        $this->assertInstanceOf(NewsMessage::class, $result); // Test fluent interface
        $this->assertEquals($message, $this->newsMessage->getMessage());
    }

    /**
     * Test created getter and setter
     */
    public function testCreatedGetterAndSetter(): void
    {
        $created = new DateTime('2026-02-01 09:30:00');
        $result = $this->newsMessage->setCreated($created);

        $this->assertInstanceOf(NewsMessage::class, $result); // Test fluent interface
        $this->assertSame($created, $this->newsMessage->getCreated());
        $this->assertInstanceOf(DateTime::class, $this->newsMessage->getCreated());
    }

    /**
     * Test author getter and setter
     */
    public function testAuthorGetterAndSetter(): void
    {
        $author = 'John Doe';
        $result = $this->newsMessage->setAuthor($author);

        $this->assertInstanceOf(NewsMessage::class, $result); // Test fluent interface
        $this->assertEquals($author, $this->newsMessage->getAuthor());
    }

    /**
     * Test email getter and setter
     */
    public function testEmailGetterAndSetter(): void
    {
        $email = 'john@example.com';
        $result = $this->newsMessage->setEmail($email);

        $this->assertInstanceOf(NewsMessage::class, $result); // Test fluent interface
        $this->assertEquals($email, $this->newsMessage->getEmail());
    }

    /**
     * Test active getter and setter
     */
    public function testActiveGetterAndSetter(): void
    {
        $result = $this->newsMessage->setActive(true);

        $this->assertInstanceOf(NewsMessage::class, $result); // Test fluent interface
        $this->assertTrue($this->newsMessage->isActive());

        $this->newsMessage->setActive(false);
        $this->assertFalse($this->newsMessage->isActive());
    }

    /**
     * Test comment getter and setter
     */
    public function testCommentGetterAndSetter(): void
    {
        $result = $this->newsMessage->setComment(true);

        $this->assertInstanceOf(NewsMessage::class, $result); // Test fluent interface
        $this->assertTrue($this->newsMessage->isComment());

        $this->newsMessage->setComment(false);
        $this->assertFalse($this->newsMessage->isComment());
    }

    /**
     * Test dateStart getter and setter
     */
    public function testDateStartGetterAndSetter(): void
    {
        $dateStart = new DateTime('2026-03-01 00:00:00');
        $result = $this->newsMessage->setDateStart($dateStart);

        $this->assertInstanceOf(NewsMessage::class, $result); // Test fluent interface
        $this->assertSame($dateStart, $this->newsMessage->getDateStart());
    }

    /**
     * Test dateEnd getter and setter
     */
    public function testDateEndGetterAndSetter(): void
    {
        $dateEnd = new DateTime('2026-03-31 23:59:59');
        $result = $this->newsMessage->setDateEnd($dateEnd);

        $this->assertInstanceOf(NewsMessage::class, $result); // Test fluent interface
        $this->assertSame($dateEnd, $this->newsMessage->getDateEnd());
    }

    /**
     * Test link getter and setter
     */
    public function testLinkGetterAndSetter(): void
    {
        $link = 'https://www.phpmyfaq.de';
        $result = $this->newsMessage->setLink($link);

        $this->assertInstanceOf(NewsMessage::class, $result); // Test fluent interface
        $this->assertEquals($link, $this->newsMessage->getLink());
    }

    /**
     * Test linkTitle getter and setter
     */
    public function testLinkTitleGetterAndSetter(): void
    {
        $linkTitle = 'Visit phpMyFAQ';
        $result = $this->newsMessage->setLinkTitle($linkTitle);

        $this->assertInstanceOf(NewsMessage::class, $result); // Test fluent interface
        $this->assertEquals($linkTitle, $this->newsMessage->getLinkTitle());
    }

    /**
     * Test linkTarget getter and setter
     */
    public function testLinkTargetGetterAndSetter(): void
    {
        $linkTarget = '_blank';
        $result = $this->newsMessage->setLinkTarget($linkTarget);

        $this->assertInstanceOf(NewsMessage::class, $result); // Test fluent interface
        $this->assertEquals($linkTarget, $this->newsMessage->getLinkTarget());
    }

    /**
     * Test fluent interface with method chaining
     */
    public function testFluentInterface(): void
    {
        $created = new DateTime('2026-04-01 12:00:00');
        $dateStart = new DateTime('2026-04-02 00:00:00');
        $dateEnd = new DateTime('2026-04-30 23:59:59');

        $result = $this->newsMessage
            ->setId(1)
            ->setLanguage('en')
            ->setHeader('Header')
            ->setMessage('Message')
            ->setCreated($created)
            ->setAuthor('Author')
            ->setEmail('author@example.com')
            ->setActive(true)
            ->setComment(false)
            ->setDateStart($dateStart)
            ->setDateEnd($dateEnd)
            ->setLink('https://example.com')
            ->setLinkTitle('Example')
            ->setLinkTarget('_self');

        $this->assertInstanceOf(NewsMessage::class, $result);
        $this->assertEquals(1, $this->newsMessage->getId());
        $this->assertEquals('en', $this->newsMessage->getLanguage());
        $this->assertEquals('Header', $this->newsMessage->getHeader());
        $this->assertEquals('Message', $this->newsMessage->getMessage());
        $this->assertSame($created, $this->newsMessage->getCreated());
        $this->assertEquals('Author', $this->newsMessage->getAuthor());
        $this->assertEquals('author@example.com', $this->newsMessage->getEmail());
        $this->assertTrue($this->newsMessage->isActive());
        $this->assertFalse($this->newsMessage->isComment());
        $this->assertSame($dateStart, $this->newsMessage->getDateStart());
        $this->assertSame($dateEnd, $this->newsMessage->getDateEnd());
        $this->assertEquals('https://example.com', $this->newsMessage->getLink());
        $this->assertEquals('Example', $this->newsMessage->getLinkTitle());
        $this->assertEquals('_self', $this->newsMessage->getLinkTarget());
    }

    /**
     * Test nullable date getters return null by default
     */
    public function testNullableDateGettersReturnNullByDefault(): void
    {
        $entity = new NewsMessage();

        $this->assertNull($entity->getDateStart());
        $this->assertNull($entity->getDateEnd());
    }

    /**
     * Test nullable link getters return empty string by default
     */
    public function testNullableLinkGettersReturnEmptyStringByDefault(): void
    {
        $entity = new NewsMessage();

        $this->assertEquals('', $entity->getLink());
        $this->assertEquals('', $entity->getLinkTitle());
        $this->assertEquals('', $entity->getLinkTarget());
    }

    /**
     * Test zero and boundary id values
     */
    public function testIdEdgeCases(): void
    {
        $this->newsMessage->setId(0);
        $this->assertEquals(0, $this->newsMessage->getId());

        $this->newsMessage->setId(-1);
        $this->assertEquals(-1, $this->newsMessage->getId());

        $this->newsMessage->setId(PHP_INT_MAX);
        $this->assertEquals(PHP_INT_MAX, $this->newsMessage->getId());
    }

    /**
     * Test empty string values
     */
    public function testEmptyStringValues(): void
    {
        $this->newsMessage->setLanguage('');
        $this->newsMessage->setHeader('');
        $this->newsMessage->setMessage('');
        $this->newsMessage->setAuthor('');
        $this->newsMessage->setEmail('');
        $this->newsMessage->setLink('');
        $this->newsMessage->setLinkTitle('');
        $this->newsMessage->setLinkTarget('');

        $this->assertEquals('', $this->newsMessage->getLanguage());
        $this->assertEquals('', $this->newsMessage->getHeader());
        $this->assertEquals('', $this->newsMessage->getMessage());
        $this->assertEquals('', $this->newsMessage->getAuthor());
        $this->assertEquals('', $this->newsMessage->getEmail());
        $this->assertEquals('', $this->newsMessage->getLink());
        $this->assertEquals('', $this->newsMessage->getLinkTitle());
        $this->assertEquals('', $this->newsMessage->getLinkTarget());
    }

    /**
     * Test created with different DateTime objects
     */
    public function testCreatedWithDifferentDateTimes(): void
    {
        $dates = [
            new DateTime('2026-01-01 00:00:00'),
            new DateTime('2026-12-31 23:59:59'),
            new DateTime(),
            new DateTime('1970-01-01 00:00:00'),
        ];

        foreach ($dates as $date) {
            $this->newsMessage->setCreated($date);
            $this->assertSame($date, $this->newsMessage->getCreated());
        }
    }
}
