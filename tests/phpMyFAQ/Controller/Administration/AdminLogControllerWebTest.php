<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(AdminLogController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class AdminLogControllerWebTest extends ControllerWebTestCase
{
    public function testAdminLogPageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/statistics/admin-log');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="pmf-admin-log-verification-result"', $response);
    }
}
