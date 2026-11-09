<?php

declare(strict_types=1);

namespace phpMyFAQ\User;

use phpMyFAQ\User;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(PasswordResetTokenService::class)]
#[UsesClass(UserException::class)]
class PasswordResetTokenServiceTest extends TestCase
{
    /**
     * A user double whose stored token hash and expiry are captured on issue() and served
     * back on verify(), like the faquser row would.
     */
    private function userWithTokenStorage(int $userId = 42): User&MockObject
    {
        $stored = ['hash' => '', 'expires' => 0];

        $user = $this->createMock(User::class);
        $user->method('getUserId')->willReturn($userId);
        $user->method('storePasswordResetToken')->willReturnCallback(static function (string $hash, int $expires) use (
            &$stored,
        ): bool {
            $stored = ['hash' => $hash, 'expires' => $expires];
            return true;
        });
        $user->method('getPasswordResetToken')->willReturnCallback(static function () use (&$stored): array {
            return $stored;
        });

        return $user;
    }

    public function testIssueAndVerifyRoundTrip(): void
    {
        $service = new PasswordResetTokenService();
        $user = $this->userWithTokenStorage();
        $token = $service->issue($user);

        $this->assertSame(42, $token['userId']);
        $this->assertGreaterThan(time(), $token['expires']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token['signature']);

        $this->assertTrue($service->verify($user, $token['expires'], $token['signature']));
    }

    public function testIssueStoresOnlyTheHashOfTheToken(): void
    {
        $service = new PasswordResetTokenService();
        $user = $this->userWithTokenStorage();
        $token = $service->issue($user);

        $stored = $user->getPasswordResetToken();
        $this->assertSame($token['expires'], $stored['expires']);
        $this->assertNotSame($token['signature'], $stored['hash']);
        $this->assertSame(hash('sha256', $token['signature']), $stored['hash']);
    }

    /**
     * Regression: the stored hash is what a database copy reveals; presenting it as the
     * token must fail.
     */
    public function testVerifyRejectsTheStoredHashPresentedAsToken(): void
    {
        $service = new PasswordResetTokenService();
        $user = $this->userWithTokenStorage();
        $token = $service->issue($user);

        $this->assertFalse($service->verify($user, $token['expires'], $user->getPasswordResetToken()['hash']));
    }

    /**
     * Regression: a token must not be derivable from the user's stored password hash.
     */
    public function testVerifyRejectsTokenDerivedFromThePasswordHash(): void
    {
        $service = new PasswordResetTokenService();
        $user = $this->userWithTokenStorage();
        $token = $service->issue($user);

        $forged = hash_hmac('sha256', '42|' . $token['expires'], '$2y$10$stored-password-hash');

        $this->assertFalse($service->verify($user, $token['expires'], $forged));
    }

    public function testEveryIssueProducesADifferentToken(): void
    {
        $service = new PasswordResetTokenService();

        $first = $service->issue($this->userWithTokenStorage());
        $second = $service->issue($this->userWithTokenStorage());

        $this->assertNotSame($first['signature'], $second['signature']);
    }

    public function testIssuingANewTokenInvalidatesThePreviousOne(): void
    {
        $service = new PasswordResetTokenService();
        $user = $this->userWithTokenStorage();

        $first = $service->issue($user);
        $second = $service->issue($user);

        $this->assertFalse($service->verify($user, $first['expires'], $first['signature']));
        $this->assertTrue($service->verify($user, $second['expires'], $second['signature']));
    }

    public function testIssueThrowsWhenTheTokenCannotBeStored(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getUserId')->willReturn(42);
        $user->method('storePasswordResetToken')->willReturn(false);

        $this->expectException(UserException::class);
        $this->expectExceptionMessage('user 42');

        new PasswordResetTokenService()->issue($user);
    }

    public function testIssueFallsBackToDefaultLifetimeForOutOfRangeValues(): void
    {
        $service = new PasswordResetTokenService();
        $user = $this->userWithTokenStorage();

        $tooShort = $service->issue($user, 10);
        $tooLong = $service->issue($user, PasswordResetTokenService::MAX_LIFETIME_SECONDS + 1);
        $now = time();

        $this->assertEqualsWithDelta(
            $now + PasswordResetTokenService::DEFAULT_LIFETIME_SECONDS,
            $tooShort['expires'],
            2,
        );
        $this->assertEqualsWithDelta(
            $now + PasswordResetTokenService::DEFAULT_LIFETIME_SECONDS,
            $tooLong['expires'],
            2,
        );
        $this->assertTrue($service->verify($user, $tooLong['expires'], $tooLong['signature']));
    }

    public function testVerifyRejectsExpiredToken(): void
    {
        $service = new PasswordResetTokenService();
        $expired = time() - 10;
        $token = bin2hex(random_bytes(32));

        $user = $this->createMock(User::class);
        $user->method('getPasswordResetToken')->willReturn(['hash' => hash('sha256', $token), 'expires' => $expired]);

        $this->assertFalse($service->verify($user, $expired, $token));
    }

    public function testVerifyRejectsTamperedExpiry(): void
    {
        $service = new PasswordResetTokenService();
        $user = $this->userWithTokenStorage();
        $token = $service->issue($user);

        $this->assertFalse($service->verify($user, $token['expires'] + 1, $token['signature']));
    }

    public function testVerifyRejectsTamperedSignature(): void
    {
        $service = new PasswordResetTokenService();
        $user = $this->userWithTokenStorage();
        $token = $service->issue($user);

        $this->assertFalse($service->verify($user, $token['expires'], 'deadbeef'));
    }

    public function testVerifyRejectsWhenNoTokenIsOutstanding(): void
    {
        $service = new PasswordResetTokenService();
        $user = $this->userWithTokenStorage();

        $this->assertFalse($service->verify($user, time() + 60, bin2hex(random_bytes(32))));
    }

    public function testVerifyRejectsEmptyInputs(): void
    {
        $service = new PasswordResetTokenService();
        $user = $this->createMock(User::class);
        $user->expects($this->never())->method('getPasswordResetToken');

        $this->assertFalse($service->verify($user, 0, 'sig'));
        $this->assertFalse($service->verify($user, time() + 60, ''));
    }

    public function testVerifyRejectsExpiryFarInTheFuture(): void
    {
        $service = new PasswordResetTokenService();
        $absurdExpiry = time() + PasswordResetTokenService::MAX_LIFETIME_SECONDS + 1000;
        $token = bin2hex(random_bytes(32));

        $user = $this->createMock(User::class);
        $user->method('getPasswordResetToken')->willReturn([
            'hash' => hash('sha256', $token),
            'expires' => $absurdExpiry,
        ]);

        $this->assertFalse($service->verify($user, $absurdExpiry, $token));
    }
}
