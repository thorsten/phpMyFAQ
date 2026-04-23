<?php

namespace phpMyFAQ;

use DateTime;
use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\Filesystem\File\FileException;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\FaqEntity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class FaqTest extends TestCase
{
    /** @var Configuration */
    private Configuration $configuration;

    /** @var Faq */
    private Faq $faq;

    /** @var array<int, array{id: int, lang: string}> */
    private array $createdFaqs = [];

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

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
        $this->configuration->set('main.currentVersion', System::getVersion());
        $this->configuration->set('security.permLevel', 'basic');

        $language = new Language($this->configuration, $this->createStub(Session::class));
        Language::$language = 'en';
        $this->configuration->setLanguage($language);

        $this->faq = new Faq($this->configuration);
    }

    /**
     * @throws AttachmentException
     * @throws FileException
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        foreach ($this->createdFaqs as $createdFaq) {
            if ($this->faq->hasTranslation($createdFaq['id'], $createdFaq['lang'])) {
                $this->faq->delete($createdFaq['id'], $createdFaq['lang']);
            }
        }

        $this->createdFaqs = [];
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
        $result = $this->createTrackedFaq($faqEntity);

        // Assert that the method returns an integer
        $this->assertIsInt($result->getId());
        $this->assertGreaterThan(0, $result->getId());
    }

    public function testGetNextSolutionId(): void
    {
        $this->assertIsInt($this->faq->getNextSolutionId());
        $this->assertGreaterThan(0, $this->faq->getNextSolutionId());

        $this->createTrackedFaq($this->getFaqEntity());

        $this->assertGreaterThan(1, $this->faq->getNextSolutionId());
    }

    public function testUpdate(): void
    {
        $faqEntity = $this->getFaqEntity();

        $faqEntity = $this->createTrackedFaq($faqEntity);

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
        $faqEntity->setId($this->createTrackedFaq($faqEntity)->getId());

        $result = $this->faq->delete($faqEntity->getId(), $faqEntity->getLanguage());

        $this->assertTrue($result);
    }

    public function testGetSolutionIdFromId(): void
    {
        $faqEntity = $this->getFaqEntity();
        $faqEntity->setId($this->createTrackedFaq($faqEntity)->getId());

        $this->assertIsInt($this->faq->getSolutionIdFromId($faqEntity->getId(), $faqEntity->getLanguage()));
        $this->assertGreaterThan(0, $this->faq->getSolutionIdFromId($faqEntity->getId(), $faqEntity->getLanguage()));
    }

    public function testHasTranslation(): void
    {
        $faqEntity = $this->getFaqEntity();
        $faqEntity->setId($this->createTrackedFaq($faqEntity)->getId());

        $this->assertTrue($this->faq->hasTranslation($faqEntity->getId(), $faqEntity->getLanguage()));
        $this->assertFalse($this->faq->hasTranslation($faqEntity->getId(), 'de'));
    }

    public function testIsActive(): void
    {
        $faqEntity = $this->getFaqEntity();
        $faqEntity->setId($this->createTrackedFaq($faqEntity)->getId());

        $this->assertTrue($this->faq->isActive($faqEntity->getId(), $faqEntity->getLanguage()));
    }

    public function testGetRecordBySolutionIdReturnsVisiblePublicFaq(): void
    {
        $faqEntity = $this->createFaqWithSolutionId(42);
        $this->grantPublicAccess($faqEntity);

        $record = $this->faq->getIdFromSolutionId(42);
        $this->assertNotEmpty($record);
        $this->assertIsArray($record);
        $this->assertGreaterThan(0, (int) $record['id']);
        $this->assertEquals($faqEntity->getId(), (int) $record['id']);
        $this->assertSame(1, (int) $record['category_id']);

        $this->faq->getFaqBySolutionId(42);
        $this->assertArrayHasKey('solution_id', $this->faq->faqRecord);
        $this->assertEquals(42, (int) $this->faq->faqRecord['solution_id']);
    }

    public function testGetRecordBySolutionIdDoesNotReturnFaqRestrictedToAnotherUser(): void
    {
        $faqEntity = $this->createFaqWithSolutionId(84);
        $this->grantUserAccess($faqEntity, 1);

        $this->assertSame([], $this->faq->getIdFromSolutionId(84));

        $this->faq->getFaqBySolutionId(84);

        $this->assertSame([], $this->faq->faqRecord);
    }

    public function testGetRecordBySolutionIdReturnsFaqForAuthorizedUser(): void
    {
        $faqEntity = $this->createFaqWithSolutionId(126);
        $this->grantUserAccess($faqEntity, 23);

        $this->faq->setUser(23);

        $record = $this->faq->getIdFromSolutionId(126);

        $this->assertNotEmpty($record);
        $this->assertSame($faqEntity->getId(), (int) $record['id']);
        $this->assertSame(1, (int) $record['category_id']);

        $this->faq->getFaqBySolutionId(126);

        $this->assertSame(126, (int) $this->faq->faqRecord['solution_id']);
        $this->assertSame($faqEntity->getQuestion(), strip_tags((string) $this->faq->faqRecord['title']));
    }

    public function testGetRecordBySolutionIdReturnsFaqForAuthorizedGroup(): void
    {
        $this->configuration->set('security.permLevel', 'medium');
        $groupConfiguration = new Configuration($this->configuration->getDb());
        $groupConfiguration->set('security.permLevel', 'medium');
        $groupConfiguration->setLanguage($this->configuration->getLanguage());
        $faq = new Faq($groupConfiguration);

        $faqEntity = $this->createFaqWithSolutionId(168, $faq);
        $this->grantGroupAccess($faqEntity, 7);

        $this->assertSame([], $faq->getIdFromSolutionId(168));

        $faq->setGroups([7]);

        $record = $faq->getIdFromSolutionId(168);

        $this->assertNotEmpty($record);
        $this->assertSame($faqEntity->getId(), (int) $record['id']);
        $this->assertSame(1, (int) $record['category_id']);

        $faq->getFaqBySolutionId(168);

        $this->assertSame(168, (int) $faq->faqRecord['solution_id']);
    }

    public function testGetAllAvailableFaqsByCategoryIdSanitizesLanguageAndSorting(): void
    {
        Language::$language = "en' OR 1=1 -- ";

        $result = $this->faq->getAllAvailableFaqsByCategoryId(
            1,
            'id DESC; DROP TABLE faqdata; --',
            'DESC; DROP TABLE faqdata; --',
        );

        $this->assertSame([], $result);
        $this->assertStringContainsString("en'' or 1=1 -- ", $this->configuration->getDb()->log());
        $this->assertStringContainsString('ORDER BY', $this->configuration->getDb()->log());
        $this->assertStringContainsString('fd.id ASC', $this->configuration->getDb()->log());
        $this->assertStringNotContainsString('DROP TABLE', $this->configuration->getDb()->log());
    }

    public function testGetFaqsByIdsNormalizesIdLists(): void
    {
        Language::$language = 'en';

        $result = $this->faq->getFaqsByIds(['1) OR 1=1 -- ', '2']);

        $this->assertSame([], $result);
        $this->assertStringContainsString('fd.id IN (1, 2)', $this->configuration->getDb()->log());
        $this->assertStringNotContainsString('OR 1=1', $this->configuration->getDb()->log());
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

    private function createFaqWithSolutionId(int $solutionId, ?Faq $faq = null): FaqEntity
    {
        $faq ??= $this->faq;

        $faqEntity = $this->getFaqEntity();
        $faqEntity->setSolutionId($solutionId);
        $faqEntity = $this->createTrackedFaq($faqEntity, $faq);
        $this->addCategoryRelation($faqEntity);

        return $faqEntity;
    }

    private function createTrackedFaq(FaqEntity $faqEntity, ?Faq $faq = null): FaqEntity
    {
        $faq ??= $this->faq;

        $createdFaq = $faq->create($faqEntity);
        $this->trackFaqForCleanup($createdFaq);

        return $createdFaq;
    }

    private function trackFaqForCleanup(FaqEntity $faqEntity): void
    {
        if (is_null($faqEntity->getId())) {
            return;
        }

        $this->createdFaqs[$faqEntity->getId()] = [
            'id' => $faqEntity->getId(),
            'lang' => $faqEntity->getLanguage(),
        ];
    }

    private function addCategoryRelation(FaqEntity $faqEntity, int $categoryId = 1): void
    {
        $this->configuration->getDb()->query(sprintf(
            "INSERT INTO faqcategoryrelations (category_id, category_lang, record_id, record_lang) VALUES (%d, '%s', %d, '%s')",
            $categoryId,
            $this->configuration->getDb()->escape($faqEntity->getLanguage()),
            $faqEntity->getId(),
            $this->configuration->getDb()->escape($faqEntity->getLanguage()),
        ));
    }

    private function grantPublicAccess(FaqEntity $faqEntity): void
    {
        $this->grantUserAccess($faqEntity, -1);
    }

    private function grantUserAccess(FaqEntity $faqEntity, int $userId): void
    {
        $this->configuration->getDb()->query(sprintf(
            'INSERT INTO faqdata_user (record_id, user_id) VALUES (%d, %d)',
            $faqEntity->getId(),
            $userId,
        ));
    }

    private function grantGroupAccess(FaqEntity $faqEntity, int $groupId): void
    {
        $this->configuration->getDb()->query(sprintf(
            'INSERT INTO faqdata_group (record_id, group_id) VALUES (%d, %d)',
            $faqEntity->getId(),
            $groupId,
        ));
    }
}
