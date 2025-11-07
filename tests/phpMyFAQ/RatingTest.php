<?php

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\Vote;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

class RatingTest extends TestCase
{
    private Sqlite3 $dbHandle;

    private Rating $rating;

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Translation::create()
            ->setLanguagesDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($this->dbHandle);
        $language = new Language($configuration, $this->createMock(Session::class));
        $language->setLanguageFromConfiguration('en');
        $configuration->setLanguage($language);

        $this->rating = new Rating($configuration);
    }

    protected function tearDown(): void
    {
        $this->dbHandle->query('DELETE FROM faqvoting');
    }

    public function testCreate(): void
    {
        $votingData = new Vote();
        $votingData
            ->setFaqId(1)
            ->setVote(5)
            ->setIp('127.0.0.1');

        $this->assertTrue($this->rating->create($votingData));
    }

    public function testGet(): void
    {
        $votingData = new Vote();
        $votingData
            ->setFaqId(1)
            ->setVote(5)
            ->setIp('127.0.0.1');

        $this->rating->create($votingData);

        $this->assertEquals(' <span data-rating="5">5</span> (1 Vote)', $this->rating->get(1));
    }

    public function testCheck(): void
    {
        $votingData = new Vote();
        $votingData
            ->setFaqId(1)
            ->setVote(5)
            ->setIp('127.0.0.1');

        $this->rating->create($votingData);

        $this->assertFalse($this->rating->check(1, '127.0.0.1'));
    }

    public function testGetNumberOfVotings(): void
    {
        $votingData = new Vote();
        $votingData
            ->setFaqId(1)
            ->setVote(5)
            ->setIp('127.0.0.1');

        $this->rating->create($votingData);

        $votingData = new Vote();
        $votingData
            ->setFaqId(1)
            ->setVote(4)
            ->setIp('127.0.0.2');

        $this->rating->update($votingData);

        $this->assertEquals(2, $this->rating->getNumberOfVotings(1));
    }

    public function testGetNumberOfVotingsWithNoVotes(): void
    {
        $this->assertEquals(0, $this->rating->getNumberOfVotings(1));
    }

    public function testUpdate(): void
    {
        $votingData = new Vote();
        $votingData
            ->setFaqId(1)
            ->setVote(5)
            ->setIp('127.0.0.1');

        $this->rating->create($votingData);

        $votingData = new Vote();
        $votingData
            ->setFaqId(1)
            ->setVote(4)
            ->setIp('127.0.0.2');

        $this->assertTrue($this->rating->update($votingData));
    }

    public function testDeleteAll(): void
    {
        $votingData = new Vote();
        $votingData->setFaqId(1)->setVote(5)->setIp('127.0.0.1');

        $this->rating->create($votingData);

        $this->assertTrue($this->rating->deleteAll());
        $this->assertEquals(' <span data-rating="0">0</span> (0 Votes)', $this->rating->get(1));
    }
}
