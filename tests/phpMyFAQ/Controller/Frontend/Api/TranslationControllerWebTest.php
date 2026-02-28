<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(TranslationController::class)]
#[UsesNamespace('phpMyFAQ')]
final class TranslationControllerWebTest extends ControllerWebTestCase
{
    public function testTranslationsReturnsJsonForSupportedLanguage(): void
    {
        $response = $this->requestApi('GET', '/translations/en');

        self::assertResponseIsSuccessful($response);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
        self::assertResponseContains('"msgSearch"', $response);
    }

    public function testTranslationsReturnsBadRequestForUnsupportedLanguage(): void
    {
        $response = $this->requestApi('GET', '/translations/not-a-language');

        self::assertResponseStatusCodeSame(400, $response);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
        self::assertResponseContains('Language not supported', $response);
    }
}
