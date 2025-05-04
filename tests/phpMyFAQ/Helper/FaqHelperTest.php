<?php

namespace phpMyFAQ\Helper;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;

class FaqHelperTest extends TestCase
{
    /** @var Configuration */
    private Configuration $configuration;

    /** @var FaqHelper*/
    private FaqHelper $faqHelper;

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
        $this->configuration->set('main.currentVersion', System::getVersion());
        $this->configuration->set('main.referenceURL', 'https://localhost:443/');

        $language = new Language($this->configuration);
        $this->configuration->setLanguage($language);

        $this->faqHelper = new FaqHelper($this->configuration);
    }

    public function testRewriteLanguageMarkupClass(): void
    {
        $this->assertEquals(
            '<div class="language-html">Foobar</div>',
            $this->faqHelper->rewriteLanguageMarkupClass('<div class="language-markup">Foobar</div>')
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
        $expectedOutput = '<p>Some text <img src="foo" /></p>';

        $actualOutput = $this->faqHelper->cleanUpContent($content);

        $this->assertEquals($expectedOutput, $actualOutput);
    }


    public function testCleanUpContentWithUmlauts(): void
    {
        $content = '<p>Hellö, wörld!</p>';
        $expectedOutput = '<p>Hellö, wörld!</p>';

        $actualOutput = $this->faqHelper->cleanUpContent($content);

        $this->assertEquals($expectedOutput, $actualOutput);
    }

    public function testCleanUpContentWithYoutubeContent(): void
    {
        $content = <<<'HTML'
        <iframe 
          title="YouTube video player" 
          src="https://www.youtube.com/embed/WaFetxHpCbE" 
          width="560" 
          height="315" 
          frameborder="0" 
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
          allowfullscreen="allowfullscreen"></iframe>
        HTML;

        $actualOutput = $this->faqHelper->cleanUpContent($content);

        $this->assertStringContainsString('YouTube video player', $actualOutput);
        $this->assertStringNotContainsString('frameborder', $actualOutput);
    }

    public function testCleanUpEmptyIframes(): void
    {
        $content = '<iframe></iframe>';
        $expectedOutput = '';

        $actualOutput = $this->faqHelper->cleanUpContent($content);

        $this->assertEquals($expectedOutput, $actualOutput);
    }

    public function testCleanUpContentWithOverflow(): void
    {
        $content = '<p style="position: relative; overflow: auto;">Foobar!</p>';
        $expectedOutput = '<p style="position: relative; ">Foobar!</p>';

        $actualOutput = $this->faqHelper->cleanUpContent($content);

        $this->assertEquals($expectedOutput, $actualOutput);
    }

    public function testConvertOldInternalLinks(): void
    {
        // Test case 1: URL with "artikel" and no language parameter
        $content1 = 'Check <a href="http://example.org/index.php?action=artikel&cat=42&id=123">this link</a>';
        $expected1 = 'Check <a href="https://localhost:443/index.php?action=faq&cat=42&id=123&artlang=en">this link</a>';

        // Test case 2: URL with "artikel" with language parameter
        $content2 = '<p>Check <a href="https://example.com/index.php?action=artikel&cat=10&id=99&artlang=en">this link</a></p>';
        $expected2 = '<p>Check <a href="https://localhost:443/index.php?action=faq&cat=10&id=99&artlang=en">this link</a></p>';

        // Test case 3: URL with "faq" instead of "artikel"
        $content3 = 'More information: <a href="http://example.net/index.php?action=faq&cat=7&id=42">click here</a>';
        $expected3 = 'More information: <a href="https://localhost:443/index.php?action=faq&cat=7&id=42&artlang=en">click here</a>';

        // Test case 4: URL with "faq" and language parameter
        $content4 = 'FAQ: <a href="https://example.org/index.php?action=faq&cat=5&id=12&artlang=fr">En français</a>';
        $expected4 = 'FAQ: <a href="https://localhost:443/index.php?action=faq&cat=5&id=12&artlang=fr">En français</a>';

        // Test case 5: Do nothing if no "artikel" or "faq" in URL
        $content5 = '<a href="https://example.org/index.php?action=search&q=test">Search</a>';
        $expected5 = '<a href="https://example.org/index.php?action=search&q=test">Search</a>';

        // Test ausführen
        $this->assertEquals($expected1, $this->faqHelper->convertOldInternalLinks($content1));
        $this->assertEquals($expected2, $this->faqHelper->convertOldInternalLinks($content2));
        $this->assertEquals($expected3, $this->faqHelper->convertOldInternalLinks($content3));
        $this->assertEquals($expected4, $this->faqHelper->convertOldInternalLinks($content4));
        $this->assertEquals($expected5, $this->faqHelper->convertOldInternalLinks($content5));

        // Test case 6: Multiple Links in a text
        $content6 = 'Here we have 2 links: ' .
            '<a href="http://example.org/index.php?action=artikel&cat=1&id=42">Link 1</a> and ' .
            '<a href="http://example.org/index.php?action=faq&cat=2&id=43&artlang=en">Link 2</a>';
        $expected6 = 'Here we have 2 links: ' .
            '<a href="https://localhost:443/index.php?action=faq&cat=1&id=42&artlang=en">Link 1</a> and ' .
            '<a href="https://localhost:443/index.php?action=faq&cat=2&id=43&artlang=en">Link 2</a>';

        $this->assertEquals($expected6, $this->faqHelper->convertOldInternalLinks($content6));
    }

}
