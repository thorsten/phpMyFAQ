<?php

declare(strict_types=1);

namespace phpMyFAQ\Category\Language;

use phpMyFAQ\Configuration;
use phpMyFAQ\Language as PmfLanguage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
final class CategoryLanguageServiceTest extends TestCase
{
    /** @var Configuration&MockObject */
    private Configuration $configuration;
    /** @var PmfLanguage&MockObject */
    private PmfLanguage $language;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configuration = $this->createStub(Configuration::class);
        $this->language = $this->getMockBuilder(PmfLanguage::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLanguageAvailable'])
            ->getMock();
        $this->configuration->method('getLanguage')->willReturn($this->language);
    }

    public function testGetLanguagesInUseReturnsCodes(): void
    {
        $this->language->method('isLanguageAvailable')->with(0, 'faqcategories')->willReturn(['en', 'de']);

        $service = new CategoryLanguageService();
        $result = $service->getLanguagesInUse($this->configuration);

        $this->assertArrayHasKey('en', $result);
        $this->assertArrayHasKey('de', $result);
    }

    public function testGetExistingTranslationsKeysMatchExisting(): void
    {
        $this->language->method('isLanguageAvailable')->with(123, 'faqcategories')->willReturn(['en', 'de']);

        $service = new CategoryLanguageService();
        $result = $service->getExistingTranslations($this->configuration, 123);

        $this->assertArrayHasKey('en', $result);
        $this->assertArrayHasKey('de', $result);
    }

    public function testGetLanguagesToTranslateExcludesExisting(): void
    {
        $this->language->method('isLanguageAvailable')->with(456, 'faqcategories')->willReturn(['en']);

        $service = new CategoryLanguageService();
        $result = $service->getLanguagesToTranslate($this->configuration, 456);

        $this->assertArrayNotHasKey('en', $result);
        $this->assertIsArray($result);
    }
}

