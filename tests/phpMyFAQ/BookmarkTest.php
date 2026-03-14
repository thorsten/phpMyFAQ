<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Database\PdoSqlite;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class BookmarkTest extends TestCase
{
    private Bookmark $bookmark;
    private DatabaseDriver $dbHandle;
    private string $dbPath;

    /**
     * @throws Exception
     * @throws Core\Exception
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

        $tempFile = tempnam(sys_get_temp_dir(), 'pmf-bookmark-');
        $this->assertNotFalse($tempFile);
        $this->dbPath = $tempFile;
        $this->assertTrue(copy(PMF_TEST_DIR . '/test.db', $this->dbPath));

        $this->dbHandle = new PdoSqlite();
        $this->dbHandle->connect($this->dbPath, '', '');
        $configuration = new Configuration($this->dbHandle);

        $user = CurrentUser::getCurrentUser($configuration);
        $language = new Language($configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $configuration->setLanguage($language);

        $this->bookmark = new Bookmark($configuration, $user);
    }

    protected function tearDown(): void
    {
        $this->dbHandle->close();
        if (isset($this->dbPath) && is_file($this->dbPath)) {
            unlink($this->dbPath);
        }

        parent::tearDown();
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

    public function testGetAllReturnsCachedBookmarksWithoutRepositoryLookup(): void
    {
        $reflection = new ReflectionClass(Bookmark::class);
        $property = $reflection->getProperty('bookmarkCache');
        $cachedBookmarks = [(object) ['faqid' => 42]];
        $property->setValue($this->bookmark, $cachedBookmarks);

        $this->assertSame($cachedBookmarks, $this->bookmark->getAll());
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

    public function testAddInvalidId(): void
    {
        $this->bookmark->removeAll();
        $this->assertFalse($this->bookmark->add(0));
        $this->assertFalse($this->bookmark->add(-5));
        $this->assertSame([], $this->bookmark->getAll());
    }

    public function testIsFaqBookmarkWithInvalidIdReturnsFalse(): void
    {
        $this->assertFalse($this->bookmark->isFaqBookmark(0));
        $this->assertFalse($this->bookmark->isFaqBookmark(-10));
    }

    public function testIsFaqBookmarkIgnoresBookmarksWithoutFaqIdProperty(): void
    {
        $reflection = new ReflectionClass(Bookmark::class);
        $property = $reflection->getProperty('bookmarkCache');
        $property->setValue($this->bookmark, [(object) ['bookmark' => 1]]);

        $this->assertFalse($this->bookmark->isFaqBookmark(1));
    }

    public function testRemoveInvalidId(): void
    {
        $this->assertFalse($this->bookmark->remove(0));
        $this->assertFalse($this->bookmark->remove(-2));
    }

    public function testRemoveNotExistingBookmark(): void
    {
        $this->bookmark->removeAll();
        $this->assertTrue($this->bookmark->remove(99999));
    }

    public function testRemoveAll(): void
    {
        $this->bookmark->removeAll();
        $this->bookmark->add(1);
        $this->bookmark->add(1);
        $this->assertTrue($this->bookmark->removeAll());
        $this->assertSame([], $this->bookmark->getAll());
    }

    public function testGetBookmarkListEmpty(): void
    {
        $this->bookmark->removeAll();
        $list = $this->bookmark->getBookmarkList();
        $this->assertIsArray($list);
        $this->assertCount(0, $list);
    }

    public function testCacheInvalidationOnAdd(): void
    {
        $this->bookmark->removeAll();
        $this->bookmark->getAll();
        $reflection = new ReflectionClass(Bookmark::class);
        $prop = $reflection->getProperty('bookmarkCache');
        $cached = $prop->getValue($this->bookmark);
        $this->assertIsArray($cached);

        $this->bookmark->add(1);
        $afterAddCache = $prop->getValue($this->bookmark);
        $this->assertNull($afterAddCache, 'Cache sollte nach add() invalidiert sein');

        $all = $this->bookmark->getAll();
        $this->assertNotNull($prop->getValue($this->bookmark));
        $this->assertCount(1, $all);

        $this->bookmark->remove(1);
    }

    public function testCacheInvalidationOnRemove(): void
    {
        $this->bookmark->removeAll();
        $this->bookmark->add(1);
        $this->bookmark->getAll();
        $reflection = new ReflectionClass(Bookmark::class);
        $prop = $reflection->getProperty('bookmarkCache');
        $this->assertIsArray($prop->getValue($this->bookmark));

        $this->bookmark->remove(1);
        $this->assertNull($prop->getValue($this->bookmark));
    }

    public function testCacheInvalidationOnRemoveAll(): void
    {
        $this->bookmark->removeAll();
        $this->bookmark->add(1);
        $this->bookmark->add(1);
        $this->bookmark->getAll();
        $reflection = new ReflectionClass(Bookmark::class);
        $prop = $reflection->getProperty('bookmarkCache');
        $this->assertIsArray($prop->getValue($this->bookmark));

        $this->bookmark->removeAll();
        $this->assertNull($prop->getValue($this->bookmark));
    }
}
