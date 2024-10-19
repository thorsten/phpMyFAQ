<?php

namespace phpMyFAQ\Template\Extensions;

use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;

class TranslateTwigExtensionTest extends TestCase
{
    private TranslateTwigExtension $translateTwigExtension;

    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        Translation::create()
            ->setLanguagesDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->translateTwigExtension = new TranslateTwigExtension();
    }
    public function testGetFunctions(): void
    {
        $functions = $this->translateTwigExtension->getFunctions();

        $this->assertIsArray($functions);
        $this->assertCount(1, $functions);
        $this->assertEquals('translate', $functions[0]->getName());
    }

    public function testGetFilters(): void
    {
        $filters = $this->translateTwigExtension->getFilters();

        $this->assertCount(1, $filters);

        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertEquals('translate', $filters[0]->getName());
    }

    public function testTranslate(): void
    {
        $translationKey = 'msgCategory';
        $expectedTranslation = 'Categories';

        $actualTranslation = $this->translateTwigExtension->translate($translationKey);

        $this->assertEquals($expectedTranslation, $actualTranslation);
    }
}
