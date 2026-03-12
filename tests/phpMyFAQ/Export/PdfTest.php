<?php

declare(strict_types=1);

namespace phpMyFAQ\Export;

use phpMyFAQ\Auth;
use phpMyFAQ\Auth\AuthDatabase;
use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\ConfigurationRepository;
use phpMyFAQ\Configuration\LayoutSettings;
use phpMyFAQ\Configuration\LdapSettings;
use phpMyFAQ\Configuration\MailSettings;
use phpMyFAQ\Configuration\SearchSettings;
use phpMyFAQ\Configuration\SecuritySettings;
use phpMyFAQ\Configuration\Storage\ConfigurationStorageSettings;
use phpMyFAQ\Configuration\Storage\ConfigurationStorageSettingsResolver;
use phpMyFAQ\Configuration\Storage\DatabaseConfigurationStore;
use phpMyFAQ\Configuration\Storage\HybridConfigurationStore;
use phpMyFAQ\Configuration\UrlSettings;
use phpMyFAQ\ConfigurationMethodsTrait;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Date;
use phpMyFAQ\Encryption;
use phpMyFAQ\Environment;
use phpMyFAQ\Faq;
use phpMyFAQ\Link\Util\TitleSlugifier;
use phpMyFAQ\Permission;
use phpMyFAQ\Permission\BasicPermission;
use phpMyFAQ\Permission\BasicPermissionRepository;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\Permission\MediumPermissionRepository;
use phpMyFAQ\Plugin\PluginManager;
use phpMyFAQ\Session\SessionWrapper;
use phpMyFAQ\Strings;
use phpMyFAQ\Strings\AbstractString;
use phpMyFAQ\Strings\Mbstring;
use phpMyFAQ\System;
use phpMyFAQ\Tags;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\UserData;
use phpMyFAQ\User\UserSession;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Pdf::class)]
#[UsesClass(Pdf\Wrapper::class)]
#[UsesClass(Translation::class)]
#[UsesClass(Auth::class)]
#[UsesClass(AuthDatabase::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(ConfigurationRepository::class)]
#[UsesClass(LayoutSettings::class)]
#[UsesClass(LdapSettings::class)]
#[UsesClass(MailSettings::class)]
#[UsesClass(SearchSettings::class)]
#[UsesClass(SecuritySettings::class)]
#[UsesClass(ConfigurationStorageSettings::class)]
#[UsesClass(ConfigurationStorageSettingsResolver::class)]
#[UsesClass(DatabaseConfigurationStore::class)]
#[UsesClass(HybridConfigurationStore::class)]
#[UsesClass(UrlSettings::class)]
#[UsesClass(Database::class)]
#[UsesClass(Sqlite3::class)]
#[UsesClass(Date::class)]
#[UsesClass(Encryption::class)]
#[UsesClass(Environment::class)]
#[UsesClass(TitleSlugifier::class)]
#[UsesClass(Permission::class)]
#[UsesClass(BasicPermission::class)]
#[UsesClass(BasicPermissionRepository::class)]
#[UsesClass(MediumPermission::class)]
#[UsesClass(MediumPermissionRepository::class)]
#[UsesClass(PluginManager::class)]
#[UsesClass(SessionWrapper::class)]
#[UsesClass(Strings::class)]
#[UsesClass(AbstractString::class)]
#[UsesClass(Mbstring::class)]
#[UsesClass(System::class)]
#[UsesClass(Tags::class)]
#[UsesClass(User::class)]
#[UsesClass(CurrentUser::class)]
#[UsesClass(UserData::class)]
#[UsesClass(UserSession::class)]
#[AllowMockObjectsWithoutExpectations]
final class PdfTest extends TestCase
{
    private Configuration $configuration;
    private Faq $faq;
    private Category $category;

    protected function setUp(): void
    {
        Strings::init();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->configuration = $this->createMock(Configuration::class);
        $this->configuration->method('getTitle')->willReturn('Test FAQ');
        $this->configuration->method('getDefaultUrl')->willReturn('https://example.com/');
        $this->configuration->method('getAdminEmail')->willReturn('admin@example.com');
        $this->configuration
            ->method('get')
            ->willReturnCallback(static fn(string $key) => match ($key) {
                'main.enableMarkdownEditor' => false,
                'main.customPdfHeader' => '',
                'main.customPdfFooter' => '',
                'main.metaPublisher' => 'Test Publisher',
                'main.dateFormat' => 'Y-m-d H:i',
                'spam.mailAddressInExport' => false,
                default => null,
            });

        $this->faq = $this->createMock(Faq::class);
        $this->category = $this->createMock(Category::class);
        $this->category->method('getAllCategories')->willReturn([]);
    }

