<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(UserController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class UserControllerWebTest extends ControllerWebTestCase
{
    public function testUserPageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/user');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="user_select"', $response);
    }

    public function testUserListPageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/user/list');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="pmf-admin-user-table"', $response);
    }
}
