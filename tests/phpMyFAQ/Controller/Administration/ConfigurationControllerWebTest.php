<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(ConfigurationController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class ConfigurationControllerWebTest extends ControllerWebTestCase
{
    public function testConfigurationPageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/configuration');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="save-configuration"', $response);
        self::assertResponseContains('href="#oauth2"', $response);
        self::assertResponseContains('href="#keycloak"', $response);
    }
}
