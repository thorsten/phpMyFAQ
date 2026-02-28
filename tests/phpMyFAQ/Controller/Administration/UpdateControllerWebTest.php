<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(UpdateController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class UpdateControllerWebTest extends ControllerWebTestCase
{
    public function testUpdatePageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/update');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="pmf-button-check-updates"', $response);
    }
}
