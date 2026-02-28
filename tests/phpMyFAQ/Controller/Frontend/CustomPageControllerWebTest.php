<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(CustomPageController::class)]
#[UsesNamespace('phpMyFAQ')]
final class CustomPageControllerWebTest extends ControllerWebTestCase
{
    public function testActiveCustomPageRenders(): void
    {
        $response = $this->requestPublic('GET', '/page/unit-test-page.html');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('Unit Test Page', $response);
        self::assertResponseContains('Unit test content', $response);
    }

    public function testInactiveCustomPageRendersNotFoundPage(): void
    {
        $response = $this->requestPublic('GET', '/page/page-2.html');

        self::assertContains($response->getStatusCode(), [200, 404]);
        self::assertResponseContains('404', $response);
    }
}
