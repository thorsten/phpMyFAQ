<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(SessionKeepAliveController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class SessionKeepAliveControllerWebTest extends ControllerWebTestCase
{
    public function testUnauthenticatedKeepAliveRedirectsToLogin(): void
    {
        $response = $this->requestAdminGuest('GET', '/session-keep-alive');

        self::assertResponseStatusCodeSame(302, $response);
        self::assertRedirectLocationContains('/login', $response);
    }
}
