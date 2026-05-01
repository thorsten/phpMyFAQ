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
class BookmarkFormatterTest extends TestCase
{
    private const int TEST_FAQ_ID = 4242;
    private const int TEST_CATEGORY_ID = 4343;

    private Configuration $configuration;
    private CurrentUser $currentUser;
    private BookmarkFormatter $formatter;

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
        $this->configuration->set('records.allowedMediaHosts', 'example.com');

        $this->currentUser = CurrentUser::getCurrentUser($this->configuration);
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);

        $this->configuration->getDb()->query('INSERT INTO faqdata (
                id, lang, solution_id, revision_id, active, sticky, keywords, thema, content, author, email, comment,
                updated, date_start, date_end
            ) VALUES (
                ' . self::TEST_FAQ_ID . ", 'en', 1, 0, 'yes', 0, '', 'Test FAQ',
                '<p>Safe</p><script>alert(1)</script><img src=\"foo\" onerror=\"alert(2)\">', 'Tester',
                'tester@example.com', 'y', '20260501010101', '00000000000000', '99991231235959'
            )");
        $this->configuration->getDb()->query('INSERT INTO faqcategories (id, lang, parent_id, name, description, user_id, group_id, active, image, show_home)
             VALUES ('
        . self::TEST_CATEGORY_ID
        . ", 'en', 0, 'General', '', 1, -1, 1, '', 1)");
        $this->configuration->getDb()->query('INSERT INTO faqcategoryrelations (category_id, category_lang, record_id, record_lang)
             VALUES ('
        . self::TEST_CATEGORY_ID
        . ", 'en', "
        . self::TEST_FAQ_ID
        . ", 'en')");
        $this->configuration
            ->getDb()
            ->query('INSERT INTO faqdata_user (record_id, user_id) VALUES (' . self::TEST_FAQ_ID . ', -1)');

        $this->formatter = new BookmarkFormatter($this->configuration, $this->currentUser);
    }

    protected function tearDown(): void
    {
        $this->configuration
            ->getDb()
            ->query(
                'DELETE FROM faqcategoryrelations WHERE category_id = '
                . self::TEST_CATEGORY_ID
                . ' AND record_id = '
                . self::TEST_FAQ_ID
                . " AND category_lang = 'en' AND record_lang = 'en'",
            );
        $this->configuration
            ->getDb()
            ->query('DELETE FROM faqcategories WHERE id = ' . self::TEST_CATEGORY_ID . " AND lang = 'en'");
        $this->configuration
            ->getDb()
            ->query('DELETE FROM faqdata_user WHERE record_id = ' . self::TEST_FAQ_ID . ' AND user_id = -1');
        $this->configuration
            ->getDb()
            ->query('DELETE FROM faqdata WHERE id = ' . self::TEST_FAQ_ID . " AND lang = 'en'");

        parent::tearDown();
    }

    public function testFormatValidBookmark(): void
    {
        $bookmark = (object) ['faqid' => self::TEST_FAQ_ID];
        $result = $this->formatter->format($bookmark);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('answer', $result);
        $this->assertSame(self::TEST_FAQ_ID, $result['id']);
        $this->assertStringContainsString('index.php?action=faq', $result['url']);
        $this->assertSame('<p>Safe</p><img src="foo" />', $result['answer']);
    }

    public function testFormatReturnsNullWhenFaqIdMissingOrInvalid(): void
    {
        $this->assertNull($this->formatter->format((object) []));
        $this->assertNull($this->formatter->format((object) ['faqid' => 0]));
        $this->assertNull($this->formatter->format((object) ['faqid' => -5]));
    }
}
