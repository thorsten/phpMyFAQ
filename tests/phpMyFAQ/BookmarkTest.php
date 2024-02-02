<?php


namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\TestCase;


class BookmarkTest extends TestCase
{
    private Bookmark $bookmark;

    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($dbHandle);
        $user = CurrentUser::getCurrentUser($configuration);
        $language = new Language($configuration);
        $language->setLanguage(false, 'en');
        $configuration->setLanguage($language);

        $this->bookmark = new Bookmark($configuration, $user);
    }

    public function testSaveFaqAsBookmarkById(): void
    {
        $result = $this->bookmark->saveFaqAsBookmarkById(1);
        $this->assertTrue($result);
    }

    public function testIsFaqBookmark(): void
    {
        $this->bookmark->saveFaqAsBookmarkById(1);
        $result = $this->bookmark->isFaqBookmark(1);
        $this->assertTrue($result);
    }
}
