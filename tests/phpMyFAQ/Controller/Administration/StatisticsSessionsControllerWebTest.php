<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(StatisticsSessionsController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class StatisticsSessionsControllerWebTest extends ControllerWebTestCase
{
    public function testStatisticsSessionsPageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/statistics/sessions');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="pmf-admin-clear-visits"', $response);
    }
}
