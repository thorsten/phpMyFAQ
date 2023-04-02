<?php

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Export\Html5;
use phpMyFAQ\Export\Json;
use phpMyFAQ\Export\Pdf;
use phpMyFAQ\Language\Plurals;
use PHPUnit\Framework\TestCase;

class ExportTest extends TestCase
{
    private Configuration $configuration;
    private Faq $faq;
    private Category $category;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        global $plr;

        parent::setUp();

        Translation::create()
            ->setLanguagesDir(PMF_LANGUAGE_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $plr = new Plurals();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');

        $this->configuration = new Configuration($dbHandle);
        $this->configuration->config['main.currentVersion'] = System::getVersion();

        $language = new Language($this->configuration);
        $this->configuration->setLanguage($language);

        $this->faq = new Faq($this->configuration);
        $this->category = new Category($this->configuration);
    }

    /**
     * @throws Exception
     */
    public function testCreatePdf(): void
    {
        $pdf = Export::create($this->faq, $this->category, $this->configuration, 'pdf');
        $this->assertInstanceOf(Pdf::class, $pdf);
    }

    /**
     * @throws Exception
     */
    public function testCreateHtml5(): void
    {
        $html5 = Export::create($this->faq, $this->category, $this->configuration, 'html5');
        $this->assertInstanceOf(Html5::class, $html5);
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

