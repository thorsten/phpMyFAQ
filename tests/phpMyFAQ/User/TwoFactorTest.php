<?php

namespace phpMyFAQ\User;

use phpMyFAQ\Configuration;
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
    private CurrentUser $currentUser;
    private TwoFactor $twoFactor;

    /**
     * @throws Exception
     * @throws TwoFactorAuthException
     */
    protected function setUp(): void
    {
        $this->configuration = $this->createStub(Configuration::class);
        $this->currentUser = $this->createMock(CurrentUser::class);
        $this->twoFactor = new TwoFactor($this->configuration, $this->currentUser);
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
        $this->currentUser->method('getUserData')->with('secret')->willReturn('testsecret');

        $secret = $this->twoFactor->getSecret($this->currentUser);
        $this->assertEquals('testsecret', $secret);
    }

    private function realTwoFactorAuth(): TwoFactorAuth
    {
        return (new ReflectionClass($this->twoFactor))
            ->getProperty('twoFactorAuth')
            ->getValue($this->twoFactor);
    }

    private function replaceTwoFactorAuth(TwoFactorAuth $twoFactorAuth): void
    {
        (new ReflectionClass($this->twoFactor))
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
        $this->currentUser->method('getUserData')->with('secret')->willReturn('testsecret');
        $this->currentUser->method('getUserById')->with(1)->willReturn(true);

        $twoFactorAuth = $this->createMock(TwoFactorAuth::class);
        $twoFactorAuth
            ->expects($this->once())
            ->method('verifyCode')
            ->with('testsecret', '123456', 0)
            ->willReturn(true);

        $this->replaceTwoFactorAuth($twoFactorAuth);

        $this->assertTrue($this->twoFactor->validateToken('123456', 1));
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     * @throws Exception
     */
    public function testValidateToken(): void
    {
        $this->configuration->method('get')->willReturn('basic');

        $secret = $this->twoFactor->generateSecret();
        $this->currentUser->method('getUserData')->with('secret')->willReturn($secret);
        $this->currentUser->method('getUserById')->with(1)->willReturn(true);

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
        $this->currentUser->method('getUserData')->with('secret')->willReturn($secret);
        $this->currentUser->method('getUserById')->with(1)->willReturn(true);

        $previousCode = $this->realTwoFactorAuth()->getCode($secret, time() - self::PERIOD);

        $this->assertFalse($this->twoFactor->validateToken($previousCode, 1));
    }

    public function testValidateTokenRejectsTheNextTimeSlice(): void
    {
        $this->configuration->method('get')->willReturn('basic');

        $secret = $this->twoFactor->generateSecret();
        $this->currentUser->method('getUserData')->with('secret')->willReturn($secret);
        $this->currentUser->method('getUserById')->with(1)->willReturn(true);

        $nextCode = $this->realTwoFactorAuth()->getCode($secret, time() + self::PERIOD);

        $this->assertFalse($this->twoFactor->validateToken($nextCode, 1));
    }

    public function testValidateTokenWithoutASecret(): void
    {
        $this->currentUser->method('getUserData')->with('secret')->willReturn('');
        $this->currentUser->method('getUserById')->with(1)->willReturn(true);

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
        $this->currentUser->method('getUserData')->with('email')->willReturn('user@example.com');
        $this->configuration->method('getDefaultUrl')->willReturn('https://example.com/');

        $qrCodeProvider = $this->createStub(EndroidQrCodeProvider::class);
        $qrCodeProvider->method('getMimeType')->willReturn('image/png');
        $qrCodeProvider->method('getQRCodeImage')->willReturn('fakeimage');

        $reflection = new ReflectionClass($this->twoFactor);
        $property = $reflection->getProperty('endroidQrCodeProvider');
        $property->setValue($this->twoFactor, $qrCodeProvider);

        $qrCode = $this->twoFactor->getQrCode('testsecret');
        $this->assertStringStartsWith('data:image/png;base64,', $qrCode);
    }
}
