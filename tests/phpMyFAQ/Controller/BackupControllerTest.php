<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller;

use phpMyFAQ\Administration\Backup;
use phpMyFAQ\Administration\Backup\BackupExportResult;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\Administration\BackupController;
use phpMyFAQ\Enums\BackupType;
use phpMyFAQ\Permission\BasicPermission;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

#[AllowMockObjectsWithoutExpectations]
class BackupControllerTest extends TestCase
{
    private Configuration $configurationMock;
    private CurrentUser $currentUserMock;
    private BasicPermission $permissionMock;
    private Backup $backupServiceMock;

    private Session $session;

    protected function setUp(): void
    {
        $this->configurationMock = $this->createStub(Configuration::class);
        $this->currentUserMock = $this->createStub(CurrentUser::class);
        $this->permissionMock = $this->createStub(BasicPermission::class);
        $this->backupServiceMock = $this->createMock(Backup::class);
        $this->session = new Session(new MockArraySessionStorage());

        $this->currentUserMock->perm = $this->permissionMock;
    }

    private function createController(): BackupController
    {
        $controller = new class(
            $this->configurationMock,
            $this->currentUserMock,
            $this->backupServiceMock,
            $this->session,
        ) extends BackupController {
            public function __construct(
                Configuration $configuration,
                CurrentUser $currentUser,
                Backup $backupService,
                Session $session,
            ) {
                $this->configuration = $configuration;
                $this->currentUser = $currentUser;
                $this->container = new ContainerBuilder();
                $this->container->set('phpmyfaq.backup', $backupService);
                $this->container->set('session', $session);
            }
        };

        // Berechtigung immer erlauben
        $this->permissionMock->method('hasPermission')->willReturn(true);

        return $controller;
    }

    public function testExportReturnsBackupContentForContentType(): void
    {
        $controller = $this->createController();

        $request = new Request(attributes: ['type' => 'content']);

        $this->backupServiceMock
            ->expects($this->once())
            ->method('export')
            ->with(BackupType::BACKUP_TYPE_DATA)
            ->willReturn(new BackupExportResult('backup.sql', 'SQL-DUMP'));

        $response = $controller->export($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('SQL-DUMP', $response->getContent());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Content-Disposition'));
    }

    public function testExportWithInvalidTypeReturnsBadRequest(): void
    {
        $controller = $this->createController();

        $request = new Request(attributes: ['type' => 'invalid']);

        $response = $controller->export($request);

        $this->assertSame(400, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */ public function testRestoreThrowsWhenCsrfTokenInvalid(): void
    {
        $this->expectException(UnauthorizedHttpException::class);

        $controller = $this->createController();

        $request = new Request(query: ['csrf' => 'invalid']);

        $controller->restore($request);
    }
}
