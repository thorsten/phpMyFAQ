<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(PageController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class PageControllerWebTest extends ControllerWebTestCase
{
    public function testPagesPageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/pages');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="confirmDeletePageModal"', $response);
    }
}
