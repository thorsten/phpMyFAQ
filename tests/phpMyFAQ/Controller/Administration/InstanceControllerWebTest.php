<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(InstanceController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class InstanceControllerWebTest extends ControllerWebTestCase
{
    public function testInstancesPageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/instances');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="pmf-modal-add-instance"', $response);
    }
}
