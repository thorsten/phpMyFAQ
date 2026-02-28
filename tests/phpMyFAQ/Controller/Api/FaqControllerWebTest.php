<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use phpMyFAQ\Functional\ControllerWebTestCase;

#[CoversClass(FaqController::class)]
#[UsesNamespace('phpMyFAQ')]
final class FaqControllerWebTest extends ControllerWebTestCase
{
    public function testFaqByIdReturnsNotFoundForUnknownRecord(): void
    {
        $response = $this->requestApi('GET', '/v3.2/faq/999999/999999');

        self::assertResponseStatusCodeSame(404, $response);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }
}
