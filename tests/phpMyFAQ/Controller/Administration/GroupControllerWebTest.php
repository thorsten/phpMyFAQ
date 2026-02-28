<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(GroupController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class GroupControllerWebTest extends ControllerWebTestCase
{
    public function testGroupPageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/group');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="group_select"', $response);
    }
}
