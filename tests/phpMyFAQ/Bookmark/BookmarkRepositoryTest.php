<?php

namespace phpMyFAQ\Bookmark;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception as MockException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class BookmarkRepositoryTest extends TestCase
{
    private Configuration $configuration;
    private CurrentUser $currentUser;
    private BookmarkRepository $repository;

    /**
     * @throws MockException
     * @throws \phpMyFAQ\Core\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
        $this->configuration->set('main.referenceURL', 'https://example.com');

        $this->currentUser = CurrentUser::getCurrentUser($this->configuration);
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);

        $this->repository = new BookmarkRepository($this->configuration, $this->currentUser);
    }

    public function testAddAndGetAllAndRemove(): void
    {
        // ensure clean state
        $this->repository->removeAll();

        $this->assertTrue($this->repository->add(1));
        $list = $this->repository->getAll();
        $this->assertIsArray($list);
        $this->assertNotEmpty($list);
        $this->assertObjectHasProperty('faqid', $list[0]);

        $this->assertTrue($this->repository->remove(1));
        $this->assertSame([], $this->repository->getAll());
    }

    public function testRemoveAll(): void
    {
        $this->repository->removeAll();
        $this->repository->add(1);
        $this->repository->add(1);
        $this->assertTrue($this->repository->removeAll());
        $this->assertSame([], $this->repository->getAll());
    }

    public function testInvalidIdsReturnFalseAndDoNothing(): void
    {
        $this->repository->removeAll();
        $this->assertFalse($this->repository->add(0));
        $this->assertFalse($this->repository->add(-1));
        $this->assertFalse($this->repository->remove(0));
        $this->assertFalse($this->repository->remove(-2));
        $this->assertSame([], $this->repository->getAll());
    }
}
