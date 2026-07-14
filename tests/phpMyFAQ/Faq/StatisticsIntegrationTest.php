<?php

namespace phpMyFAQ\Faq;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Statistics tests against a real SQLite database, pinning the effective
 * permission semantics (user AND group must match) and the result limits.
 */
#[AllowMockObjectsWithoutExpectations]
class StatisticsIntegrationTest extends TestCase
{
    private Configuration $configuration;

    private string $databaseFile;

    private ?Configuration $previousConfiguration = null;

    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();
        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->databaseFile = PMF_TEST_DIR . '/statistics-' . uniqid('', true) . '.db';
        copy(PMF_TEST_DIR . '/test.db', $this->databaseFile);

        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databaseFile, '', '');
        Database::setTablePrefix('');

        $this->configuration = new Configuration($dbHandle);
        $this->configuration->getAll();
        $this->configuration->set('main.currentVersion', System::getVersion());
        $this->configuration->set('main.language', 'language_en.php');

        $reflectionProperty = new ReflectionProperty(Configuration::class, 'configuration');
        $this->previousConfiguration = $reflectionProperty->getValue();
        $reflectionProperty->setValue(null, $this->configuration);

        $language = new Language($this->configuration, $this->createStub(Session::class));
        $this->configuration->setLanguage($language);
        Language::$language = 'en';
    }

    protected function tearDown(): void
    {
        $reflectionProperty = new ReflectionProperty(Configuration::class, 'configuration');
        $reflectionProperty->setValue(null, $this->previousConfiguration);

        Language::$language = '';
        @unlink($this->databaseFile);

        parent::tearDown();
    }

    /**
     * @param int[] $userIds
     * @param int[] $groupIds
     * @param int[] $categoryIds
     */
    private function seedFaq(
        int $id,
        string $question,
        string $updated,
        int $visits,
        array $userIds = [-1],
        array $groupIds = [],
        array $categoryIds = [1],
    ): void {
        $database = $this->configuration->getDb();

        $database->query(sprintf(
            "INSERT INTO faqdata (id, lang, solution_id, revision_id, active, sticky, keywords, thema, content, "
            . "author, email, comment, updated, date_start, date_end, created, notes, sticky_order) "
            . "VALUES (%d, 'en', %d, 0, 'yes', 0, '', '%s', 'Answer', 'Author', 'author@example.com', 'y', "
            . "'%s', '00000000000000', '99991231235959', '2026-03-01 01:01:01', '', 0)",
            $id,
            1000 + $id,
            $database->escape($question),
            $updated,
        ));

        $database->query(sprintf(
            "INSERT INTO faqvisits (id, lang, visits, last_visit) VALUES (%d, 'en', %d, %d)",
            $id,
            $visits,
            time(),
        ));

        foreach ($categoryIds as $categoryId) {
            $database->query(sprintf(
                "INSERT INTO faqcategoryrelations (category_id, category_lang, record_id, record_lang) "
                . "VALUES (%d, 'en', %d, 'en')",
                $categoryId,
                $id,
            ));
        }

        foreach ($userIds as $userId) {
            $database->query(sprintf('INSERT INTO faqdata_user (record_id, user_id) VALUES (%d, %d)', $id, $userId));
        }

        foreach ($groupIds as $groupId) {
            $database->query(sprintf('INSERT INTO faqdata_group (record_id, group_id) VALUES (%d, %d)', $id, $groupId));
        }
    }

    private function createStatistics(string $permLevel): Statistics
    {
        $this->configuration->set('security.permLevel', $permLevel);

        return new Statistics($this->configuration);
    }

    public function testGetLatestDataHidesUserRestrictedFaqsFromGuests(): void
    {
        $this->seedFaq(9001, 'Public FAQ', '20260101000000', 1);
        $this->seedFaq(9002, 'Restricted FAQ', '20260102000000', 1, userIds: [42]);

        $latest = $this->createStatistics('basic')->getLatestData(10, 'en');

        $this->assertArrayHasKey(9001, $latest);
        $this->assertArrayNotHasKey(9002, $latest);
    }

    public function testGetLatestDataShowsUserRestrictedFaqsToTheirUser(): void
    {
        $this->seedFaq(9001, 'Public FAQ', '20260101000000', 1);
        $this->seedFaq(9002, 'Restricted FAQ', '20260102000000', 1, userIds: [42]);

        $latest = $this->createStatistics('basic')->setUser(42)->getLatestData(10, 'en');

        $this->assertArrayHasKey(9001, $latest);
        $this->assertArrayHasKey(9002, $latest);
    }

    public function testGetLatestDataFiltersByGroupWithGroupSupport(): void
    {
        $this->seedFaq(9001, 'Group 10 FAQ', '20260101000000', 1, groupIds: [10]);
        $this->seedFaq(9002, 'Group 99 FAQ', '20260102000000', 1, groupIds: [99]);

        $latest = $this->createStatistics('medium')->setGroups([10])->getLatestData(10, 'en');

        $this->assertArrayHasKey(9001, $latest);
        $this->assertArrayNotHasKey(9002, $latest);
    }

    public function testGetLatestDataWithGroupSupportRequiresMatchingUser(): void
    {
        $this->seedFaq(9001, 'Owner-only FAQ', '20260101000000', 1, userIds: [42], groupIds: [10]);

        $statistics = $this->createStatistics('medium');

        $this->assertArrayNotHasKey(9001, $statistics->setGroups([10])->getLatestData(10, 'en'));
        $this->assertArrayHasKey(9001, $statistics->setUser(42)->setGroups([10])->getLatestData(10, 'en'));
    }

    public function testGetLatestDataReturnsMostRecentlyUpdatedFaqsUpToCount(): void
    {
        $this->seedFaq(9001, 'Oldest', '20260101000000', 1);
        $this->seedFaq(9002, 'Middle', '20260102000000', 1);
        $this->seedFaq(9003, 'Newest', '20260103000000', 1);

        $latest = $this->createStatistics('basic')->getLatestData(2, 'en');

        $this->assertCount(2, $latest);
        $this->assertArrayHasKey(9003, $latest);
        $this->assertArrayHasKey(9002, $latest);
    }

    public function testGetLatestDataCountsEachFaqOnceDespiteMultipleGroupsAndCategories(): void
    {
        $this->seedFaq(9001, 'Fanout FAQ', '20260103000000', 1, groupIds: [10, 20, 30], categoryIds: [1, 2, 3]);
        $this->seedFaq(9002, 'Second FAQ', '20260102000000', 1, groupIds: [10]);

        $latest = $this->createStatistics('medium')->setGroups([10, 20, 30])->getLatestData(2, 'en');

        $this->assertCount(2, $latest);
        $this->assertArrayHasKey(9001, $latest);
        $this->assertArrayHasKey(9002, $latest);
    }

    public function testGetTopTenDataOrdersByVisitsAndFiltersByCategory(): void
    {
        $this->seedFaq(9001, 'Category 1 popular', '20260101000000', 500, categoryIds: [1]);
        $this->seedFaq(9002, 'Category 2 popular', '20260101000000', 900, categoryIds: [2]);
        $this->seedFaq(9003, 'Category 1 quiet', '20260101000000', 5, categoryIds: [1]);

        $statistics = $this->createStatistics('basic');

        $all = $statistics->getTopTenData(10, 0, 'en');
        $this->assertCount(3, $all);
        $this->assertSame(900, reset($all)['visits']);

        $categoryOne = $statistics->getTopTenData(10, 1, 'en');
        $this->assertCount(2, $categoryOne);
        $this->assertSame(500, reset($categoryOne)['visits']);
    }

    public function testGetTrendingDataRespectsPermissions(): void
    {
        $this->seedFaq(9001, 'Visible trending', '20260101000000', 100);
        $this->seedFaq(9002, 'Hidden trending', '20260102000000', 200, userIds: [42]);

        $trending = $this->createStatistics('basic')->getTrendingData(10, 'en');

        $this->assertArrayHasKey(9001, $trending);
        $this->assertArrayNotHasKey(9002, $trending);
    }

    public function testGetTopVotedDataReturnsPermittedVotedFaqs(): void
    {
        $this->seedFaq(9001, 'Voted FAQ', '20260101000000', 1);
        $this->seedFaq(9002, 'Hidden voted FAQ', '20260102000000', 1, userIds: [42]);

        $database = $this->configuration->getDb();
        $database->query(
            "INSERT INTO faqvoting (id, artikel, vote, usr, datum, ip) VALUES (9001, 9001, 9, 2, '', '')",
        );
        $database->query(
            "INSERT INTO faqvoting (id, artikel, vote, usr, datum, ip) VALUES (9002, 9002, 10, 2, '', '')",
        );

        $voted = $this->createStatistics('basic')->getTopVotedData(10, 'en');

        $this->assertCount(1, $voted);
        $this->assertSame('Voted FAQ', $voted[0]['question']);
    }
}
