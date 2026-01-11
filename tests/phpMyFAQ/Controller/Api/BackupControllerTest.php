<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class BackupControllerTest extends TestCase
{
    public function testDownloadRequiresAuthentication(): void
    {
        $request = new Request();
        $request->attributes->set('type', 'data');

        $controller = new BackupController();

        $this->expectException(Exception::class);
        $controller->download($request);
    }

    public function testDownloadWithInvalidBackupType(): void
    {
        $request = new Request();
        $request->attributes->set('type', 'invalid');

        $controller = new BackupController();

        $this->expectException(Exception::class);
        $controller->download($request);
    }

    public function testDownloadWithDataType(): void
    {
        $request = new Request();
        $request->attributes->set('type', 'data');

        $controller = new BackupController();

        $this->expectException(Exception::class);
        $controller->download($request);
    }

    public function testDownloadWithLogsType(): void
    {
        $request = new Request();
        $request->attributes->set('type', 'logs');

        $controller = new BackupController();

        $this->expectException(Exception::class);
        $controller->download($request);
    }

    public function testDownloadWithContentType(): void
    {
        $request = new Request();
        $request->attributes->set('type', 'content');

        $controller = new BackupController();

        $this->expectException(Exception::class);
        $controller->download($request);
    }
}
