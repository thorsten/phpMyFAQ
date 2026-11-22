<?php

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\Vote;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class RatingTest extends TestCase
{
    private Sqlite3 $dbHandle;
    private string $databasePath;
    private ?Configuration $previousConfiguration = null;

    private Rating $rating;

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Translation::resetInstance();
        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-rating-test-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        $this->initializeDatabaseStatics($this->dbHandle);
        $configuration = new Configuration($this->dbHandle);
        $language = new Language($configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $configuration->setLanguage($language);

        $this->rating = new Rating($configuration);

        // Clean up any existing data before each test
        $this->dbHandle->query('DELETE FROM faqvoting');
    }

    protected function tearDown(): void
    {
        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, $this->previousConfiguration);

        $this->dbHandle->query('DELETE FROM faqvoting');
        $this->dbHandle->close();
        @unlink($this->databasePath);

        parent::tearDown();
    }

    public function testCreate(): void
    {
        $votingData = new Vote();
        $votingData->setFaqId(1)->setVote(5)->setIp('127.0.0.1');

        $this->assertTrue($this->rating->create($votingData));
    }

    public function testGet(): void
    {
        $votingData = new Vote();
        $votingData->setFaqId(1)->setVote(5)->setIp('127.0.0.1');

        $this->rating->create($votingData);

        $this->assertEquals(' <span data-rating="5">5</span> (1 Vote)', $this->rating->get(1));
    }

    /**
     * vote and usr are integer columns: SQLite, PostgreSQL and SQL Server divide them as
     * integers unless the query asks for a decimal result.
     */
    public function testGetReturnsTheDecimalAverageStoredInTheDatabase(): void
    {
        $votingData = new Vote();
        $votingData->setFaqId(1)->setVote(5)->setIp('127.0.0.1');
        $this->rating->create($votingData);

        $votingData = new Vote();
        $votingData->setFaqId(1)->setVote(4)->setIp('127.0.0.2');
        $this->rating->update($votingData);

        $this->assertSame(' <span data-rating="4.5">4.5</span> (2 Votes)', $this->rating->get(1));
    }

    public function testGetKeepsTheDecimalsOfTheAverage(): void
    {
        $repository = $this->createStub(\phpMyFAQ\Search\Rating\RatingRepository::class);
        $repository->method('fetchByRecordId')->willReturn((object) ['voting' => '4.6667', 'usr' => 3]);

        // Rating is readonly: its properties can only be set on an instance built without the constructor
        $rating = new \ReflectionClass(Rating::class)->newInstanceWithoutConstructor();
        new \ReflectionProperty(Rating::class, 'ratingRepository')->setValue($rating, $repository);
        new \ReflectionProperty(Rating::class, 'plurals')->setValue($rating, new \phpMyFAQ\Language\Plurals());

        $this->assertSame(' <span data-rating="4.67">4.67</span> (3 Votes)', $rating->get(1));
    }

    public function testCheck(): void
    {
        $votingData = new Vote();
        $votingData->setFaqId(1)->setVote(5)->setIp('127.0.0.1');

        $this->rating->create($votingData);

        $this->assertFalse($this->rating->check(1, '127.0.0.1'));
    }

    public function testGetNumberOfVotings(): void
    {
        $votingData = new Vote();
        $votingData->setFaqId(1)->setVote(5)->setIp('127.0.0.1');

        $this->rating->create($votingData);

        $votingData = new Vote();
        $votingData->setFaqId(1)->setVote(4)->setIp('127.0.0.2');

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
        $votingData->setFaqId(1)->setVote(5)->setIp('127.0.0.1');

        $this->rating->create($votingData);

        $votingData = new Vote();
        $votingData->setFaqId(1)->setVote(4)->setIp('127.0.0.2');

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

    private function initializeDatabaseStatics(Sqlite3 $dbHandle): void
    {
        $databaseReflection = new ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $dbHandle);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'sqlite3');
        Database::setTablePrefix('');
    }
}
