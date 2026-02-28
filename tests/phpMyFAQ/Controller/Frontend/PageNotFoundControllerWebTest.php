<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(PageNotFoundController::class)]
#[UsesNamespace('phpMyFAQ')]
final class PageNotFoundControllerWebTest extends ControllerWebTestCase
{
    public function testNotFoundPageRenders(): void
    {
        $this->overrideConfigurationValues(['main.enableUserTracking' => false]);

        $response = $this->requestPublic('GET', '/404.html');

        self::assertResponseStatusCodeSame(404, $response);
        self::assertResponseContains('Error 404', $response);
        self::assertResponseContains('Back to main page', $response);
    }
}
