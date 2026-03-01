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
    public function testAddFaqRendersDisabledStateWhenGuestsAreAllowedButNoCategoriesExist(): void
    {
        $this->overrideConfigurationValues([
            'main.enableUserTracking' => false,
            'records.allowNewFaqsForGuests' => true,
        ]);

        $response = $this->requestPublic('GET', '/add-faq.html');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('alert alert-danger', $response);
        self::assertStringNotContainsString('id="pmf-add-faq-form"', (string) $response->getContent());
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

    public function testUnknownSolutionIdReturnsNotFound(): void
    {
        $response = $this->requestPublic('GET', '/solution_id_999999.html');

        self::assertResponseStatusCodeSame(404, $response);
    }

    public function testMissingShortContentRouteWithNonZeroFaqIdReturnsNotFound(): void
    {
        $response = $this->requestPublic('GET', '/content/999999/en');

        self::assertResponseStatusCodeSame(404, $response);
    }

}
