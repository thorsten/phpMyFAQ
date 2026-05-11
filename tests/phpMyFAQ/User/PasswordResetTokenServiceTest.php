<?php

declare(strict_types=1);

namespace phpMyFAQ\User;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PasswordResetTokenService::class)]
class PasswordResetTokenServiceTest extends TestCase
{
    public function testIssueAndVerifyRoundTrip(): void
    {
        $service = new PasswordResetTokenService();
        $token = $service->issue(42, 'secret-key');

        $this->assertSame(42, $token['userId']);
        $this->assertGreaterThan(time(), $token['expires']);
        $this->assertNotSame('', $token['signature']);

        $this->assertTrue($service->verify(42, $token['expires'], $token['signature'], 'secret-key'));
    }

    public function testVerifyRejectsExpiredToken(): void
    {
        $service = new PasswordResetTokenService();
        $expired = time() - 10;
        $signature = hash_hmac('sha256', '42|' . $expired, 'secret-key');

        $this->assertFalse($service->verify(42, $expired, $signature, 'secret-key'));
    }

    public function testVerifyRejectsTamperedUserId(): void
    {
        $service = new PasswordResetTokenService();
        $token = $service->issue(42, 'secret-key');

        $this->assertFalse($service->verify(43, $token['expires'], $token['signature'], 'secret-key'));
    }

    public function testVerifyRejectsTamperedSignature(): void
    {
        $service = new PasswordResetTokenService();
        $token = $service->issue(42, 'secret-key');

        $this->assertFalse($service->verify(42, $token['expires'], 'deadbeef', 'secret-key'));
    }

    public function testVerifyRejectsAfterPasswordKeyChange(): void
    {
        $service = new PasswordResetTokenService();
        $token = $service->issue(42, 'old-key');

        $this->assertFalse($service->verify(42, $token['expires'], $token['signature'], 'new-key'));
    }

    public function testVerifyRejectsEmptyInputs(): void
    {
        $service = new PasswordResetTokenService();

        $this->assertFalse($service->verify(0, time() + 60, 'sig', 'k'));
        $this->assertFalse($service->verify(1, 0, 'sig', 'k'));
        $this->assertFalse($service->verify(1, time() + 60, '', 'k'));
        $this->assertFalse($service->verify(1, time() + 60, 'sig', ''));
    }

    public function testVerifyRejectsExpiryFarInTheFuture(): void
    {
        $service = new PasswordResetTokenService();
        $absurdExpiry = time() + PasswordResetTokenService::MAX_LIFETIME_SECONDS + 1000;
        $signature = hash_hmac('sha256', '42|' . $absurdExpiry, 'secret-key');

        $this->assertFalse($service->verify(42, $absurdExpiry, $signature, 'secret-key'));
    }
}
