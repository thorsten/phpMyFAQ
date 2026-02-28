<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(PushController::class)]
#[UsesNamespace('phpMyFAQ')]
final class PushControllerWebTest extends ControllerWebTestCase
{
    public function testVapidPublicKeyEndpointReturnsJson(): void
    {
        $response = $this->requestApi('GET', '/push/vapid-public-key');

        self::assertResponseIsSuccessful($response);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }

    public function testPushStatusReturnsUnauthorizedWhenAnonymous(): void
    {
        $response = $this->requestApi('GET', '/push/status');

        self::assertResponseStatusCodeSame(401, $response);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }
}
