<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(NewsController::class)]
#[UsesNamespace('phpMyFAQ')]
final class NewsControllerWebTest extends ControllerWebTestCase
{
    public function testNewsPageReturnsNotFoundForMissingRecord(): void
    {
        $this->overrideConfigurationValues(['main.enableUserTracking' => false]);

        $response = $this->requestPublic('GET', '/news/999/en/missing-news.html');

        self::assertResponseStatusCodeSame(404, $response);
        self::assertResponseContains('Back to main page', $response);
    }
}
