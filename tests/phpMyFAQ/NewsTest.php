<?php

namespace phpMyFAQ;

use DateTime;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\NewsMessage;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

class NewsTest extends TestCase
{
    private News $news;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($dbHandle);

        $language = new Language($configuration, $this->createStub(Session::class));
        $configuration->setLanguage($language);

        $this->news = new News($configuration);
    }

    public function testCreate(): void
    {
        $news = new NewsMessage();
        $news
            ->setCreated(new DateTime())
            ->setLanguage('en')
            ->setHeader('Test')
            ->setMessage('Message')
            ->setAuthor('Test Author')
            ->setEmail('test@example.org')
            ->setActive(true)
            ->setComment(true);

        $this->assertTrue($this->news->create($news));
        $this->news->delete(1);
    }

    public function testUpdate(): void
    {
        $news = new NewsMessage();
        $news
            ->setCreated(new DateTime())
            ->setLanguage('en')
            ->setHeader('Test')
            ->setMessage('Message')
            ->setAuthor('Test Author')
            ->setEmail('test@example.org')
            ->setActive(true)
            ->setComment(true);

        $this->news->create($news);

        $news
            ->setId(1)
            ->setCreated(new DateTime())
            ->setLanguage('en')
            ->setHeader('Test Updated')
            ->setMessage('Message Updated')
            ->setAuthor('Test Author')
            ->setEmail('test@example.org')
            ->setActive(true)
            ->setComment(true);

        $this->assertTrue($this->news->update($news));
        $this->news->delete(1);
    }
}
