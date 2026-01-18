<?php

namespace phpMyFAQ\Translation;

use phpMyFAQ\Configuration;
use phpMyFAQ\Translation\DTO\TranslationRequest;
use phpMyFAQ\Translation\Exception\TranslationException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ContentTranslationServiceTest extends TestCase
{
    private TranslationProviderInterface $provider;
    private ContentTranslationService $service;

    protected function setUp(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $this->provider = $this->createMock(TranslationProviderInterface::class);

        $configuration->method('getTranslationProvider')->willReturn($this->provider);

        $this->service = new ContentTranslationService($configuration);
    }

    /**
     * @throws TranslationException
     */ public function testTranslateFaqWithAllFields(): void
    {
        $fields = [
            'question' => 'What is phpMyFAQ?',
            'answer' => '<p>phpMyFAQ is an <strong>open-source</strong> FAQ system.</p>',
            'keywords' => 'phpmyfaq, faq, open source',
        ];

        $this->provider
            ->method('translate')
            ->willReturnMap([
                ['What is phpMyFAQ?', 'en', 'de', false, 'Was ist phpMyFAQ?'],
                [
                    '<p>phpMyFAQ is an <strong>open-source</strong> FAQ system.</p>',
                    'en',
                    'de',
                    true,
                    '<p>phpMyFAQ ist ein <strong>Open-Source</strong> FAQ-System.</p>',
                ],
                ['phpmyfaq, faq, open source', 'en', 'de', false, 'phpmyfaq, faq, open source'],
            ]);

        $request = new TranslationRequest('faq', 'en', 'de', $fields);
        $result = $this->service->translateFaq($request);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('Was ist phpMyFAQ?', $result->getTranslatedFields()['question']);
        $this->assertEquals(
            '<p>phpMyFAQ ist ein <strong>Open-Source</strong> FAQ-System.</p>',
            $result->getTranslatedFields()['answer'],
        );
        $this->assertEquals('phpmyfaq, faq, open source', $result->getTranslatedFields()['keywords']);
    }

    /**
     * @throws TranslationException
     */ public function testTranslateFaqWithPartialFields(): void
    {
        $fields = [
            'question' => 'What is phpMyFAQ?',
        ];

        $this->provider
            ->method('translate')
            ->with('What is phpMyFAQ?', 'en', 'de', false)
            ->willReturn('Was ist phpMyFAQ?');

        $request = new TranslationRequest('faq', 'en', 'de', $fields);
        $result = $this->service->translateFaq($request);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('Was ist phpMyFAQ?', $result->getTranslatedFields()['question']);
        $this->assertArrayNotHasKey('answer', $result->getTranslatedFields());
        $this->assertArrayNotHasKey('keywords', $result->getTranslatedFields());
    }

    /**
     * @throws TranslationException
     */ public function testTranslateFaqWithEmptyFields(): void
    {
        $fields = [
            'question' => '',
            'answer' => '',
        ];

        $request = new TranslationRequest('faq', 'en', 'de', $fields);
        $result = $this->service->translateFaq($request);

        $this->assertTrue($result->isSuccess());
        $this->assertEmpty($result->getTranslatedFields());
    }

    public function testTranslateFaqThrowsExceptionWhenNoProvider(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getTranslationProvider')->willReturn(null);

        $service = new ContentTranslationService($configuration);

        $this->expectException(TranslationException::class);
        $this->expectExceptionMessage('No translation provider configured');

        $request = new TranslationRequest('faq', 'en', 'de', ['question' => 'Test']);
        $service->translateFaq($request);
    }

    /**
     * @throws TranslationException
     */ public function testTranslateCustomPageWithAllFields(): void
    {
        $fields = [
            'pageTitle' => 'About Us',
            'content' => '<h1>Welcome</h1><p>This is our <em>about</em> page.</p>',
            'seoTitle' => 'About Us - Company',
            'seoDescription' => 'Learn more about our company',
        ];

        $this->provider
            ->method('translate')
            ->willReturnMap([
                ['About Us', 'en', 'de', false, 'Über uns'],
                [
                    '<h1>Welcome</h1><p>This is our <em>about</em> page.</p>',
                    'en',
                    'de',
                    true,
                    '<h1>Willkommen</h1><p>Dies ist unsere <em>Über uns</em> Seite.</p>',
                ],
                ['About Us - Company', 'en', 'de', false, 'Über uns - Unternehmen'],
                ['Learn more about our company', 'en', 'de', false, 'Erfahren Sie mehr über unser Unternehmen'],
            ]);

        $request = new TranslationRequest('customPage', 'en', 'de', $fields);
        $result = $this->service->translateCustomPage($request);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('Über uns', $result->getTranslatedFields()['pageTitle']);
        $this->assertEquals(
            '<h1>Willkommen</h1><p>Dies ist unsere <em>Über uns</em> Seite.</p>',
            $result->getTranslatedFields()['content'],
        );
        $this->assertEquals('Über uns - Unternehmen', $result->getTranslatedFields()['seoTitle']);
        $this->assertEquals(
            'Erfahren Sie mehr über unser Unternehmen',
            $result->getTranslatedFields()['seoDescription'],
        );
    }

    public function testTranslateCustomPageThrowsExceptionWhenNoProvider(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getTranslationProvider')->willReturn(null);

        $service = new ContentTranslationService($configuration);

        $this->expectException(TranslationException::class);
        $this->expectExceptionMessage('No translation provider configured');

        $request = new TranslationRequest('customPage', 'en', 'de', ['pageTitle' => 'Test']);
        $service->translateCustomPage($request);
    }

    /**
     * @throws TranslationException
     */ public function testTranslateCategoryWithAllFields(): void
    {
        $fields = [
            'name' => 'General Questions',
            'description' => 'Frequently asked general questions',
        ];

        $this->provider
            ->method('translate')
            ->willReturnMap([
                ['General Questions',                  'en', 'de', false, 'Allgemeine Fragen'],
                ['Frequently asked general questions', 'en', 'de', false, 'Häufig gestellte allgemeine Fragen'],
            ]);

        $request = new TranslationRequest('category', 'en', 'de', $fields);
        $result = $this->service->translateCategory($request);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('Allgemeine Fragen', $result->getTranslatedFields()['name']);
        $this->assertEquals('Häufig gestellte allgemeine Fragen', $result->getTranslatedFields()['description']);
    }

    public function testTranslateCategoryThrowsExceptionWhenNoProvider(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getTranslationProvider')->willReturn(null);

        $service = new ContentTranslationService($configuration);

        $this->expectException(TranslationException::class);
        $this->expectExceptionMessage('No translation provider configured');

        $request = new TranslationRequest('category', 'en', 'de', ['name' => 'Test']);
        $service->translateCategory($request);
    }

    /**
     * @throws TranslationException
     */ public function testTranslateNewsWithAllFields(): void
    {
        $fields = [
            'header' => 'New Release Available',
            'message' => 'We are happy to announce our new release.',
            'linkTitle' => 'Read more',
        ];

        $this->provider
            ->method('translate')
            ->willReturnMap([
                ['New Release Available', 'en', 'de', false, 'Neue Version verfügbar'],
                [
                    'We are happy to announce our new release.',
                    'en',
                    'de',
                    false,
                    'Wir freuen uns, unsere neue Version anzukündigen.',
                ],
                ['Read more', 'en', 'de', false, 'Mehr lesen'],
            ]);

        $request = new TranslationRequest('news', 'en', 'de', $fields);
        $result = $this->service->translateNews($request);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('Neue Version verfügbar', $result->getTranslatedFields()['header']);
        $this->assertEquals(
            'Wir freuen uns, unsere neue Version anzukündigen.',
            $result->getTranslatedFields()['message'],
        );
        $this->assertEquals('Mehr lesen', $result->getTranslatedFields()['linkTitle']);
    }

    public function testTranslateNewsThrowsExceptionWhenNoProvider(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getTranslationProvider')->willReturn(null);

        $service = new ContentTranslationService($configuration);

        $this->expectException(TranslationException::class);
        $this->expectExceptionMessage('No translation provider configured');

        $request = new TranslationRequest('news', 'en', 'de', ['header' => 'Test']);
        $service->translateNews($request);
    }
}
