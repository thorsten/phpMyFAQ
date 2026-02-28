<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(ExportController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class ExportControllerWebTest extends ControllerWebTestCase
{
    public function testExportPageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/export');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('<h1 class="h2">', $response);
        self::assertResponseContains('FAQ Export', $response);
    }
}
