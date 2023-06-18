<?php

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;

class FaqTest extends TestCase
{
    /** @var Configuration */
    private Configuration $configuration;

    /** @var Faq */
    private Faq $faq;

    /**
     * @throws Exception
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

        $language = new Language($this->configuration);
        $this->configuration->setLanguage($language);

        $this->faq = new Faq($this->configuration);
    }

    public function testSetGroups(): void
    {
        $this->assertInstanceOf(Faq::class, $this->faq->setGroups([-1]));
    }

    public function testSetUser(): void
    {
        $this->assertInstanceOf(Faq::class, $this->faq->setUser(-1));
    }
}
