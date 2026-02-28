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
    public function testWebAuthnPageRenders(): void
    {
        $response = $this->requestPublic('GET', '/services/webauthn');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('Login with Passkey', $response);
    }
}
