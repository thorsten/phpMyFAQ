<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(OrphanedFaqsController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class OrphanedFaqsControllerWebTest extends ControllerWebTestCase
{
    public function testOrphanedFaqsPageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/orphaned-faqs');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('These FAQs are not assigned to any category.', $response);
    }
}
