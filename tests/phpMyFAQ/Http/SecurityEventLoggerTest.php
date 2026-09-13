<?php

declare(strict_types=1);

namespace phpMyFAQ\Http;

use phpMyFAQ\Enums\AdminLogType;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final class SecurityEventLoggerTest extends TestCase
{
    public function testLogsTypeDetailAndRequestContextAsWarning(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'security-permission-violation: User has no "USER_ADD" permission. '
                . '[method=POST path=/admin/api/user/add ip=203.0.113.7 user=42]',
            );

        $request = Request::create('/admin/api/user/add', 'POST', server: ['REMOTE_ADDR' => '203.0.113.7']);

        new SecurityEventLogger($logger)->log(
            AdminLogType::SECURITY_PERMISSION_VIOLATION,
            $request,
            'User has no "USER_ADD" permission.',
            42,
        );
    }

    public function testAnonymousUserAndUnknownIpAreLabelled(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('warning')
            ->with($this->logicalAnd($this->stringContains('user=anonymous'), $this->stringContains('ip=unknown')));

        $request = Request::create('/api/v4.0/faqs');
        $request->server->remove('REMOTE_ADDR');

        new SecurityEventLogger($logger)->log(AdminLogType::SECURITY_UNAUTHORIZED_ACCESS, $request, 'nope');
    }

    public function testControlCharactersCannotForgeLogLines(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('warning')
            ->with($this->logicalAnd(
                $this->logicalNot($this->stringContains("\n")),
                $this->stringContains('evil admin'),
            ));

        new SecurityEventLogger($logger)->log(
            AdminLogType::SECURITY_CSRF_VIOLATION,
            Request::create('/'),
            "evil\r\nadmin",
        );
    }
}
