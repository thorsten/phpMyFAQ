<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(GlossaryController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class GlossaryControllerWebTest extends ControllerWebTestCase
{
    public function testGlossaryPageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/glossary');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="pmf-admin-glossary-table"', $response);
    }
}
