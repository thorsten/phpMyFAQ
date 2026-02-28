<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(OAuth2Controller::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractApiController::class)]
final class OAuth2ControllerWebTest extends ControllerWebTestCase
{
    public function testUnauthenticatedAuthorizeEndpointReturnsUnauthorizedJson(): void
    {
        $response = $this->requestApi('GET', '/oauth/authorize');

        self::assertResponseStatusCodeSame(401, $response);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }
}
