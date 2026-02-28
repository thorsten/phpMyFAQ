<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(SetupController::class)]
#[UsesNamespace('phpMyFAQ')]
final class SetupControllerWebTest extends ControllerWebTestCase
{
    public function testSetupCheckReturnsUnauthorizedWhenAnonymous(): void
    {
        $response = $this->requestApi('POST', '/setup/check');

        self::assertResponseStatusCodeSame(401, $response);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }

    public function testSetupBackupReturnsUnauthorizedWhenAnonymous(): void
    {
        $response = $this->requestApi('POST', '/setup/backup');

        self::assertResponseStatusCodeSame(401, $response);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }
}
