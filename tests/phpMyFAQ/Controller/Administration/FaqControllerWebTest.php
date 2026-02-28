<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(FaqController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class FaqControllerWebTest extends ControllerWebTestCase
{
    public function testFaqOverviewPageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/faqs');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="pmf-faq-search-autocomplete"', $response);
    }
}
