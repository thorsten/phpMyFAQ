<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class BookmarkTest extends TestCase
{
    private Bookmark $bookmark;

    /**
     * @throws Exception
     * @throws Core\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        Translation::create()
            ->setLanguagesDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $_SERVER['HTTP_HOST'] = 'example.com';

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($dbHandle);
        $configuration->set('main.referenceURL', 'http://example.com');

        $user = CurrentUser::getCurrentUser($configuration);
        $language = new Language($configuration, $this->createMock(Session::class));
        $language->setLanguage(false, 'en');
        $configuration->setLanguage($language);

        $this->bookmark = new Bookmark($configuration, $user);
    }

    public function testSaveFaqAsBookmarkById(): void
    {
        $result = $this->bookmark->add(1);
        $this->assertTrue($result);

        // Clean up
        $this->bookmark->remove(1);
    }

    public function testIsFaqBookmark(): void
    {
        $this->bookmark->add(1);
        $result = $this->bookmark->isFaqBookmark(1);
        $this->assertTrue($result);

        // Clean up
        $this->bookmark->remove(1);
    }

    public function testGetAll(): void
    {
        $this->bookmark->add(1);
        $result = $this->bookmark->getAll();
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));

        // Clean up
        $this->bookmark->remove(1);
    }

    public function testRemove(): void
    {
        $this->bookmark->add(1);
        $this->assertTrue($this->bookmark->remove(1));
    }

    public function testRenderBookmarkTree(): void
    {
        $this->bookmark->add(1);
        $result = $this->bookmark->getBookmarkList();
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));

        // Clean up
        $this->bookmark->remove(1);
    }
}
