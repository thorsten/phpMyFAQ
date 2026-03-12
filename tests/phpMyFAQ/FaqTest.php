<?php

namespace phpMyFAQ;

use DateTime;
use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\Filesystem\File\FileException;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\Tenant\QuotaExceededException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
#[UsesClass(Faq::class)]
class FaqTest extends TestCase
{
    /** @var Configuration */
    private Configuration $configuration;

    /** @var Faq */
    private Faq $faq;

    private string $databaseFile;

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->resetConfigurationSingleton();
        $this->databaseFile = PMF_TEST_DIR . '/faq-' . uniqid('', true) . '.db';
        copy(PMF_TEST_DIR . '/test.db', $this->databaseFile);

        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databaseFile, '', '');
        $this->configuration = new Configuration($dbHandle);
        $this->configuration->set('main.currentVersion', System::getVersion());
        $this->configuration->set('main.language', 'language_en.php');
        $this->configuration->set('main.referenceURL', 'https://localhost/');
        $this->configuration->set('records.numberOfRecordsPerPage', 10);
        $this->configuration->set('records.randomSort', false);
        $this->configuration->set('records.orderStickyFaqsCustom', false);
        $this->configuration->set('search.enableElasticsearch', false);
        $this->configuration->set('security.permLevel', 'basic');

        $language = new Language($this->configuration, $this->createStub(Session::class));
        $this->configuration->setLanguage($language);
        Language::$language = 'en';

        $this->faq = new Faq($this->configuration);
    }

    /**
     * @throws AttachmentException
     * @throws FileException
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        putenv('PMF_TENANT_QUOTA_MAX_FAQS');
        Language::$language = '';
        @unlink($this->databaseFile);
    }

    public function testSetGroups(): void
    {
        $this->assertInstanceOf(Faq::class, $this->faq->setGroups([-1]));
    }

    public function testSetUser(): void
    {
        $this->assertInstanceOf(Faq::class, $this->faq->setUser(-1));
    }

    public function testHasTitleAHash(): void
    {
        $this->assertTrue($this->faq->hasTitleAHash('H#llo World!'));
        $this->assertFalse($this->faq->hasTitleAHash('Hallo World!'));
    }

    public function testCreate(): void
    {
        $faqEntity = $this->getFaqEntity();

        // Call the method being tested
        $result = $this->faq->create($faqEntity);

        // Assert that the method returns an integer
        $this->assertIsInt($result->getId());
        $this->assertGreaterThan(0, $result->getId());
    }

    public function testGetNextSolutionId(): void
    {
        $this->assertIsInt($this->faq->getNextSolutionId());
        $this->assertGreaterThan(0, $this->faq->getNextSolutionId());

        $this->faq->create($this->getFaqEntity());

        $this->assertGreaterThan(1, $this->faq->getNextSolutionId());
    }

    public function testCreateThrowsWhenFaqQuotaIsExceeded(): void
    {
        putenv('PMF_TENANT_QUOTA_MAX_FAQS=0');

        $this->expectException(QuotaExceededException::class);
        $this->faq->create($this->getFaqEntity());
    }

    public function testUpdate(): void
    {
        $faqEntity = $this->getFaqEntity();

        $faqEntity = $this->faq->create($faqEntity);

        $faqEntity->setId($faqEntity->getId());

        $faqEntity->setRevisionId(1);
        $faqEntity->setQuestion('Updated question');
        $faqEntity->setAnswer('Updated answer');

        $result = $this->faq->update($faqEntity);

        $this->assertInstanceOf(FaqEntity::class, $result);
    }

    /**
     * @throws AttachmentException
     * @throws FileException
     */
    public function testDeleteRecord(): void
    {
        $faqEntity = $this->getFaqEntity();
        $faqEntity->setId($this->faq->create($faqEntity)->getId());

        $result = $this->faq->delete($faqEntity->getId(), $faqEntity->getLanguage());

        $this->assertTrue($result);
    }

    public function testGetSolutionIdFromId(): void
    {
        $faqEntity = $this->getFaqEntity();
        $faqEntity->setId($this->faq->create($faqEntity)->getId());

        $this->assertIsInt($this->faq->getSolutionIdFromId($faqEntity->getId(), $faqEntity->getLanguage()));
        $this->assertGreaterThan(0, $this->faq->getSolutionIdFromId($faqEntity->getId(), $faqEntity->getLanguage()));
    }

    public function testHasTranslation(): void
    {
        $faqEntity = $this->getFaqEntity();
        $faqEntity->setId($this->faq->create($faqEntity)->getId());

        $this->assertTrue($this->faq->hasTranslation($faqEntity->getId(), $faqEntity->getLanguage()));
        $this->assertFalse($this->faq->hasTranslation($faqEntity->getId(), 'de'));
    }

    public function testIsActive(): void
    {
        $faqEntity = $this->getFaqEntity();
        $faqEntity->setId($this->faq->create($faqEntity)->getId());

        $this->assertTrue($this->faq->isActive($faqEntity->getId(), $faqEntity->getLanguage()));
    }

    public function testGetRecordBySolutionId(): void
    {
        $faqEntity = $this->getFaqEntity();
        $faqEntity->setSolutionId(42);
        $faqEntity = $this->faq->create($faqEntity);

        // Fetch record by solution id and validate it matches the created one
        $record = $this->faq->getIdFromSolutionId(42);
        $this->assertNotEmpty($record);
        $this->assertIsArray($record);
        $this->assertGreaterThan(0, (int) $record['id']);
        $this->assertEquals($faqEntity->getId(), (int) $record['id']);

        // Also verify the solution_id is actually 42 via getFaqBySolutionId
        $this->faq->getFaqBySolutionId(42);
        $this->assertArrayHasKey('solution_id', $this->faq->faqRecord);
        $this->assertEquals(42, (int) $this->faq->faqRecord['solution_id']);
    }

    public function testGetFaqReturnsInactiveMessageForFrontendAndRawContentForAdmin(): void
    {
        $this->seedFaqRecord(id: 5001, solutionId: 7001, active: 'no', answer: 'Inactive answer');

        $this->faq->getFaq(5001);
        $this->assertSame(Translation::get(key: 'err_inactiveArticle'), $this->faq->faqRecord['content']);

        $this->faq->getFaq(5001, null, true);
        $this->assertSame('Inactive answer', $this->faq->faqRecord['content']);
    }

    public function testGetFaqReturnsExpiredMessageForFrontend(): void
    {
        $this->seedFaqRecord(id: 5002, solutionId: 7002, dateEnd: '20000101000000', answer: 'Expired answer');

        $this->faq->getFaq(5002);

        $this->assertSame(Translation::get(key: 'err_expiredArticle'), $this->faq->faqRecord['content']);
    }

    public function testGetFaqFallsBackToDefaultLanguage(): void
    {
        $this->seedFaqRecord(id: 5003, solutionId: 7003, lang: 'en', question: 'English Question');
        Language::$language = 'de';

        $this->faq->getFaq(5003);

        $this->assertSame('English Question', strip_tags((string) $this->faq->faqRecord['title']));
        $this->assertSame('en', $this->faq->faqRecord['lang']);
    }

    public function testGetFaqBySolutionIdFallsBackWithoutPermissionFilter(): void
    {
        $this->seedFaqRecord(id: 5004, solutionId: 7004, question: 'Fallback question', userId: null);

        $this->faq->getFaqBySolutionId(7004);

        $this->assertSame(5004, (int) $this->faq->faqRecord['id']);
        $this->assertSame(7004, (int) $this->faq->faqRecord['solution_id']);
        $this->assertSame('Fallback question', strip_tags((string) $this->faq->faqRecord['title']));
    }

    public function testGetQuestionAndKeywordsUseCachedRecordWhenAvailable(): void
    {
        $this->faq->faqRecord = [
            'id' => 5005,
            'title' => 'Cached Question',
            'keywords' => 'cached,keywords',
        ];

        $this->assertSame('Cached Question', $this->faq->getQuestion(5005));
        $this->assertSame('cached,keywords', $this->faq->getKeywords(5005));
    }

    public function testGetQuestionAndKeywordsReadDatabaseAndMissingQuestionFallsBackToTranslation(): void
    {
        $this->seedFaqRecord(id: 5006, solutionId: 7006, keywords: 'alpha & beta', question: 'Database Question');

        $this->assertSame('Database Question', $this->faq->getQuestion(5006));
        $this->assertSame('alpha &amp; beta', $this->faq->getKeywords(5006));
        $this->assertSame(Translation::get(key: 'no_cats'), $this->faq->getQuestion(999999));
    }

    public function testGetFaqsByIdsAndGetFaqByIdAndCategoryIdReturnMappedData(): void
    {
        $this->seedFaqRecord(
            id: 5007,
            solutionId: 7007,
            categoryId: 77,
            question: 'Mapped FAQ',
            answer: 'Mapped answer',
        );

        $records = $this->faq->getFaqsByIds([5007]);
        $record = $this->faq->getFaqByIdAndCategoryId(5007, 77);

        $this->assertCount(1, $records);
        $this->assertSame(5007, $records[0]['record_id']);
        $this->assertStringContainsString('/content/77/5007/en/mapped-faq.html', $records[0]['record_link']);
        $this->assertSame(5007, $record['id']);
        $this->assertSame(77, $record['category_id']);
        $this->assertStringContainsString('/content/77/5007/en/mapped-faq.html', $record['link']);
        $this->assertSame([], $this->faq->getIdFromSolutionId(999999));
    }

    public function testGetAllAvailableFaqsByCategoryIdReturnsPreviewAndFullData(): void
    {
        $this->seedFaqRecord(
            id: 5008,
            solutionId: 7008,
            categoryId: 88,
            question: 'Preview FAQ',
            answer: 'A long answer for previews',
        );

        $previewData = $this->faq->getAllAvailableFaqsByCategoryId(88);
        $fullData = $this->faq->getAllAvailableFaqsByCategoryId(88, 'visits', 'DESC', false);

        $this->assertCount(1, $previewData);
        $this->assertSame('Preview FAQ', $previewData[0]['record_title']);
        $this->assertNotSame('', $previewData[0]['record_preview']);
        $this->assertStringContainsString('/content/88/5008/en/preview-faq.html', $previewData[0]['record_link']);

        $this->assertCount(1, $fullData);
        $this->assertSame('Preview FAQ', $fullData[0]['question']);
        $this->assertSame('A long answer for previews', $fullData[0]['answer']);
        $this->assertStringContainsString('/content/88/5008/en/preview-faq.html', $fullData[0]['link']);
    }

    public function testRenderFaqsByFaqIdsReturnsDistinctResultsWithoutPagination(): void
    {
        $this->seedFaqRecord(
            id: 5009,
            solutionId: 7009,
            categoryId: 91,
            question: 'Duplicate FAQ',
            answer: 'Duplicated once',
        );
        $this->configuration
            ->getDb()
            ->query(
                "INSERT INTO faqcategoryrelations (category_id, category_lang, record_id, record_lang) VALUES (92, 'en', 5009, 'en')",
            );

        $results = $this->faq->renderFaqsByFaqIds([5009], usePagination: false);

        $this->assertCount(1, $results);
        $this->assertSame('Duplicate FAQ', trim($results[0]->question));
        $this->assertStringContainsString('/content/91/5009/en/duplicate-faq.html', $results[0]->url);
        $this->assertNotSame('', $results[0]->answerPreview);
    }

    public function testGetReturnsMappedFaqRows(): void
    {
        $this->seedFaqRecord(
            id: 5010,
            solutionId: 7010,
            categoryId: 93,
            question: 'Mapped row',
            answer: 'Mapped content',
        );

        $rows = $this->faq->get(categoryId: 93, lang: 'en');

        $this->assertCount(1, $rows);
        $this->assertSame(5010, $rows[0]['id']);
        $this->assertSame(7010, $rows[0]['solution_id']);
        $this->assertSame('Mapped row', $rows[0]['topic']);
        $this->assertSame('Mapped content', $rows[0]['content']);
    }

    public function testGetStickyFaqsDataSortsByCustomOrderAndSkipsDuplicateFaqs(): void
    {
        $this->configuration->set('records.orderStickyFaqsCustom', 'true');
        $this->setConfigurationValue('records.orderStickyFaqsCustom', 'true');
        $this->seedFaqRecord(
            id: 5011,
            solutionId: 7011,
            categoryId: 101,
            question: 'Second sticky',
            sticky: 1,
            stickyOrder: 2,
            visits: 10,
        );
        $this->seedFaqRecord(
            id: 5012,
            solutionId: 7012,
            categoryId: 102,
            question: 'First sticky',
            sticky: 1,
            stickyOrder: 1,
            visits: 5,
        );
        $this->configuration
            ->getDb()
            ->query(
                "INSERT INTO faqcategoryrelations (category_id, category_lang, record_id, record_lang) VALUES (103, 'en', 5012, 'en')",
            );

        $sticky = $this->faq->getStickyFaqsData();

        $this->assertCount(2, $sticky);
        $this->assertSame(5012, $sticky[0]['id']);
        $this->assertSame(5011, $sticky[1]['id']);
        $this->assertStringContainsString('/content/102/5012/en/first-sticky.html', $sticky[0]['url']);
    }

    public function testGetAllFaqsBuildsRecordsAndReplacesInactiveAndExpiredContent(): void
    {
        $this->seedFaqRecord(
            id: 5013,
            solutionId: 7013,
            categoryId: 111,
            question: 'Inactive FAQ',
            answer: 'Should be hidden',
            active: 'no',
        );
        $this->seedFaqRecord(
            id: 5014,
            solutionId: 7014,
            categoryId: 112,
            question: 'Expired FAQ',
            answer: 'Should expire',
            dateEnd: '20000101000000',
        );
        $this->seedFaqRecord(
            id: 5015,
            solutionId: 7015,
            categoryId: 113,
            question: 'Visible FAQ',
            answer: 'Visible answer',
            notes: 'some note',
        );

        $this->faq->getAllFaqs(Faq::SORTING_TYPE_FAQID, ['fd.id' => ['5015']], 'ASC');

        $this->assertCount(1, $this->faq->faqRecords);
        $this->assertSame(5015, $this->faq->faqRecords[0]['id']);

        $this->faq->faqRecords = [];
        $this->faq->getAllFaqs(Faq::SORTING_TYPE_FAQID, ['fd.id' => ['5013', '5014']], 'ASC');

        $this->assertCount(2, $this->faq->faqRecords);
        $this->assertSame(Translation::get(key: 'err_inactiveArticle'), $this->faq->faqRecords[0]['content']);
        $this->assertSame(Translation::get(key: 'err_expiredArticle'), $this->faq->faqRecords[1]['content']);
    }

    public function testGetSolutionIdFromIdReturnsNextSolutionIdWhenRecordIsMissing(): void
    {
        $next = $this->faq->getNextSolutionId();

        $this->assertSame($next, $this->faq->getSolutionIdFromId(999998, 'en'));
    }

    public function testIsActiveHandlesNewsAndMissingRecords(): void
    {
        $this->configuration
            ->getDb()
            ->query(
                "INSERT INTO faqnews (id, lang, header, artikel, datum, author_name, author_email, active, comment, link, linktitel, target)
             VALUES (6001, 'en', 'News header', 'News article', '20260301000000', 'Author', 'author@example.com', 'n', 'n', '', '', '_self')",
            );

        $this->assertFalse($this->faq->isActive(6001, 'en', 'news'));
        $this->assertFalse($this->faq->isActive(999997, 'en'));
    }

    private function getFaqEntity(): FaqEntity
    {
        $faqEntity = new FaqEntity();
        $faqEntity
            ->setRevisionId(0)
            ->setLanguage('en')
            ->setActive(true)
            ->setSticky(true)
            ->setKeywords('Keywords')
            ->setQuestion('Question')
            ->setAnswer('Answer')
            ->setAuthor('Author')
            ->setEmail('foo@bar.baz')
            ->setComment(true)
            ->setNotes('')
            ->setUpdatedDate(new DateTime());

        return $faqEntity;
    }

    private function seedFaqRecord(
        int $id,
        int $solutionId,
        int $categoryId = 1,
        string $lang = 'en',
        string $active = 'yes',
        int $sticky = 0,
        int $stickyOrder = 0,
        string $question = 'Question',
        string $answer = 'Answer',
        string $keywords = 'Keywords',
        string $dateEnd = '99991231235959',
        int $visits = 1,
        ?int $userId = -1,
        string $notes = '',
    ): void {
        $question = $this->configuration->getDb()->escape($question);
        $answer = $this->configuration->getDb()->escape($answer);
        $keywords = $this->configuration->getDb()->escape($keywords);
        $notes = $this->configuration->getDb()->escape($notes);

        $this->configuration
            ->getDb()
            ->query(sprintf(
                "INSERT INTO faqdata (id, lang, solution_id, revision_id, active, sticky, keywords, thema, content, author, email, comment, updated, date_start, date_end, created, notes, sticky_order)
                 VALUES (%d, '%s', %d, 0, '%s', %d, '%s', '%s', '%s', 'Author', 'author@example.com', 'y', '20260301010101', '00000000000000', '%s', '2026-03-01 01:01:01', '%s', %d)",
                $id,
                $lang,
                $solutionId,
                $active,
                $sticky,
                $keywords,
                $question,
                $answer,
                $dateEnd,
                $notes,
                $stickyOrder,
            ));
        $this->configuration
            ->getDb()
            ->query(sprintf(
                "INSERT INTO faqcategoryrelations (category_id, category_lang, record_id, record_lang) VALUES (%d, '%s', %d, '%s')",
                $categoryId,
                $lang,
                $id,
                $lang,
            ));
        $this->configuration
            ->getDb()
            ->query(sprintf(
                "INSERT INTO faqvisits (id, lang, visits, last_visit) VALUES (%d, '%s', %d, 20260301010101)",
                $id,
                $lang,
                $visits,
            ));

        if ($userId !== null) {
            $this->configuration
                ->getDb()
                ->query(sprintf('INSERT INTO faqdata_user (record_id, user_id) VALUES (%d, %d)', $id, $userId));
        }
    }

    private function resetConfigurationSingleton(): void
    {
        $reflectionProperty = new ReflectionProperty(Configuration::class, 'configuration');
        $reflectionProperty->setValue(null, null);
    }

    private function setConfigurationValue(string $key, mixed $value): void
    {
        $reflectionProperty = new ReflectionProperty(Configuration::class, 'config');
        $config = $reflectionProperty->getValue($this->configuration);
        $config[$key] = $value;
        $reflectionProperty->setValue($this->configuration, $config);
    }
}
