<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(WebAuthnController::class)]
#[UsesNamespace('phpMyFAQ')]
final class WebAuthnControllerWebTest extends ControllerWebTestCase
{
    public function testWebAuthnPrepareRouteIsRegistered(): void
    {
        $response = $this->requestApi('POST', '/webauthn/prepare');

        self::assertNotSame(404, $response->getStatusCode());
    }

    public function testWebAuthnRegisterRouteIsRegistered(): void
    {
        $response = $this->requestApi('POST', '/webauthn/register');

        self::assertNotSame(404, $response->getStatusCode());
    }

    public function testWebAuthnPrepareLoginRouteIsRegistered(): void
    {
        $response = $this->requestApi('POST', '/webauthn/prepare-login');

        self::assertNotSame(404, $response->getStatusCode());
    }

    public function testWebAuthnLoginRouteIsRegistered(): void
    {
        $response = $this->requestApi('POST', '/webauthn/login');

        self::assertNotSame(404, $response->getStatusCode());
    }
}
