<?php

namespace phpMyFAQ\Bookmark;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\MockObject\Exception as MockException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

class BookmarkFormatterTest extends TestCase
{
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
            ->setLanguagesDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
        $this->configuration->set('main.referenceURL', 'https://example.com');

        $this->currentUser = CurrentUser::getCurrentUser($this->configuration);
        $language = new Language($this->configuration, $this->createMock(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);

        $this->formatter = new BookmarkFormatter($this->configuration, $this->currentUser);
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
        $this->assertStringContainsString('index.php?action=faq', $result['url']);
    }

    public function testFormatReturnsNullWhenFaqIdMissingOrInvalid(): void
    {
        $this->assertNull($this->formatter->format((object) []));
        $this->assertNull($this->formatter->format((object) ['faqid' => 0]));
        $this->assertNull($this->formatter->format((object) ['faqid' => -5]));
    }
}

