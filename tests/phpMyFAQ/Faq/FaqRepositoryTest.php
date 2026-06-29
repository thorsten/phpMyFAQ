<?php

namespace phpMyFAQ\Faq;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
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
    ): void {
        $database = $this->configuration->getDb();
        $prefix = Database::getTablePrefix();

        $database->query(sprintf(
            "INSERT INTO %sfaqdata (id, lang, solution_id, revision_id, active, sticky, keywords, thema, content, author, email, comment, updated, date_start, date_end, created, notes, sticky_order)
             VALUES (%d, '%s', %d, 0, '%s', 0, '%s', '%s', 'Answer', 'Author', 'author@example.com', 'y', '20260301010101', '00000000000000', '99991231235959', '2026-03-01 01:01:01', '', 0)",
            $prefix,
            $id,
            $lang,
            $solutionId,
            $active,
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
        $this->assertSame($this->faqRepository->getNextSolutionId(), $this->faqRepository->getSolutionIdFromId(999998, 'en'));
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
}
