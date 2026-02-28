<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(TitleController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(\phpMyFAQ\Controller\AbstractController::class)]
final class TitleControllerWebTest extends ControllerWebTestCase
{
    public function testTitleEndpointReturnsJson(): void
    {
        $this->overrideConfigurationValues(['api.enableAccess' => true], 'api');

        $response = $this->requestApi('GET', '/v3.2/title');

        self::assertResponseIsSuccessful($response);
        self::assertSame('application/json', $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }
}
