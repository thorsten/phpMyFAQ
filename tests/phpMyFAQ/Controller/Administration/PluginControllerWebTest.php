<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(PluginController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class PluginControllerWebTest extends ControllerWebTestCase
{
    public function testPluginsPageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/plugins');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('List of the installed plugins and their versions', $response);
    }
}
