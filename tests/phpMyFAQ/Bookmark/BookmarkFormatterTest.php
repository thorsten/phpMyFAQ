<?php

namespace phpMyFAQ\Bookmark;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Database\PdoSqlite;
use phpMyFAQ\Enums\FaqStatus;
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
    private const int TEST_FAQ_ID = 1;

    private Configuration $configuration;
    private CurrentUser $currentUser;
    private BookmarkFormatter $formatter;
    private DatabaseDriver $dbHandle;
    private string $dbPath;

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

        $tempFile = tempnam(sys_get_temp_dir(), 'pmf-bookmarks-formatter-');
        $this->assertNotFalse($tempFile);
        $this->dbPath = $tempFile;
        $this->assertTrue(copy(PMF_TEST_DIR . '/test.db', $this->dbPath));

        $this->dbHandle = new PdoSqlite();
        $this->dbHandle->connect($this->dbPath, '', '');
        $this->configuration = new Configuration($this->dbHandle);

        $this->currentUser = CurrentUser::getCurrentUser($this->configuration);
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);

        $this->seedFaq(self::TEST_FAQ_ID);

        $this->formatter = new BookmarkFormatter($this->configuration, $this->currentUser);
    }

    private function seedFaq(int $faqId): void
    {
        $this->configuration->getDb()->query(sprintf(
            "INSERT INTO faqdata
                (id, lang, solution_id, revision_id, status, sticky, keywords, thema, content,
                 author, email, comment, updated, date_start, date_end)
             VALUES
                (%d, 'en', %d, 0, '%s', 0, '', 'Test FAQ', '<p>Safe</p>', 'Tester', 'tester@example.com', 'y',
                 '20260501010101', '00000000000000', '99991231235959')",
            $faqId,
            1000 + $faqId,
            FaqStatus::Published->value,
        ));
        $this->configuration
            ->getDb()
            ->query(sprintf('INSERT INTO faqdata_user (record_id, user_id) VALUES (%d, -1)', $faqId));
        $this->configuration
            ->getDb()
            ->query(sprintf('INSERT INTO faqdata_group (record_id, group_id) VALUES (%d, -1)', $faqId));
    }

    protected function tearDown(): void
    {
        $this->dbHandle->close();
        if (isset($this->dbPath) && is_file($this->dbPath)) {
            unlink($this->dbPath);
        }
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
        $this->assertStringContainsString('/content/', $result['url']);
    }

    public function testFormatReturnsNullForDraftFaq(): void
    {
        $this->configuration
            ->getDb()
            ->query("UPDATE faqdata SET status = 'draft' WHERE id = " . self::TEST_FAQ_ID . " AND lang = 'en'");

        $this->assertNull($this->formatter->format((object) ['faqid' => self::TEST_FAQ_ID]));
    }

    public function testFormatReturnsNullForNotYetPublishedFaq(): void
    {
        $this->configuration
            ->getDb()
            ->query(
                "UPDATE faqdata SET date_start = '99991231000000' WHERE id = " . self::TEST_FAQ_ID . " AND lang = 'en'",
            );

        $this->assertNull($this->formatter->format((object) ['faqid' => self::TEST_FAQ_ID]));
    }

    public function testFormatReturnsNullForNotPermittedFaq(): void
    {
        $this->configuration->getDb()->query('DELETE FROM faqdata_user WHERE record_id = ' . self::TEST_FAQ_ID);

        $this->assertNull($this->formatter->format((object) ['faqid' => self::TEST_FAQ_ID]));
    }

    public function testFormatReturnsNullWhenFaqIdMissingOrInvalid(): void
    {
        $this->assertNull($this->formatter->format((object) []));
        $this->assertNull($this->formatter->format((object) ['faqid' => 0]));
        $this->assertNull($this->formatter->format((object) ['faqid' => -5]));
    }
}