    public function testConstructorInitializesPdfWrapper(): void
    {
        $pdf = new Pdf($this->faq, $this->category, $this->configuration);

        self::assertInstanceOf(Pdf::class, $pdf);
    }

    public function testGenerateWithEmptyFaqData(): void
    {
        $this->faq->method('get')->willReturn([]);

        $pdf = new Pdf($this->faq, $this->category, $this->configuration);
        $result = $pdf->generate();

        self::assertNotEmpty($result);
        self::assertStringStartsWith('%PDF', $result);
    }

    public function testGenerateWithFaqDataProducesPdf(): void
    {
        $this->category->method('getCategoryName')->willReturn('Test Category');
        $this->category->method('getLevelOf')->willReturn(1);

        $this->faq
            ->method('get')
            ->willReturn([
                [
                    'id' => 1,
                    'category_id' => 1,
                    'topic' => 'Test Question',
                    'content' => '<p>Test Answer</p>',
                    'keywords' => 'test, faq',
                    'lastmodified' => '2026-01-01',
                ],
            ]);

        $pdf = new Pdf($this->faq, $this->category, $this->configuration);
        $result = $pdf->generate();

        self::assertStringStartsWith('%PDF', $result);
    }

    public function testGenerateWithMultipleCategoriesAddsBookmarks(): void
    {
        $this->category->method('getCategoryName')->willReturn('Category');
        $this->category->method('getLevelOf')->willReturn(1);

        $this->faq
            ->method('get')
            ->willReturn([
                [
                    'id' => 1,
                    'category_id' => 1,
                    'topic' => 'First FAQ',
                    'content' => 'First answer',
                    'keywords' => 'first',
                    'lastmodified' => '2026-01-01',
                ],
                [
                    'id' => 2,
                    'category_id' => 2,
                    'topic' => 'Second FAQ',
                    'content' => 'Second answer',
                    'keywords' => 'second',
                    'lastmodified' => '2026-01-02',
                ],
            ]);

        $pdf = new Pdf($this->faq, $this->category, $this->configuration);
        $result = $pdf->generate();

        self::assertStringStartsWith('%PDF', $result);
    }

    public function testGenerateWithMarkdownEnabled(): void
    {
        $config = $this->createMock(Configuration::class);
        $config->method('getTitle')->willReturn('Test FAQ');
        $config->method('getDefaultUrl')->willReturn('https://example.com/');
        $config->method('getAdminEmail')->willReturn('admin@example.com');
        $config
            ->method('get')
            ->willReturnCallback(static fn(string $key) => match ($key) {
                'main.enableMarkdownEditor' => true,
                'main.customPdfHeader' => '',
                'main.customPdfFooter' => '',
                'main.metaPublisher' => 'Test',
                'main.dateFormat' => 'Y-m-d H:i',
                'spam.mailAddressInExport' => false,
                default => null,
            });

        $this->category->method('getCategoryName')->willReturn('Category');
        $this->category->method('getLevelOf')->willReturn(1);

        $this->faq
            ->method('get')
            ->willReturn([
                [
                    'id' => 1,
                    'category_id' => 1,
                    'topic' => 'Markdown FAQ',
                    'content' => '**Bold** and *italic* text',
                    'keywords' => 'markdown',
                    'lastmodified' => '2026-01-01',
                ],
            ]);

        $pdf = new Pdf($this->faq, $this->category, $config);
        $result = $pdf->generate();

        self::assertStringStartsWith('%PDF', $result);
    }

    public function testGenerateWithCustomParameters(): void
    {
        $this->faq->method('get')->willReturn([]);

        $pdf = new Pdf($this->faq, $this->category, $this->configuration);
        $result = $pdf->generate(categoryId: 5, downwards: false, language: 'en');

        self::assertStringStartsWith('%PDF', $result);
    }

