<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(GroupController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationApiController::class)]
final class GroupControllerWebTest extends ControllerWebTestCase
{
    public function testGroupDataRouteIsRegistered(): void
    {
        $response = $this->requestAdminApi('GET', '/group/data/4');

        self::assertNotSame(404, $response->getStatusCode());
    }

    public function testGroupMembersRouteIsRegistered(): void
    {
        $response = $this->requestAdminApi('GET', '/group/members/4');

        self::assertNotSame(404, $response->getStatusCode());
    }

    public function testGroupPermissionsRouteIsRegistered(): void
    {
        $response = $this->requestAdminApi('GET', '/group/permissions/4');

        self::assertNotSame(404, $response->getStatusCode());
    }
}
