<?php

namespace phpMyFAQ\Helper;

use League\CommonMark\Exception\CommonMarkException;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

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
        $this->configuration->set('main.enableMarkdownEditor', true);

        $session = $this->createMock(Session::class);
        $language = new Language($this->configuration, $session);
        $this->configuration->setLanguage($language);

        $this->faqHelper = new FaqHelper($this->configuration);
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

    /**
     * @throws CommonMarkException
     */
    public function testRenderAnswerPreviewWithMarkdown(): void
    {
        $answer = '# Hello, World!';
        $wordCount = 2;

        $result = $this->faqHelper->renderAnswerPreview($answer, $wordCount);

        $this->assertEquals('Hello, World!', trim($result));
    }

    /**
     * @throws CommonMarkException
     */
    public function testRenderAnswerPreviewWithoutMarkdown(): void
    {
        $this->configuration->set('main.enableMarkdownEditor', false);
        $answer = '<p>Hello, <strong>World!</strong></p>';
        $wordCount = 2;

        $result = $this->faqHelper->renderAnswerPreview($answer, $wordCount);

        $this->assertEquals('Hello, World!', trim($result));
    }

    public function testConvertOldInternalLinks(): void
    {
        // Test questions for slug generation
        $question1 = 'How can I create an account?';
        $question2 = 'Software installation guide';

        // Test case 1: URL with "artikel" and without language parameter
        $content1 = 'See <a href="http://example.org/index.php?action=artikel&cat=42&id=123">this link</a>';
        $expected1 = 'See <a href="https://localhost:443/content/42/123/en/how-can-i-create-an-account.html">this link</a>';

        // Test case 2: URL with "artikel" and with language parameter
        $content2 = '<p>See <a href="https://example.com/index.php?action=artikel&cat=10&id=99&artlang=en">this link</a></p>';
        $expected2 = '<p>See <a href="https://localhost:443/content/10/99/en/how-can-i-create-an-account.html">this link</a></p>';

        // Test case 3: URL with "faq" instead of "artikel"
        $content3 = 'More information: <a href="http://example.net/index.php?action=faq&cat=7&id=42">Click here</a>';
        $expected3 = 'More information: <a href="https://localhost:443/content/7/42/en/how-can-i-create-an-account.html">Click here</a>';

        // Test case 4: URL with "faq" and language parameter
        $content4 = 'FAQ: <a href="https://example.org/index.php?action=faq&cat=5&id=12&artlang=fr">In French</a>';
        $expected4 = 'FAQ: <a href="https://localhost:443/content/5/12/fr/how-can-i-create-an-account.html">In French</a>';

        // Test case 5: No change if pattern doesn't match
        $content5 = '<a href="https://example.org/index.php?action=search&q=test">Search</a>';
        $expected5 = '<a href="https://example.org/index.php?action=search&q=test">Search</a>';

        // Test case 6: Multiple links in one text
        $content6 = 'Here are two links: ' .
            '<a href="http://example.org/index.php?action=artikel&cat=1&id=42">Link 1</a> and ' .
            '<a href="http://example.org/index.php?action=faq&cat=2&id=43&artlang=en">Link 2</a>';
        $expected6 = 'Here are two links: ' .
            '<a href="https://localhost:443/content/1/42/en/how-can-i-create-an-account.html">Link 1</a> and ' .
            '<a href="https://localhost:443/content/2/43/en/how-can-i-create-an-account.html">Link 2</a>';

        // Test case 7: Test with special characters in question
        $question3 = 'How to install PHP 8.1? (Quick & Easy)';
        $content7 = '<a href="http://example.org/index.php?action=artikel&cat=3&id=55">PHP Installation</a>';
        $expected7 = '<a href="https://localhost:443/content/3/55/en/how-to-install-php-81-quick-&-easy.html">PHP Installation</a>';

        // Run tests
        $this->assertEquals($expected1, $this->faqHelper->convertOldInternalLinks($question1, $content1));
        $this->assertEquals($expected2, $this->faqHelper->convertOldInternalLinks($question1, $content2));
        $this->assertEquals($expected3, $this->faqHelper->convertOldInternalLinks($question1, $content3));
        $this->assertEquals($expected4, $this->faqHelper->convertOldInternalLinks($question1, $content4));
        $this->assertEquals($expected5, $this->faqHelper->convertOldInternalLinks($question1, $content5));
        $this->assertEquals($expected6, $this->faqHelper->convertOldInternalLinks($question1, $content6));
        $this->assertEquals($expected7, $this->faqHelper->convertOldInternalLinks($question3, $content7));

        // Test with different question text
        $this->assertEquals(
            'See <a href="https://localhost:443/content/42/123/en/software-installation-guide.html">this link</a>',
            $this->faqHelper->convertOldInternalLinks($question2, $content1)
        );
    }
}
