<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(BackupController::class)]
#[UsesNamespace('phpMyFAQ')]
final class BackupControllerWebTest extends ControllerWebTestCase
{
    public function testBackupEndpointReturnsUnauthorizedProblemWhenApiIsDisabled(): void
    {
        $this->getConfiguration('api')->getAll();
        $this->overrideConfigurationValues(['api.enableAccess' => false], 'api');

        $response = $this->requestApi('GET', '/v4.0/backup/data');

        self::assertResponseStatusCodeSame(401, $response);
        self::assertStringContainsString('problem+json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }

    public function testBackupEndpointReturnsUnauthorizedWhenAnonymous(): void
    {
        $this->overrideConfigurationValues(['api.enableAccess' => true], 'api');

        $response = $this->requestApi('GET', '/v4.0/backup/data');

        self::assertResponseStatusCodeSame(401, $response);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }
}
