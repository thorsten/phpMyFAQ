<?php

namespace phpMyFAQ\User;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use phpMyFAQ\Configuration;
use ReflectionClass;
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\EndroidQrCodeProvider;
use RobThree\Auth\TwoFactorAuthException;

class TwoFactorTest extends TestCase
{
    private Configuration $configuration;
    private CurrentUser $currentUser;
    private TwoFactor $twoFactor;

    /**
     * @throws Exception
     * @throws TwoFactorAuthException
     */
    protected function setUp(): void
    {
        $this->configuration = $this->createMock(Configuration::class);
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
        $this->currentUser->expects($this->once())
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
        $this->currentUser->method('getUserData')
            ->with('secret')
            ->willReturn('testsecret');

        $secret = $this->twoFactor->getSecret($this->currentUser);
        $this->assertEquals('testsecret', $secret);
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     * @throws Exception
     */
    public function testValidateToken(): void
    {
        $this->configuration->method('get')
            ->with('security.permLevel')
            ->willReturn('basic');

        $this->currentUser->method('getUserData')
            ->with('secret')
            ->willReturn('testsecret');

        $this->currentUser->method('getUserById')
            ->with(1)
            ->willReturn(true);

        $twoFactorAuth = $this->createMock(TwoFactorAuth::class);
        $twoFactorAuth->method('verifyCode')
            ->with('testsecret', '123456')
            ->willReturn(true);

        $reflection = new ReflectionClass($this->twoFactor);
        $property = $reflection->getProperty('twoFactorAuth');
        $property->setValue($this->twoFactor, $twoFactorAuth);

        $result = $this->twoFactor->validateToken('123456', 1);
        $this->assertTrue($result);
    }

    public function testValidateTokenWithInvalidLength(): void
    {
        $result = $this->twoFactor->validateToken('12345', 1);
        $this->assertFalse($result);
    }

    public function testGetQrCode(): void
    {
        $this->configuration->method('getTitle')
            ->willReturn('phpMyFAQ');
        $this->currentUser->method('getUserData')
            ->with('email')
            ->willReturn('user@example.com');
        $this->configuration->method('getDefaultUrl')
            ->willReturn('https://example.com/');

        $qrCodeProvider = $this->createMock(EndroidQrCodeProvider::class);
        $qrCodeProvider->method('getMimeType')
            ->willReturn('image/png');
        $qrCodeProvider->method('getQRCodeImage')
            ->willReturn('fakeimage');

        $reflection = new ReflectionClass($this->twoFactor);
        $property = $reflection->getProperty('endroidQrCodeProvider');
        $property->setValue($this->twoFactor, $qrCodeProvider);

        $qrCode = $this->twoFactor->getQrCode('testsecret');
        $this->assertStringStartsWith('data:image/png;base64,', $qrCode);
    }
}
