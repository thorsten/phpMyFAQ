<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(PdfController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(\phpMyFAQ\Controller\AbstractController::class)]
final class PdfControllerWebTest extends ControllerWebTestCase
{
    public function testGetByIdReturnsNotFoundForUnknownFaq(): void
    {
        $this->overrideConfigurationValues(['api.enableAccess' => true], 'api');

        $response = $this->requestApi('GET', '/v3.2/pdf/1/999999');

        self::assertResponseStatusCodeSame(404, $response);
        self::assertSame('application/json', $response->headers->get('Content-Type'));
    }
}
