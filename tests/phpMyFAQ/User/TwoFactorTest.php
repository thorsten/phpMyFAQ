<?php

namespace phpMyFAQ\User;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RobThree\Auth\Providers\Qr\EndroidQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\TwoFactorAuthException;

#[AllowMockObjectsWithoutExpectations]
class TwoFactorTest extends TestCase
{
    private const int PERIOD = 30;

    private Configuration $configuration;
    private DatabaseDriver $database;
    private CurrentUser $currentUser;
    private TwoFactor $twoFactor;

    /**
     * @throws Exception
     * @throws TwoFactorAuthException
     */
    protected function setUp(): void
    {
        $this->configuration = $this->createMock(Configuration::class);
        $this->database = $this->createMock(DatabaseDriver::class);
        $this->configuration->method('getDb')->willReturn($this->database);
        $this->currentUser = $this->createMock(CurrentUser::class);
        $this->twoFactor = new TwoFactor($this->configuration, $this->currentUser);
    }

    /**
     * Lets the database mock report the given last accepted slice and record the UPDATE.
     *
     * @param list<string> $updates
     */
    private function storeLastAcceptedSlice(?int $lastSlice, array &$updates): void
    {
        $this->database
            ->method('query')
            ->willReturnCallback(static function (string $query) use (&$updates): bool {
                if (str_starts_with($query, 'UPDATE')) {
                    $updates[] = $query;
                }

                return true;
            });
        $this->database->method('numRows')->willReturn(1);
        $this->database->method('fetchArray')->willReturn(['twofactor_last_slice' => $lastSlice]);
    }

    public function testGenerateSecret(): void
    {
        $secret = $this->twoFactor->generateSecret();
        $this->assertIsString($secret);
        $this->assertNotEmpty($secret);
    }

    public function testSaveSecret(): void
    {
        $this->currentUser
            ->expects($this->once())
            ->method('setUserData')
            ->with(['secret' => 'testsecret'])
            ->willReturn(true);

        $result = $this->twoFactor->saveSecret('testsecret');
        $this->assertTrue($result);
    }

    public function testSaveSecretWithEmptyString(): void
    {
        $result = $this->twoFactor->saveSecret('');
        $this->assertFalse($result);
    }

    public function testGetSecret(): void
    {
        $this->currentUser->expects($this->once())->method('getUserData')->with('secret')->willReturn('testsecret');

        $secret = $this->twoFactor->getSecret($this->currentUser);
        $this->assertEquals('testsecret', $secret);
    }

    private function realTwoFactorAuth(): TwoFactorAuth
    {
        return new ReflectionClass($this->twoFactor)
            ->getProperty('twoFactorAuth')
            ->getValue($this->twoFactor);
    }

    private function replaceTwoFactorAuth(TwoFactorAuth $twoFactorAuth): void
    {
        new ReflectionClass($this->twoFactor)
            ->getProperty('twoFactorAuth')
            ->setValue($this->twoFactor, $twoFactorAuth);
    }

    /**
     * Only the current time slice may be accepted, so the acceptance window is at
     * most one period instead of the three slices RobThree allows by default.
     *
     * @throws Exception
     */
    public function testValidateTokenAcceptsOnlyTheCurrentTimeSlice(): void
    {
        $this->currentUser->method('getUserData')->willReturn('testsecret');
        $this->currentUser->method('getUserById')->willReturn(true);

        $twoFactorAuth = $this->createMock(TwoFactorAuth::class);
        $twoFactorAuth->expects($this->once())->method('verifyCode')->with('testsecret', '123456', 0)->willReturn(true);

        $this->replaceTwoFactorAuth($twoFactorAuth);

        $updates = [];
        $this->storeLastAcceptedSlice(null, $updates);

        $this->assertTrue($this->twoFactor->validateToken('123456', 1));
    }

    /**
     * @throws Exception
     */
    public function testValidateTokenRecordsTheAcceptedTimeSlice(): void
    {
        $this->currentUser->method('getUserData')->willReturn('testsecret');
        $this->currentUser->method('getUserById')->willReturn(true);

        $twoFactorAuth = $this->createMock(TwoFactorAuth::class);
        $twoFactorAuth->method('verifyCode')->willReturn(true);
        $this->replaceTwoFactorAuth($twoFactorAuth);

        $updates = [];
        $this->storeLastAcceptedSlice(null, $updates);

        $this->assertTrue($this->twoFactor->validateToken('123456', 1));

        $this->assertCount(1, $updates);
        $this->assertStringContainsString(
            sprintf('twofactor_last_slice = %d WHERE user_id = 1', intdiv(time(), self::PERIOD)),
            $updates[0],
        );
    }

    /**
     * A code that was already accepted for the current slice must not be usable a
     * second time, even though it is still mathematically valid.
     *
     * @throws Exception
     */
    public function testValidateTokenRejectsAReplayWithinTheSameTimeSlice(): void
    {
        $this->currentUser->method('getUserData')->willReturn('testsecret');
        $this->currentUser->method('getUserById')->willReturn(true);

        $twoFactorAuth = $this->createMock(TwoFactorAuth::class);
        $twoFactorAuth->method('verifyCode')->willReturn(true);
        $this->replaceTwoFactorAuth($twoFactorAuth);

        $updates = [];
        $this->storeLastAcceptedSlice(intdiv(time(), self::PERIOD), $updates);

        $this->assertFalse($this->twoFactor->validateToken('123456', 1));
        $this->assertSame([], $updates);
    }

