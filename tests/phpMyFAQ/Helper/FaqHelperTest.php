<?php

namespace phpMyFAQ\Helper;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\System;
use PHPUnit\Framework\TestCase;

class FaqHelperTest extends TestCase
{
    /** @var Configuration */
    private Configuration $configuration;

    /** @var FaqHelper*/
    private FaqHelper $faqHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
        $this->configuration->set('main.currentVersion', System::getVersion());
        $this->configuration->set('main.referenceURL', 'https://localhost:443/');

        $this->faqHelper = new FaqHelper($this->configuration);
    }

    public function testRenderMarkupContent(): void
    {
        $this->assertEquals(
            '<div class="language-html">Foobar</div>',
            $this->faqHelper->renderMarkupContent('<div class="language-markup">Foobar</div>')
        );
    }

    public function testRewriteUrlFragments(): void
    {
        $content = '<a href="#Foobar">Hello, World</a>';
        $result = $this->faqHelper->rewriteUrlFragments($content, 'https://localhost:443/');

        $this->assertEquals(
            '<a href="https://localhost:443/#Foobar">Hello, World</a>',
            $result
        );
    }
    public function testCreateFaqUrl(): void
    {
        $faqEntity = new FaqEntity();
        $faqEntity
            ->setId(42)
            ->setLanguage('de');

        $this->assertEquals(
            'https://localhost:443/index.php?action=faq&cat=1&id=42&artlang=de',
            $this->faqHelper->createFaqUrl($faqEntity, 1)
        );
    }

    public function testCleanUpContent(): void
    {
        $content = '<p>Some text <script>alert("Hello, world!");</script><img src=foo onerror=alert(document.cookie)></p>';
        $expectedOutput = '<p>Some text <img src="foo"></p>';

        $actualOutput = $this->faqHelper->cleanUpContent($content);

        $this->assertEquals($expectedOutput, $actualOutput);
    }
}
