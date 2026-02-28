<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(AuthenticationController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class AuthenticationControllerWebTest extends ControllerWebTestCase
{
    public function testAdminLoginPageIsReachable(): void
    {
        $response = $this->requestAdmin('GET', '/login');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('admin/authenticate', $response);
    }

    public function testAdminAuthenticateWithoutCredentialsRedirectsToDashboard(): void
    {
        $response = $this->requestAdmin('POST', '/authenticate');

        self::assertResponseStatusCodeSame(302, $response);
        self::assertRedirectLocationContains('./', $response);
    }
}
