<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(ImportController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class ImportControllerWebTest extends ControllerWebTestCase
{
    public function testImportPageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/import');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="uploadCSVFileForm"', $response);
    }
}
