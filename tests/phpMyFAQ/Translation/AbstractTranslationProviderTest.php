<?php

declare(strict_types=1);

namespace phpMyFAQ\Translation;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;

#[CoversClass(AbstractTranslationProvider::class)]
#[UsesClass(HtmlPreserver::class)]
final class AbstractTranslationProviderTest extends TestCase
{
    /**
     * A provider that upper-cases the text and records every call, so the test can see
     * exactly what the base class handed to the provider-specific implementation.
     */
    private function createProvider(): AbstractTranslationProvider
    {
        $configuration = $this->createStub(Configuration::class);

        return new class ($configuration, new MockHttpClient()) extends AbstractTranslationProvider {
            /** @var array<int, array{string, string, string}> */
            public array $calls = [];

            protected function doTranslate(string $text, string $sourceLang, string $targetLang): string
            {
                $this->calls[] = [$text, $sourceLang, $targetLang];

                return strtoupper($text);
            }

            protected function doTranslateBatch(array $texts, string $sourceLang, string $targetLang): array
            {
                return array_map(strtoupper(...), $texts);
            }

            protected function mapLanguageCode(string $pmfLangCode): string
            {
                return $pmfLangCode;
            }

            public function translateBatch(array $texts, string $sourceLang, string $targetLang, bool $preserveHtml = false): array
            {
                return $this->doTranslateBatch($texts, $sourceLang, $targetLang);
            }

            public function getProviderName(): string
            {
                return 'recording';
            }

            public function getSupportedLanguages(): array
            {
                return ['en', 'de'];
            }

            public function supportsLanguagePair(string $sourceLang, string $targetLang): bool
            {
                return true;
            }
        };
    }

    public function testEmptyTextIsNotSentToTheProvider(): void
    {
        $provider = $this->createProvider();

        $this->assertSame('', $provider->translate('', 'en', 'de'));
        $this->assertSame([], $provider->calls);
    }

    public function testPlainTextIsPassedThroughUnchanged(): void
    {
        $provider = $this->createProvider();

        $this->assertSame('HELLO WORLD', $provider->translate('hello world', 'en', 'de'));
        $this->assertSame([['hello world', 'en', 'de']], $provider->calls);
    }

    public function testHtmlIsSentToTheProviderUnlessPreservationIsRequested(): void
    {
        $provider = $this->createProvider();

        $this->assertSame('<P>HELLO</P>', $provider->translate('<p>hello</p>', 'en', 'de'));
        $this->assertSame('<p>hello</p>', $provider->calls[0][0]);
    }

    public function testPreservedHtmlTagsNeverReachTheProviderAndAreRestoredAfterwards(): void
    {
        $provider = $this->createProvider();

        $translated = $provider->translate('<p>hello <strong>world</strong></p>', 'en', 'de', preserveHtml: true);

        $sentText = $provider->calls[0][0];
        $this->assertStringNotContainsString('<p>', $sentText);
        $this->assertStringNotContainsString('<strong>', $sentText);
        $this->assertStringContainsString('hello', $sentText);
        $this->assertStringContainsString('world', $sentText);

        $this->assertSame('<p>HELLO <strong>WORLD</strong></p>', $translated);
    }

    public function testPreservationIsANoOpForTextWithoutTags(): void
    {
        $provider = $this->createProvider();

        $this->assertSame('HELLO', $provider->translate('hello', 'en', 'de', preserveHtml: true));
    }
}
