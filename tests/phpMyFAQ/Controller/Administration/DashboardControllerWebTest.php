<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(DashboardController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class DashboardControllerWebTest extends ControllerWebTestCase
{
    public function testUnauthenticatedDashboardRedirectsToLogin(): void
    {
        $response = $this->requestAdmin('GET', '/');

        self::assertResponseStatusCodeSame(302, $response);
        self::assertRedirectLocationContains('/login', $response);
    }
}
