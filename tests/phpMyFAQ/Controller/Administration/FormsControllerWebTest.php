<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(FormsController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class FormsControllerWebTest extends ControllerWebTestCase
{
    public function testFormsPageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/forms');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="navigation"', $response);
    }
}
