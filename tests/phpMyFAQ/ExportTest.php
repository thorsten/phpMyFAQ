<?php

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Export\Json;
use phpMyFAQ\Export\Pdf;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

class ExportTest extends TestCase
{
    private Configuration $configuration;
    private Faq $faq;
    private Category $category;

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
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

        $language = new Language($this->configuration, $this->createMock(Session::class));
        $this->configuration->setLanguage($language);

        $this->faq = new Faq($this->configuration);
        $this->category = new Category($this->configuration);
    }

    /**
     * @throws Exception
     */
    public function testCreatePdf(): void
    {
        $pdf = Export::create($this->faq, $this->category, $this->configuration);
        $this->assertInstanceOf(Pdf::class, $pdf);
    }

    /**
     * @throws Exception
     */
    public function testCreateJson(): void
    {
        $json = Export::create($this->faq, $this->category, $this->configuration, 'json');
        $this->assertInstanceOf(Json::class, $json);
    }

    public function testCreateInvalidMode(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Export not implemented!');
        Export::create($this->faq, $this->category, $this->configuration, 'invalid');
    }

    public function testGetExportTimestamp(): void
    {
        $timestamp = Export::getExportTimestamp();
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2}$/', $timestamp);
    }
}

