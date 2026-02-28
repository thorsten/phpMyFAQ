<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(RobotsController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractController::class)]
final class RobotsControllerWebTest extends ControllerWebTestCase
{
    public function testRobotsEndpointReturnsPlainText(): void
    {
        $response = $this->requestAny('GET', '/robots.txt');

        self::assertResponseIsSuccessful($response);
        self::assertSame('text/plain', $response->headers->get('Content-Type'));
        self::assertNotEmpty((string) $response->getContent());
    }
}
