<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(LlmsController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractController::class)]
final class LlmsControllerWebTest extends ControllerWebTestCase
{
    public function testLlmsEndpointReturnsPlainText(): void
    {
        $response = $this->requestAny('GET', '/llms.txt');

        self::assertResponseIsSuccessful($response);
        self::assertSame('text/plain', $response->headers->get('Content-Type'));
        self::assertNotEmpty((string) $response->getContent());
    }
}
