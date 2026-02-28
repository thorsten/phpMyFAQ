<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(PdfController::class)]
#[UsesNamespace('phpMyFAQ')]
final class PdfControllerWebTest extends ControllerWebTestCase
{
    public function testPdfEndpointReturnsNotFoundJsonForUnknownFaq(): void
    {
        $this->overrideConfigurationValues(['api.enableAccess' => true], 'api');

        $response = $this->requestApi('GET', '/v3.2/pdf/999999/999999');

        self::assertResponseStatusCodeSame(404, $response);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }
}