    /**
     * @throws Exception
     */
    public function testValidateTokenRejectsASliceOlderThanTheLastAcceptedOne(): void
    {
        $this->currentUser->method('getUserData')->willReturn('testsecret');
        $this->currentUser->method('getUserById')->willReturn(true);

        $twoFactorAuth = $this->createMock(TwoFactorAuth::class);
        $twoFactorAuth->method('verifyCode')->willReturn(true);
        $this->replaceTwoFactorAuth($twoFactorAuth);

        $updates = [];
        $this->storeLastAcceptedSlice(intdiv(time(), self::PERIOD) + 1, $updates);

        $this->assertFalse($this->twoFactor->validateToken('123456', 1));
        $this->assertSame([], $updates);
    }

    /**
     * @throws Exception
     */
    public function testValidateTokenDoesNotRecordASliceForAWrongCode(): void
    {
        $this->currentUser->method('getUserData')->willReturn('testsecret');
        $this->currentUser->method('getUserById')->willReturn(true);

        $twoFactorAuth = $this->createMock(TwoFactorAuth::class);
        $twoFactorAuth->method('verifyCode')->willReturn(false);
        $this->replaceTwoFactorAuth($twoFactorAuth);

        $this->database->expects($this->never())->method('query');

        $this->assertFalse($this->twoFactor->validateToken('123456', 1));
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     * @throws Exception
     */
    public function testValidateToken(): void
    {
        $this->configuration->method('get')->willReturnCallback(static fn(string $key): ?string => $key
            === 'security.permLevel'
                ? 'basic'
                : null);

        $secret = $this->twoFactor->generateSecret();
        $this->currentUser->method('getUserData')->willReturn($secret);
        $this->currentUser->method('getUserById')->willReturn(true);

        $updates = [];
        $this->storeLastAcceptedSlice(null, $updates);

        // Without any discrepancy tolerance a code generated just before a slice
        // boundary would no longer verify just after it, so retry in that case.
        do {
            $sliceBefore = intdiv(time(), self::PERIOD);
            $result = $this->twoFactor->validateToken($this->realTwoFactorAuth()->getCode($secret), 1);
            $sliceAfter = intdiv(time(), self::PERIOD);
        } while ($sliceBefore !== $sliceAfter);

        $this->assertTrue($result);
    }

    /**
     * The previous code stays mathematically valid but is outside the accepted
     * window, which is what shortens the replay window of a captured code.
     */
    public function testValidateTokenRejectsThePreviousTimeSlice(): void
    {
        $this->configuration->method('get')->willReturn('basic');

        $secret = $this->twoFactor->generateSecret();
        $this->currentUser->method('getUserData')->willReturn($secret);
        $this->currentUser->method('getUserById')->willReturn(true);

        $previousCode = $this->realTwoFactorAuth()->getCode($secret, time() - self::PERIOD);

        $this->assertFalse($this->twoFactor->validateToken($previousCode, 1));
    }

    public function testValidateTokenRejectsTheNextTimeSlice(): void
    {
        $this->configuration->method('get')->willReturn('basic');

        $secret = $this->twoFactor->generateSecret();
        $this->currentUser->method('getUserData')->willReturn($secret);
        $this->currentUser->method('getUserById')->willReturn(true);

        $nextCode = $this->realTwoFactorAuth()->getCode($secret, time() + self::PERIOD);

        $this->assertFalse($this->twoFactor->validateToken($nextCode, 1));
    }

    public function testValidateTokenWithoutASecret(): void
    {
        $this->currentUser->method('getUserData')->willReturn('');
        $this->currentUser->method('getUserById')->willReturn(true);

        $this->assertFalse($this->twoFactor->validateToken('123456', 1));
    }

    public function testValidateTokenWithInvalidLength(): void
    {
        $result = $this->twoFactor->validateToken('12345', 1);
        $this->assertFalse($result);
    }

    public function testGetQrCode(): void
    {
        $this->configuration->method('getTitle')->willReturn('phpMyFAQ');
        $this->currentUser
            ->expects($this->once())
            ->method('getUserData')
            ->with('email')
            ->willReturn('user@example.com');
        $this->configuration->method('getDefaultUrl')->willReturn('https://example.com/');

        $qrCodeProvider = $this->createMock(EndroidQrCodeProvider::class);
        $qrCodeProvider->method('getMimeType')->willReturn('image/png');
        $qrCodeProvider->method('getQRCodeImage')->willReturn('fakeimage');

        $reflection = new ReflectionClass($this->twoFactor);
        $property = $reflection->getProperty('endroidQrCodeProvider');
        $property->setValue($this->twoFactor, $qrCodeProvider);

        $qrCode = $this->twoFactor->getQrCode('testsecret');
        $this->assertStringStartsWith('data:image/png;base64,', $qrCode);
    }
}
