<?php

namespace phpMyFAQ;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(Ldap::class)]
#[RequiresPhpExtension('ldap')]
class LdapTest extends TestCase
{
    private Configuration $configuration;
    private Ldap $ldap;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configuration = $this->createStub(Configuration::class);

        $this->configuration
            ->method('getLdapConfig')
            ->willReturn([
                'ldap_mapping' => [
                    'username' => 'uid',
                    'mail' => 'mail',
                    'name' => 'cn',
                    'memberOf' => 'CN=TestGroup,DC=example,DC=com',
                ],
            ]);

        $this->configuration->method('getLdapOptions')->willReturn([]);

        $this->configuration
            ->method('get')
            ->willReturnCallback(fn(string $item) => match ($item) {
                'ldap.ldap_use_dynamic_login' => false,
                'ldap.ldap_use_anonymous_login' => false,
                'ldap.ldap_dynamic_login_attribute' => 'uid',
                'ldap.ldap_mapping.username' => 'uid',
                'ldap.ldap_use_memberOf' => false,
                'ldap.ldap_mapping.memberOf' => 'CN=TestGroup,DC=example,DC=com',
                default => null,
            });

        $this->ldap = new Ldap($this->configuration);
    }

    public function testConstructorLoadsConfig(): void
    {
        $reflection = new ReflectionClass(Ldap::class);
        $property = $reflection->getProperty('ldapConfig');

        $config = $property->getValue($this->ldap);
        $this->assertIsArray($config);
        $this->assertArrayHasKey('ldap_mapping', $config);
        $this->assertEquals('uid', $config['ldap_mapping']['username']);
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->ldap->error);
        $this->assertNull($this->ldap->errno);
    }

    // ── quote() ──────────────────────────────────────────────────────────

    public function testQuoteReplacesSpecialCharacters(): void
    {
        $this->assertEquals('simple', $this->ldap->quote('simple'));
        $this->assertEquals('test\\2a', $this->ldap->quote('test*'));
        $this->assertEquals('test\\28\\29', $this->ldap->quote('test()'));
        $this->assertEquals('test\\20space', $this->ldap->quote('test space'));
        $this->assertEquals('test\\5cslash', $this->ldap->quote('test\\slash'));
        $this->assertEquals('', $this->ldap->quote(''));
    }

    // ── connect() ────────────────────────────────────────────────────────

    public function testConnectWithEmptyServerReturnsFalse(): void
    {
        $this->assertFalse($this->ldap->connect('', 389, 'DC=example,DC=com'));
    }

    public function testConnectWithEmptyBaseReturnsFalse(): void
    {
        $this->assertFalse($this->ldap->connect('localhost', 389, ''));
    }

    public function testConnectSetsBaseAndCreatesConnection(): void
    {
        // ldap_connect() in PHP 8+ creates a lazy Connection object.
        // The bind will fail since there's no real LDAP server, covering the failure path.
        $result = $this->ldap->connect('ldap://127.0.0.1', 9, 'DC=example,DC=com', 'cn=admin', 'pass');

        // Bind will fail → connect returns false and sets error
        $this->assertFalse($result);
        $this->assertNotNull($this->ldap->error);
        $this->assertStringContainsString('Unable to bind to LDAP server', $this->ldap->error);
    }

    public function testConnectWithDynamicLogin(): void
    {
        $config = $this->createStub(Configuration::class);
        $config
            ->method('getLdapConfig')
            ->willReturn([
                'ldap_mapping' => ['username' => 'uid', 'mail' => 'mail', 'name' => 'cn'],
            ]);
        $config->method('getLdapOptions')->willReturn([]);
        $config
            ->method('get')
            ->willReturnCallback(fn(string $item) => match ($item) {
                'ldap.ldap_use_dynamic_login' => true,
                'ldap.ldap_dynamic_login_attribute' => 'uid',
                default => null,
            });

        $ldap = new Ldap($config);
        $result = $ldap->connect('ldap://127.0.0.1', 9, 'DC=example,DC=com', 'testuser', 'pass');

        $this->assertFalse($result);
        $this->assertStringContainsString('Unable to bind to LDAP server', $ldap->error);
    }

    public function testConnectWithAnonymousLogin(): void
    {
        $config = $this->createStub(Configuration::class);
        $config
            ->method('getLdapConfig')
            ->willReturn([
                'ldap_mapping' => ['username' => 'uid', 'mail' => 'mail', 'name' => 'cn'],
            ]);
        $config->method('getLdapOptions')->willReturn([]);
        $config
            ->method('get')
            ->willReturnCallback(fn(string $item) => match ($item) {
                'ldap.ldap_use_dynamic_login' => false,
                'ldap.ldap_use_anonymous_login' => true,
                default => null,
            });

        $ldap = new Ldap($config);
        $result = $ldap->connect('ldap://127.0.0.1', 9, 'DC=example,DC=com');

        $this->assertFalse($result);
        $this->assertStringContainsString('Unable to bind to LDAP server', $ldap->error);
    }

    public function testConnectWithLdapOptionSuccess(): void
    {
        $config = $this->createStub(Configuration::class);
        $config
            ->method('getLdapConfig')
            ->willReturn([
                'ldap_mapping' => ['username' => 'uid', 'mail' => 'mail', 'name' => 'cn'],
            ]);
        $config
            ->method('getLdapOptions')
            ->willReturn([
                'LDAP_OPT_PROTOCOL_VERSION' => 3,
            ]);
        $config->method('get')->willReturn(null);

        $ldap = new Ldap($config);
        $result = $ldap->connect('ldap://127.0.0.1', 9, 'DC=example,DC=com');

        // Options set successfully, then bind fails
        $this->assertFalse($result);
    }

    public function testConnectBindFailureSetsErrnoAndResetsDs(): void
    {
        $result = $this->ldap->connect('ldap://127.0.0.1', 9, 'DC=example,DC=com', 'admin', 'pass');

        $this->assertFalse($result);
        $this->assertNotNull($this->ldap->errno);
        $this->assertNotNull($this->ldap->error);

        // After bind failure, ds is set to false
        $reflection = new ReflectionClass(Ldap::class);
        $ds = $reflection->getProperty('ds')->getValue($this->ldap);
        $this->assertFalse($ds);
    }

    // ── bind() ───────────────────────────────────────────────────────────

    public function testBindWithFalseConnectionReturnsFalse(): void
    {
        $this->setPrivateProperty($this->ldap, 'ds', false);

        $result = $this->ldap->bind('cn=admin', 'pass');

        $this->assertFalse($result);
        $this->assertEquals('The LDAP connection handler is not a valid resource.', $this->ldap->error);
    }

    public function testBindAnonymousWithConnection(): void
    {
        $ds = ldap_connect('ldap://127.0.0.1:9');
        $this->setPrivateProperty($this->ldap, 'ds', $ds);

        // Anonymous bind to unreachable server will fail
        try {
            $result = $this->ldap->bind();
        } catch (\ErrorException) {
            // PHP error handler may convert the warning to an exception
            $result = false;
        }

        $this->assertFalse($result);
    }

    public function testBindWithCredentialsAndConnection(): void
    {
        $ds = ldap_connect('ldap://127.0.0.1:9');
        $this->setPrivateProperty($this->ldap, 'ds', $ds);

        try {
            $result = $this->ldap->bind('cn=admin,dc=example,dc=com', 'password');
        } catch (\ErrorException) {
            $result = false;
        }

        $this->assertFalse($result);
    }

    // ── getMail() / getCompleteName() – no connection ────────────────────

    public function testGetMailWithoutConnectionReturnsFalse(): void
    {
        $result = $this->ldap->getMail('testuser');
        $this->assertFalse($result);
        $this->assertEquals('The LDAP connection handler is not a valid resource.', $this->ldap->error);
    }

    public function testGetCompleteNameWithoutConnectionReturnsFalse(): void
    {
        $result = $this->ldap->getCompleteName('testuser');
        $this->assertFalse($result);
        $this->assertEquals('The LDAP connection handler is not a valid resource.', $this->ldap->error);
    }

    // ── getDn() – no connection ──────────────────────────────────────────

    public function testGetDnWithoutConnectionReturnsFalse(): void
    {
        $result = $this->ldap->getDn('testuser');
        $this->assertFalse($result);
        $this->assertEquals('The LDAP connection handler is not a valid resource.', $this->ldap->error);
    }

    // ── getGroupMemberships() – no connection ────────────────────────────

    public function testGetGroupMembershipsWithoutConnectionReturnsFalse(): void
    {
        $result = $this->ldap->getGroupMemberships('testuser');
        $this->assertFalse($result);
        $this->assertEquals('The LDAP connection handler is not a valid resource.', $this->ldap->error);
    }

    // ── getLdapData() – base check ───────────────────────────────────────

    public function testGetMailWithNullBaseReturnsFalse(): void
    {
        $ds = ldap_connect('ldap://127.0.0.1:9');
        $this->setPrivateProperty($this->ldap, 'ds', $ds);
        // base is null by default

        $result = $this->ldap->getMail('testuser');
        $this->assertFalse($result);
        $this->assertEquals('LDAP base DN is not configured.', $this->ldap->error);
    }

    public function testGetMailWithEmptyBaseReturnsFalse(): void
    {
        $ds = ldap_connect('ldap://127.0.0.1:9');
        $this->setPrivateProperty($this->ldap, 'ds', $ds);
        $this->setPrivateProperty($this->ldap, 'base', '');

        $result = $this->ldap->getMail('testuser');
        $this->assertFalse($result);
        $this->assertEquals('LDAP base DN is not configured.', $this->ldap->error);
    }

    public function testGetCompleteNameWithNullBaseReturnsFalse(): void
    {
        $ds = ldap_connect('ldap://127.0.0.1:9');
        $this->setPrivateProperty($this->ldap, 'ds', $ds);

        $result = $this->ldap->getCompleteName('testuser');
        $this->assertFalse($result);
        $this->assertEquals('LDAP base DN is not configured.', $this->ldap->error);
    }

    // ── getLdapData() – invalid mapping field ────────────────────────────

    public function testGetLdapDataWithInvalidMappingField(): void
    {
        $ds = ldap_connect('ldap://127.0.0.1:9');
        $this->setPrivateProperty($this->ldap, 'ds', $ds);
        $this->setPrivateProperty($this->ldap, 'base', 'dc=example,dc=com');

        $reflection = new ReflectionClass(Ldap::class);
        $method = $reflection->getMethod('getLdapData');

        $result = $method->invoke($this->ldap, 'testuser', 'nonexistent_field');
        $this->assertFalse($result);
        $this->assertStringContainsString('does not exist in LDAP mapping configuration', $this->ldap->error);
    }

    // ── getLdapData() – search failure ────────────────────────────────────

    public function testGetMailWithSearchFailure(): void
    {
        $ds = ldap_connect('ldap://127.0.0.1:9');
        $this->setPrivateProperty($this->ldap, 'ds', $ds);
        $this->setPrivateProperty($this->ldap, 'base', 'dc=example,dc=com');

        // ldap_search on an unbound connection will fail
        try {
            $result = $this->ldap->getMail('testuser');
        } catch (\ErrorException) {
            $result = false;
        }

        $this->assertFalse($result);
    }

    public function testGetMailWithMemberOfFilter(): void
    {
        $config = $this->createStub(Configuration::class);
        $config
            ->method('getLdapConfig')
            ->willReturn([
                'ldap_mapping' => [
                    'username' => 'uid',
                    'mail' => 'mail',
                    'name' => 'cn',
                    'memberOf' => 'CN=TestGroup,DC=example,DC=com',
                ],
            ]);
        $config->method('getLdapOptions')->willReturn([]);
        $config
            ->method('get')
            ->willReturnCallback(fn(string $item) => match ($item) {
                'ldap.ldap_mapping.username' => 'uid',
                'ldap.ldap_use_memberOf' => true,
                'ldap.ldap_mapping.memberOf' => 'CN=TestGroup,DC=example,DC=com',
                default => null,
            });

        $ldap = new Ldap($config);
        $ds = ldap_connect('ldap://127.0.0.1:9');
        $this->setPrivateProperty($ldap, 'ds', $ds);
        $this->setPrivateProperty($ldap, 'base', 'dc=example,dc=com');

        try {
            $result = $ldap->getMail('testuser');
        } catch (\ErrorException) {
            $result = false;
        }

        $this->assertFalse($result);
    }

    // ── getLdapDn() – base check ─────────────────────────────────────────

    public function testGetDnWithNullBaseReturnsFalse(): void
    {
        $ds = ldap_connect('ldap://127.0.0.1:9');
        $this->setPrivateProperty($this->ldap, 'ds', $ds);

        $result = $this->ldap->getDn('testuser');
        $this->assertFalse($result);
        $this->assertEquals('LDAP base DN is not configured.', $this->ldap->error);
    }

    public function testGetDnWithEmptyBaseReturnsFalse(): void
    {
        $ds = ldap_connect('ldap://127.0.0.1:9');
        $this->setPrivateProperty($this->ldap, 'ds', $ds);
        $this->setPrivateProperty($this->ldap, 'base', '');

        $result = $this->ldap->getDn('testuser');
        $this->assertFalse($result);
        $this->assertEquals('LDAP base DN is not configured.', $this->ldap->error);
    }

    // ── getLdapDn() – search failure ─────────────────────────────────────

    public function testGetDnWithSearchFailure(): void
    {
        $ds = ldap_connect('ldap://127.0.0.1:9');
        $this->setPrivateProperty($this->ldap, 'ds', $ds);
        $this->setPrivateProperty($this->ldap, 'base', 'dc=example,dc=com');

        try {
            $result = $this->ldap->getDn('testuser');
        } catch (\ErrorException) {
            $result = false;
        }

        $this->assertFalse($result);
    }

    // ── getGroupMemberships() – base check ───────────────────────────────

    public function testGetGroupMembershipsWithNullBaseReturnsFalse(): void
    {
        $ds = ldap_connect('ldap://127.0.0.1:9');
        $this->setPrivateProperty($this->ldap, 'ds', $ds);

        $result = $this->ldap->getGroupMemberships('testuser');
        $this->assertFalse($result);
        $this->assertEquals('LDAP base DN is not configured.', $this->ldap->error);
    }

    public function testGetGroupMembershipsWithEmptyBaseReturnsFalse(): void
    {
        $ds = ldap_connect('ldap://127.0.0.1:9');
        $this->setPrivateProperty($this->ldap, 'ds', $ds);
        $this->setPrivateProperty($this->ldap, 'base', '');

        $result = $this->ldap->getGroupMemberships('testuser');
        $this->assertFalse($result);
        $this->assertEquals('LDAP base DN is not configured.', $this->ldap->error);
    }

    // ── getGroupMemberships() – search failure ───────────────────────────

    public function testGetGroupMembershipsWithSearchFailure(): void
    {
        $ds = ldap_connect('ldap://127.0.0.1:9');
        $this->setPrivateProperty($this->ldap, 'ds', $ds);
        $this->setPrivateProperty($this->ldap, 'base', 'dc=example,dc=com');

        try {
            $result = $this->ldap->getGroupMemberships('testuser');
        } catch (\ErrorException) {
            $result = false;
        }

        $this->assertFalse($result);
    }

    // ── error() ──────────────────────────────────────────────────────────

    public function testErrorMethodWithNullDs(): void
    {
        // error() with null ds will call ldap_error(null)
        // In PHP 8+ this triggers TypeError, but when namespace mocks are loaded it returns a string.
        try {
            $result = $this->ldap->error();
            // If namespace mock is active, we get a string back
            $this->assertIsString($result);
        } catch (\TypeError) {
            // Expected when no namespace mock is active
            $this->assertTrue(true);
        }
    }

    public function testErrorMethodWithConnection(): void
    {
        $ds = ldap_connect('ldap://127.0.0.1:9');
        $this->setPrivateProperty($this->ldap, 'ds', $ds);

        $result = $this->ldap->error();
        $this->assertIsString($result);
    }

    public function testErrorMethodWithExplicitResource(): void
    {
        $ds = ldap_connect('ldap://127.0.0.1:9');

        $result = $this->ldap->error($ds);
        $this->assertIsString($result);
    }

    public function testErrorMethodUsesInternalDsWhenNullPassed(): void
    {
        $ds = ldap_connect('ldap://127.0.0.1:9');
        $this->setPrivateProperty($this->ldap, 'ds', $ds);

        // Passing null should use internal $this->ds
        $result = $this->ldap->error(null);
        $this->assertIsString($result);
    }

    // ── helpers ──────────────────────────────────────────────────────────

    private function setPrivateProperty(object $object, string $propertyName, mixed $value): void
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setValue($object, $value);
    }
}
