<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(StatisticsSearchController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class StatisticsSearchControllerWebTest extends ControllerWebTestCase
{
    public function testStatisticsSearchPageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/statistics/search');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="pmf-table-search-terms"', $response);
    }
}
