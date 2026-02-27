<?php

namespace phpMyFAQ\Bookmark;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Database\PdoSqlite;
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

        $this->formatter = new BookmarkFormatter($this->configuration, $this->currentUser);
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
        $bookmark = (object) ['faqid' => 1];
        $result = $this->formatter->format($bookmark);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('answer', $result);
        $this->assertSame(1, $result['id']);
        $this->assertStringContainsString('/content/', $result['url']);
    }

    public function testFormatReturnsNullWhenFaqIdMissingOrInvalid(): void
    {
        $this->assertNull($this->formatter->format((object) []));
        $this->assertNull($this->formatter->format((object) ['faqid' => 0]));
        $this->assertNull($this->formatter->format((object) ['faqid' => -5]));
    }
}
