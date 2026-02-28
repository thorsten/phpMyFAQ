<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(StickyFaqsController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class StickyFaqsControllerWebTest extends ControllerWebTestCase
{
    public function testStickyFaqsPageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/sticky-faqs');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="confirmUnstickyModal"', $response);
    }
}
