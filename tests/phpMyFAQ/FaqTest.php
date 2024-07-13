<?php

namespace phpMyFAQ;

use DateTime;
use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\Filesystem\File\FileException;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\FaqEntity;
use PHPUnit\Framework\TestCase;

class FaqTest extends TestCase
{
    /** @var Configuration */
    private Configuration $configuration;

    /** @var Faq */
    private Faq $faq;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Translation::create()
            ->setLanguagesDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
        $this->configuration->set('main.currentVersion', System::getVersion());

        $language = new Language($this->configuration);
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

        $faqEntity = $this->getFaqEntity();
        $this->faq->deleteRecord(1, $faqEntity->getLanguage());
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

        $result = $this->faq->deleteRecord($faqEntity->getId(), $faqEntity->getLanguage());

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
        $this->faq->create($faqEntity);

        $this->faq->getRecordBySolutionId(42);

        $this->assertEquals(1, $faqEntity->getId());
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
}
