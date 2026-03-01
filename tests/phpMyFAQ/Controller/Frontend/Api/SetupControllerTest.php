<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Permission\PermissionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(SetupController::class)]
#[UsesNamespace('phpMyFAQ')]
final class SetupControllerTest extends ApiControllerTestCase
{
    public function testCheckReturnsBadRequestWhenNoVersionIsGiven(): void
    {
        $controller = new SetupController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock());

        $response = $controller->check(new Request([], [], [], [], [], [], ''));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            '{"message":"No version given."}',
            (string) $response->getContent(),
        );
    }

    public function testBackupReturnsBadRequestWhenNoVersionIsGiven(): void
    {
        $controller = new SetupController();
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(
            PermissionInterface::class,
            ['hasPermission' => true],
        );
        $this->injectControllerState($controller, $currentUser);

        $response = $controller->backup(new Request([], [], [], [], [], [], ''));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            '{"message":"No version given."}',
            (string) $response->getContent(),
        );
    }

    public function testUpdateDatabaseReturnsBadRequestWhenNoVersionIsGiven(): void
    {
        $controller = new SetupController();
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(
            PermissionInterface::class,
            ['hasPermission' => true],
        );
        $this->injectControllerState($controller, $currentUser);

        $response = $controller->updateDatabase(new Request([], [], [], [], [], [], ''));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            '{"message":"No version given."}',
            (string) $response->getContent(),
        );
    }
}
