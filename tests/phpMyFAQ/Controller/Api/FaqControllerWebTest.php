<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Functional\ControllerWebTestCase;
use phpMyFAQ\Language;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(FaqController::class)]
#[UsesNamespace('phpMyFAQ')]
final class FaqControllerWebTest extends ControllerWebTestCase
{
    public function testStickyReturnsJsonForAcceptLanguage(): void
    {
        $this->overrideConfigurationValues(['api.enableAccess' => true], 'api');

        $response = $this->requestApi(
            'GET',
            '/v4.0/faqs/sticky',
            [],
            [
                'HTTP_ACCEPT_LANGUAGE' => 'de',
            ],
        );

        self::assertContains($response->getStatusCode(), [200, 404]);
        self::assertJson((string) $response->getContent());
    }

    public function testPopularReturnsJsonForAcceptLanguage(): void
    {
        $this->overrideConfigurationValues(['api.enableAccess' => true], 'api');

        $response = $this->requestApi(
            'GET',
            '/v4.0/faqs/popular',
            [],
            [
                'HTTP_ACCEPT_LANGUAGE' => 'de',
            ],
        );

        self::assertContains($response->getStatusCode(), [200, 404]);
        self::assertJson((string) $response->getContent());
    }

    public function testFaqByIdReturnsNotFoundForUnknownRecord(): void
    {
        $this->overrideConfigurationValues(['api.enableAccess' => true], 'api');

        $response = $this->requestApi('GET', '/v4.0/faq/999999/999999');

        self::assertResponseStatusCodeSame(404, $response);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }

    protected function tearDown(): void
    {
        Language::$language = 'en';

        parent::tearDown();
    }
}
