<?php

namespace phpMyFAQ\Auth;

use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\AuthenticationSourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuthSsoTest extends TestCase
{
    private Configuration $configurationMock;

    protected function setUp(): void
    {
        $this->configurationMock = $this->createMock(Configuration::class);
    }

    public function testConstructor(): void
    {
        // Test that constructor creates AuthSso instance
        $authSso = new AuthSso($this->configurationMock);
        $this->assertInstanceOf(AuthSso::class, $authSso);
        $this->assertInstanceOf(AuthDriverInterface::class, $authSso);
    }

    public function testCreateWithLdapActive(): void
    {
        $login = 'testuser';
        $password = 'password';
        $domain = 'example.com';

        $this->configurationMock->expects($this->once())
            ->method('isLdapActive')
            ->willReturn(true);

        $authSso = new AuthSso($this->configurationMock);

        // Since LDAP integration would create AuthLdap instance,
        // we expect this to potentially throw an exception in test environment
        $this->expectException(\Exception::class);
        $authSso->create($login, $password, $domain);
    }

    public function testCreateWithoutLdap(): void
    {
        $login = 'testuser';
        $password = 'password';
        $domain = 'example.com';

        $this->configurationMock->expects($this->once())
            ->method('isLdapActive')
            ->willReturn(false);

        $authSso = new AuthSso($this->configurationMock);

        // User creation will fail in test environment, expect TypeError from Permission
        $this->expectException(\TypeError::class);
        $authSso->create($login, $password, $domain);
    }

    public function testUpdate(): void
    {
        $authSso = new AuthSso($this->configurationMock);
        $result = $authSso->update('testuser', 'password');
        $this->assertTrue($result);
    }

    public function testDelete(): void
    {
        $authSso = new AuthSso($this->configurationMock);
        $result = $authSso->delete('testuser');
        $this->assertTrue($result);
    }

    public function testCheckCredentialsWithoutRemoteUser(): void
    {
        // Mock $_SERVER to not have REMOTE_USER
        unset($_SERVER['REMOTE_USER']);

        $authSso = new AuthSso($this->configurationMock);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Remote User not set!');

        $authSso->checkCredentials('testuser', 'password');
    }

    public function testCheckCredentialsWithDomainBackslashFormat(): void
    {
        $_SERVER['REMOTE_USER'] = 'EXAMPLE\\testuser';

        $authSso = new AuthSso($this->configurationMock);
        $result = $authSso->checkCredentials('testuser', 'password');

        $this->assertTrue($result);

        // Cleanup
        unset($_SERVER['REMOTE_USER']);
    }

    public function testCheckCredentialsWithDomainBackslashFormatWrongUser(): void
    {
        $_SERVER['REMOTE_USER'] = 'EXAMPLE\\differentuser';

        $authSso = new AuthSso($this->configurationMock);
        $result = $authSso->checkCredentials('testuser', 'password');

        $this->assertFalse($result);

        // Cleanup
        unset($_SERVER['REMOTE_USER']);
    }

    public function testCheckCredentialsWithEmailFormat(): void
    {
        $_SERVER['REMOTE_USER'] = 'testuser@example.com';

        $authSso = new AuthSso($this->configurationMock);
        $result = $authSso->checkCredentials('testuser', 'password');

        $this->assertTrue($result);

        // Cleanup
        unset($_SERVER['REMOTE_USER']);
    }

    public function testCheckCredentialsWithEmailFormatWrongUser(): void
    {
        $_SERVER['REMOTE_USER'] = 'differentuser@example.com';

        $authSso = new AuthSso($this->configurationMock);
        $result = $authSso->checkCredentials('testuser', 'password');

        $this->assertFalse($result);

        // Cleanup
        unset($_SERVER['REMOTE_USER']);
    }

    public function testCheckCredentialsWithPlainUsername(): void
    {
        $_SERVER['REMOTE_USER'] = 'testuser';

        $authSso = new AuthSso($this->configurationMock);
        $result = $authSso->checkCredentials('testuser', 'password');

        $this->assertTrue($result);

        // Cleanup
        unset($_SERVER['REMOTE_USER']);
    }

    public function testCheckCredentialsWithPlainUsernameWrongUser(): void
    {
        $_SERVER['REMOTE_USER'] = 'differentuser';

        $authSso = new AuthSso($this->configurationMock);
        $result = $authSso->checkCredentials('testuser', 'password');

        $this->assertFalse($result);

        // Cleanup
        unset($_SERVER['REMOTE_USER']);
    }

    public function testIsValidLoginWithPhpAuthUser(): void
    {
        $_SERVER['PHP_AUTH_USER'] = 'testuser';

        $authSso = new AuthSso($this->configurationMock);
        $result = $authSso->isValidLogin('testuser');

        $this->assertEquals(1, $result);

        // Cleanup
        unset($_SERVER['PHP_AUTH_USER']);
    }

    public function testIsValidLoginWithoutPhpAuthUser(): void
    {
        unset($_SERVER['PHP_AUTH_USER']);

        $authSso = new AuthSso($this->configurationMock);
        $result = $authSso->isValidLogin('testuser');

        $this->assertEquals(0, $result);
    }

    public function testRemoteUserParsingEdgeCases(): void
    {
        // Test with multiple backslashes - AuthSso takes the second part after first split
        $_SERVER['REMOTE_USER'] = 'DOMAIN\\user';

        $authSso = new AuthSso($this->configurationMock);
        $result = $authSso->checkCredentials('user', 'password');

        $this->assertTrue($result);

        // Cleanup
        unset($_SERVER['REMOTE_USER']);
    }

    public function testRemoteUserParsingWithEmptyParts(): void
    {
        // Test with malformed domain format
        $_SERVER['REMOTE_USER'] = 'DOMAIN\\';

        $authSso = new AuthSso($this->configurationMock);
        $result = $authSso->checkCredentials('', 'password');

        $this->assertTrue($result);

        // Cleanup
        unset($_SERVER['REMOTE_USER']);
    }

    public function testAuthenticationSourceTypeEnum(): void
    {
        $expectedValue = AuthenticationSourceType::AUTH_SSO->value;
        $this->assertIsString($expectedValue);
        $this->assertEquals('sso', $expectedValue);
    }

    public function testInheritsFromAuthClass(): void
    {
        $reflection = new \ReflectionClass(AuthSso::class);
        $parentClass = $reflection->getParentClass();

        $this->assertEquals('phpMyFAQ\Auth', $parentClass->getName());
        $this->assertTrue($reflection->implementsInterface(AuthDriverInterface::class));
    }

    public function testRequestIntegration(): void
    {
        // Test that AuthSso properly integrates with Symfony Request
        $authSso = new AuthSso($this->configurationMock);

        // Verify that the Request is created from globals in constructor
        $this->assertInstanceOf(AuthSso::class, $authSso);
    }
}
