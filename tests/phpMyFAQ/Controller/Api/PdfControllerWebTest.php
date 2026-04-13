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
    public function testGetByIdReturnsUnauthorizedProblemWhenApiIsDisabled(): void
    {
        $this->getConfiguration('api')->getAll();
        $this->overrideConfigurationValues(['api.enableAccess' => false], 'api');

        $response = $this->requestApi('GET', '/v4.0/pdf/1/1');

        self::assertResponseStatusCodeSame(401, $response);
        self::assertStringContainsString('problem+json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }

    public function testGetByIdReturnsNotFoundForUnknownFaq(): void
    {
        $this->overrideConfigurationValues(['api.enableAccess' => true], 'api');

        $response = $this->requestApi('GET', '/v4.0/pdf/1/999999');

        self::assertResponseStatusCodeSame(404, $response);
        self::assertSame('application/json', $response->headers->get('Content-Type'));
    }
}
