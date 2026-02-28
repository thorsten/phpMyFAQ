<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(SetupController::class)]
#[UsesNamespace('phpMyFAQ')]
final class SetupControllerWebTest extends ControllerWebTestCase
{
    public function testSetupPageRenders(): void
    {
        $response = $this->requestPublic('GET', '/setup');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('Setup', $response);
    }

    public function testUpdatePageRenders(): void
    {
        $response = $this->requestPublic('GET', '/update?step=1');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('Update', $response);
    }

    public function testSetupInstallPageRenders(): void
    {
        $response = $this->requestPublic('GET', '/setup/install');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('Installation', $response);
    }
}
