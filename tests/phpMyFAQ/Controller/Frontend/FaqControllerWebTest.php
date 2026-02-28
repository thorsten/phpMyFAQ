<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(FaqController::class)]
#[UsesNamespace('phpMyFAQ')]
final class FaqControllerWebTest extends ControllerWebTestCase
{
    public function testAddFaqRedirectsToLoginWhenGuestFaqsAreDisabled(): void
    {
        $this->overrideConfigurationValues([
            'main.enableUserTracking' => false,
            'records.allowNewFaqsForGuests' => false,
        ]);

        $response = $this->requestPublic('GET', '/add-faq.html');

        self::assertResponseStatusCodeSame(302, $response);
        self::assertSame('https://localhost/login', $response->headers->get('Location'));
    }

    public function testInvalidSolutionIdReturnsNotFound(): void
    {
        $response = $this->requestPublic('GET', '/solution_id_0.html');

        self::assertResponseStatusCodeSame(404, $response);
    }

    public function testMissingShortContentRouteReturnsNotFound(): void
    {
        $response = $this->requestPublic('GET', '/content/0/en');

        self::assertResponseStatusCodeSame(404, $response);
    }
}
