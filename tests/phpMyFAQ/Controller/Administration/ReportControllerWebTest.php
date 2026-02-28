<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(ReportController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class ReportControllerWebTest extends ControllerWebTestCase
{
    public function testReportPageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/statistics/report');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="pmf-admin-report-form"', $response);
    }
}
