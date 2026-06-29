<?php

namespace phpMyFAQ\Faq;

use DateTime;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\Language;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class FaqRepositoryTest extends TestCase
{
    private Configuration $configuration;

    private FaqRepository $faqRepository;

    private string $databaseFile;

    protected function setUp(): void
    {
        parent::setUp();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $reflectionProperty = new ReflectionProperty(Configuration::class, 'configuration');
        $reflectionProperty->setValue(null, null);

        $this->databaseFile = PMF_TEST_DIR . '/faq-repository-' . uniqid('', true) . '.db';
        copy(PMF_TEST_DIR . '/test.db', $this->databaseFile);

        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databaseFile, '', '');
        $this->configuration = new Configuration($dbHandle);
        $this->configuration->set('main.currentVersion', System::getVersion());
        $this->configuration->set('main.language', 'language_en.php');
        $this->configuration->set('security.permLevel', 'basic');

        $language = new Language($this->configuration, $this->createStub(Session::class));
        $this->configuration->setLanguage($language);
        Language::$language = 'en';

        $this->faqRepository = new FaqRepository($this->configuration);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Language::$language = '';
        @unlink($this->databaseFile);
    }

    private function seedFaqRecord(
        int $id,
        int $solutionId,
        string $question = 'Question',
        string $keywords = 'Keywords',
        string $active = 'yes',
        int $categoryId = 1,
        string $lang = 'en',
        int $userId = -1,
        int $sticky = 0,
    ): void {
        $database = $this->configuration->getDb();
        $prefix = Database::getTablePrefix();

        $database->query(sprintf(
            "INSERT INTO %sfaqdata (id, lang, solution_id, revision_id, active, sticky, keywords, thema, content, author, email, comment, updated, date_start, date_end, created, notes, sticky_order)
             VALUES (%d, '%s', %d, 0, '%s', %d, '%s', '%s', 'Answer', 'Author', 'author@example.com', 'y', '20260301010101', '00000000000000', '99991231235959', '2026-03-01 01:01:01', '', 0)",
            $prefix,
            $id,
            $lang,
            $solutionId,
            $active,
            $sticky,
            $database->escape($keywords),
            $database->escape($question),
        ));
        $database->query(sprintf(
            "INSERT INTO %sfaqcategoryrelations (category_id, category_lang, record_id, record_lang) VALUES (%d, '%s', %d, '%s')",
            $prefix,
            $categoryId,
            $lang,
            $id,
            $lang,
        ));
        $database->query(sprintf(
            'INSERT INTO %sfaqdata_user (record_id, user_id) VALUES (%d, %d)',
            $prefix,
            $id,
            $userId,
        ));
    }

    public function testGetNextSolutionIdReturnsMaxPlusIncrement(): void
    {
        $this->seedFaqRecord(id: 5010, solutionId: 7010);
        $this->seedFaqRecord(id: 5011, solutionId: 7020);

        $nextSolutionId = $this->faqRepository->getNextSolutionId();

        $this->assertSame(7020 + PMF_SOLUTION_ID_INCREMENT_VALUE, $nextSolutionId);
    }

    public function testGetSolutionIdFromIdReturnsStoredSolutionId(): void
    {
        $this->seedFaqRecord(id: 5012, solutionId: 7030);

        $this->assertSame(7030, $this->faqRepository->getSolutionIdFromId(5012, 'en'));
    }

    public function testGetSolutionIdFromIdFallsBackToNextSolutionIdWhenMissing(): void
    {
        $this->assertSame(
            $this->faqRepository->getNextSolutionId(),
            $this->faqRepository->getSolutionIdFromId(999998, 'en'),
        );
    }

    public function testHasTranslation(): void
    {
        $this->seedFaqRecord(id: 5013, solutionId: 7040);

        $this->assertTrue($this->faqRepository->hasTranslation(5013, 'en'));
        $this->assertFalse($this->faqRepository->hasTranslation(5013, 'de'));
        $this->assertFalse($this->faqRepository->hasTranslation(999999, 'en'));
    }

    public function testIsActive(): void
    {
        $this->seedFaqRecord(id: 5014, solutionId: 7050, active: 'yes');
        $this->seedFaqRecord(id: 5015, solutionId: 7060, active: 'no');

        $this->assertTrue($this->faqRepository->isActive(5014, 'en'));
        $this->assertFalse($this->faqRepository->isActive(5015, 'en'));
        $this->assertFalse($this->faqRepository->isActive(999997, 'en'));
    }

    public function testIsActiveUsesNewsTableForNewsCommentType(): void
    {
        $this->assertFalse($this->faqRepository->isActive(6001, 'en', 'news'));
    }

    public function testGetIdFromSolutionId(): void
    {
        $this->seedFaqRecord(id: 5016, solutionId: 7070, question: 'Resolved Question', categoryId: 77);

        $record = $this->faqRepository->getIdFromSolutionId(7070, -1, [-1], false);

        $this->assertSame(5016, (int) $record['id']);
        $this->assertSame('en', $record['lang']);
        $this->assertSame('Resolved Question', $record['question']);
        $this->assertSame(77, (int) $record['category_id']);
    }

    public function testGetIdFromSolutionIdReturnsEmptyArrayWhenMissing(): void
    {
        $this->assertSame([], $this->faqRepository->getIdFromSolutionId(999999, -1, [-1], false));
    }

    public function testFetchQuestionReturnsRawQuestion(): void
    {
        $this->seedFaqRecord(id: 5017, solutionId: 7080, question: 'Database Question');

        $this->assertSame('Database Question', $this->faqRepository->fetchQuestion(5017, 'en'));
    }

    public function testFetchQuestionReturnsNullWhenMissing(): void
    {
        $this->assertNull($this->faqRepository->fetchQuestion(999999, 'en'));
    }

    public function testFetchKeywordsReturnsRawUnescapedKeywords(): void
    {
        $this->seedFaqRecord(id: 5018, solutionId: 7090, keywords: 'alpha & beta');

        $this->assertSame('alpha & beta', $this->faqRepository->fetchKeywords(5018, 'en'));
    }

    public function testFetchKeywordsReturnsNullWhenMissing(): void
    {
        $this->assertNull($this->faqRepository->fetchKeywords(999996, 'en'));
    }

    public function testGetFaqResultReturnsMatchingRecord(): void
    {
        $this->seedFaqRecord(id: 5020, solutionId: 7100, question: 'Result Question');

        $result = $this->faqRepository->getFaqResult(5020, 'en', null, false, -1, [-1], false);

        $this->assertSame(1, $this->configuration->getDb()->numRows($result));
        $row = $this->configuration->getDb()->fetchObject($result);
        $this->assertSame(5020, (int) $row->id);
        $this->assertSame('Result Question', $row->thema);
    }

    public function testFetchFaqByIdAndCategoryIdReturnsRow(): void
    {
        $this->seedFaqRecord(id: 5021, solutionId: 7110, question: 'Scoped Question', categoryId: 88);

        $row = $this->faqRepository->fetchFaqByIdAndCategoryId(5021, 88, true, -1, [-1], false);

        $this->assertIsObject($row);
        $this->assertSame(5021, (int) $row->id);
        $this->assertSame('Scoped Question', $row->question);
        $this->assertSame(88, (int) $row->category_id);
    }

    public function testFetchFaqByIdAndCategoryIdReturnsNullWhenMissing(): void
    {
        $this->assertNull($this->faqRepository->fetchFaqByIdAndCategoryId(999995, 999994, true, -1, [-1], false));
    }

    public function testFetchRowBySolutionIdReturnsRow(): void
    {
        $this->seedFaqRecord(id: 5022, solutionId: 7120, question: 'By Solution');

        $row = $this->faqRepository->fetchRowBySolutionId(7120, -1, [-1], false);

        $this->assertIsObject($row);
        $this->assertSame(5022, (int) $row->id);
        $this->assertSame(7120, (int) $row->solution_id);
    }

    public function testFetchRowBySolutionIdReturnsNullWhenMissing(): void
    {
        $this->assertNull($this->faqRepository->fetchRowBySolutionId(999993, -1, [-1], false));
    }

    public function testFetchAvailableFaqsByCategoryIdReturnsActiveRows(): void
    {
        $this->seedFaqRecord(id: 5030, solutionId: 7200, question: 'Available FAQ', categoryId: 91);

        $rows = $this->faqRepository->fetchAvailableFaqsByCategoryId(91, 'fd', 'id', 'ASC', -1, [-1], false);

        $this->assertCount(1, $rows);
        $this->assertSame(5030, (int) $rows[0]->id);
        $this->assertSame('Available FAQ', $rows[0]->thema);
    }

    public function testFetchAvailableFaqsByCategoryIdReturnsEmptyArrayForUnknownCategory(): void
    {
        $this->assertSame(
            [],
            $this->faqRepository->fetchAvailableFaqsByCategoryId(99999, 'fd', 'id', 'ASC', -1, [-1], false),
        );
    }

    public function testFetchFaqsByIdsReturnsRows(): void
    {
        $this->seedFaqRecord(id: 5031, solutionId: 7210, question: 'Listed FAQ');

        $rows = $this->faqRepository->fetchFaqsByIds('5031', true, -1, [-1], false);

        $this->assertCount(1, $rows);
        $this->assertSame(5031, (int) $rows[0]->id);
        $this->assertSame('Listed FAQ', $rows[0]->question);
    }

    public function testFetchFaqsByIdsReturnsEmptyArrayWhenNoneMatch(): void
    {
        $this->assertSame([], $this->faqRepository->fetchFaqsByIds('999999', true, -1, [-1], false));
    }

    public function testFetchStickyFaqsReturnsActiveStickyRows(): void
    {
        $this->seedFaqRecord(id: 5040, solutionId: 7300, question: 'Sticky FAQ', sticky: 1);
        $this->seedFaqRecord(id: 5041, solutionId: 7310, question: 'Non-sticky FAQ', sticky: 0);

        $rows = $this->faqRepository->fetchStickyFaqs(-1, [-1], false);

        $ids = array_map(static fn(object $row): int => (int) $row->id, $rows);
        $this->assertContains(5040, $ids);
        $this->assertNotContains(5041, $ids);
    }

    public function testFetchAllFaqsReturnsRows(): void
    {
        $this->seedFaqRecord(id: 5042, solutionId: 7320, question: 'All FAQ');

        $rows = $this->faqRepository->fetchAllFaqs(null, '', -1, [-1], false);

        $ids = array_map(static fn(object $row): int => (int) $row->id, $rows);
        $this->assertContains(5042, $ids);
    }

    public function testFetchAllFaqsAppliesConditionFilter(): void
    {
        $this->seedFaqRecord(id: 5043, solutionId: 7330, question: 'Wanted');
        $this->seedFaqRecord(id: 5044, solutionId: 7340, question: 'Unwanted');

        $rows = $this->faqRepository->fetchAllFaqs(['fd.id' => '5043'], '', -1, [-1], false);

        $ids = array_map(static fn(object $row): int => (int) $row->id, $rows);
        $this->assertContains(5043, $ids);
        $this->assertNotContains(5044, $ids);
    }

    public function testInsertCreatesFaqRow(): void
    {
        $this->faqRepository->insert($this->makeFaqEntity(5050, 7400, 'Inserted Question'));

        $this->assertSame('Inserted Question', $this->faqRepository->fetchQuestion(5050, 'en'));
        $this->assertSame(7400, $this->faqRepository->getSolutionIdFromId(5050, 'en'));
    }

    public function testUpdateModifiesFaqRow(): void
    {
        $faqEntity = $this->makeFaqEntity(5051, 7410, 'Before');
        $this->faqRepository->insert($faqEntity);

        $faqEntity->setQuestion('After')->setRevisionId(1);
        $this->faqRepository->update($faqEntity);

        $this->assertSame('After', $this->faqRepository->fetchQuestion(5051, 'en'));
    }

    public function testDeleteByIdAndLanguageRemovesFaqRow(): void
    {
        $this->faqRepository->insert($this->makeFaqEntity(5052, 7420, 'To Delete'));
        $this->assertTrue($this->faqRepository->hasTranslation(5052, 'en'));

        $this->faqRepository->deleteByIdAndLanguage(5052, 'en');

        $this->assertFalse($this->faqRepository->hasTranslation(5052, 'en'));
    }

    public function testQueryRenderableFaqsByCategoryIdReturnsResult(): void
    {
        $this->seedFaqRecord(id: 5060, solutionId: 7500, question: 'Render Cat', categoryId: 95);

        $result = $this->faqRepository->queryRenderableFaqsByCategoryId(95, 'ORDER BY fd.id ASC', -1, [-1], false);

        $this->assertGreaterThan(0, $this->configuration->getDb()->numRows($result));
        $row = $this->configuration->getDb()->fetchObject($result);
        $this->assertSame(5060, (int) $row->id);
        $this->assertSame('Render Cat', $row->question);
    }

    public function testQueryRenderableFaqsByIdsReturnsResult(): void
    {
        $this->seedFaqRecord(id: 5061, solutionId: 7510, question: 'Render By Id');

        $result = $this->faqRepository->queryRenderableFaqsByIds('5061', 'fd.id', 'ASC', -1, [-1], false);

        $this->assertGreaterThan(0, $this->configuration->getDb()->numRows($result));
        $row = $this->configuration->getDb()->fetchObject($result);
        $this->assertSame(5061, (int) $row->id);
        $this->assertSame('Render By Id', $row->question);
    }

    private function makeFaqEntity(int $id, int $solutionId, string $question): FaqEntity
    {
        return new FaqEntity()
            ->setId($id)
            ->setLanguage('en')
            ->setSolutionId($solutionId)
            ->setRevisionId(0)
            ->setActive(true)
            ->setSticky(false)
            ->setKeywords('Keywords')
            ->setQuestion($question)
            ->setAnswer('Answer')
            ->setAuthor('Author')
            ->setEmail('foo@bar.baz')
            ->setComment(false)
            ->setNotes('')
            ->setUpdatedDate(new DateTime());
    }
}