    public function testGenerateFileProducesPdf(): void
    {
        $config = $this->createRealConfiguration();

        $category = $this->createMock(Category::class);
        $category->method('getAllCategories')->willReturn([]);

        $faqData = [
            'id' => 42,
            'lang' => 'en',
            'category_id' => 1,
            'title' => 'How to test PDFs?',
            'content' => '<p>Use PHPUnit</p>',
            'solution_id' => 1001,
            'email' => 'author@example.com',
            'author' => 'Test Author',
            'date' => '2026-01-15 10:30:00',
        ];

        $pdf = new Pdf($this->createMock(Faq::class), $category, $config);
        $result = $pdf->generateFile($faqData);

        self::assertStringStartsWith('%PDF', $result);
    }

    public function testGenerateFileWithCustomFilename(): void
    {
        $config = $this->createRealConfiguration();

        $category = $this->createMock(Category::class);
        $category->method('getAllCategories')->willReturn([]);

        $faqData = [
            'id' => 1,
            'lang' => 'en',
            'category_id' => 1,
            'title' => 'Custom PDF',
            'content' => 'Content',
            'solution_id' => 100,
            'email' => 'test@example.com',
            'author' => 'Author',
            'date' => '2026-01-01 00:00:00',
        ];

        $pdf = new Pdf($this->createMock(Faq::class), $category, $config);
        $result = $pdf->generateFile($faqData, 'custom-file.pdf');

        self::assertStringStartsWith('%PDF', $result);
    }

    public function testGenerateFileWithDefaultFilenameFormat(): void
    {
        $config = $this->createRealConfiguration();

        $category = $this->createMock(Category::class);
        $category->method('getAllCategories')->willReturn([]);

        $faqData = [
            'id' => 99,
            'lang' => 'de',
            'category_id' => 1,
            'title' => 'German FAQ',
            'content' => 'German content',
            'solution_id' => 200,
            'email' => 'test@example.com',
            'author' => 'Author',
            'date' => '2026-02-01 12:00:00',
        ];

        $pdf = new Pdf($this->createMock(Faq::class), $category, $config);
        $result = $pdf->generateFile($faqData, null);

        self::assertStringStartsWith('%PDF', $result);
    }

    public function testGenerateFileWithEmptyFilename(): void
    {
        $config = $this->createRealConfiguration();

        $category = $this->createMock(Category::class);
        $category->method('getAllCategories')->willReturn([]);

        $faqData = [
            'id' => 50,
            'lang' => 'en',
            'category_id' => 1,
            'title' => 'FAQ Title',
            'content' => 'Content',
            'solution_id' => 300,
            'email' => 'test@example.com',
            'author' => 'Author',
            'date' => '2026-01-01 00:00:00',
        ];

        $pdf = new Pdf($this->createMock(Faq::class), $category, $config);
        $result = $pdf->generateFile($faqData, '');

        self::assertStringStartsWith('%PDF', $result);
    }

    public function testGenerateFileWithAttachments(): void
    {
        $config = $this->createRealConfiguration();

        $category = $this->createMock(Category::class);
        $category->method('getAllCategories')->willReturn([]);

        $faqData = [
            'id' => 10,
            'lang' => 'en',
            'category_id' => 1,
            'title' => 'FAQ with attachments',
            'content' => 'Main content',
            'solution_id' => 400,
            'email' => 'test@example.com',
            'author' => 'Author',
            'date' => '2026-01-01 00:00:00',
            'attachmentList' => [
                ['url' => 'https://example.com/file1.pdf', 'filename' => 'Document.pdf'],
                ['url' => 'https://example.com/file2.zip', 'filename' => 'Archive.zip'],
            ],
        ];

        $pdf = new Pdf($this->createMock(Faq::class), $category, $config);
        $result = $pdf->generateFile($faqData);

        self::assertStringStartsWith('%PDF', $result);
    }

    public function testGenerateFileWithMarkdownContent(): void
    {
        $config = $this->createRealConfiguration();

        $category = $this->createMock(Category::class);
        $category->method('getAllCategories')->willReturn([]);

        $faqData = [
            'id' => 1,
            'lang' => 'en',
            'category_id' => 1,
            'title' => 'Markdown FAQ',
            'content' => '# Heading\n\n**Bold text**',
            'solution_id' => 500,
            'email' => 'test@example.com',
            'author' => 'Author',
            'date' => '2026-01-01 00:00:00',
        ];

        $pdf = new Pdf($this->createMock(Faq::class), $category, $config);
        $result = $pdf->generateFile($faqData);

        self::assertStringStartsWith('%PDF', $result);
    }

    private function createRealConfiguration(): Configuration
    {
        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        Database::setTablePrefix('');

        $config = new Configuration($dbHandle);
        $config->getAll();

        return $config;
    }
}
