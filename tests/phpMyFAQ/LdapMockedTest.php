<?php

/**
 * Tests for Ldap class using namespace-level function mocking.
 *
 * By defining ldap_* functions in the phpMyFAQ namespace, calls from Ldap.php
 * (which is also in phpMyFAQ namespace) resolve to these mocks instead of the
 * global LDAP extension functions. This allows testing post-search logic
 * without a real LDAP server.
 */

namespace phpMyFAQ;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

// ── Namespace-level LDAP function mocks ─────────────────────────────────────

function ldap_search(mixed $ds, string $base, string $filter, array $attributes = [], ...$rest): mixed
{
    return LdapMockedTest::$mockReturns['ldap_search'] ?? false;
}

function ldap_first_entry(mixed $ds, mixed $result): mixed
{
    return LdapMockedTest::$mockReturns['ldap_first_entry'] ?? false;
}

function ldap_get_entries(mixed $ds, mixed $result): array|false
{
    return LdapMockedTest::$mockReturns['ldap_get_entries'] ?? false;
}

function ldap_get_dn(mixed $ds, mixed $entry): string|false
{
    return LdapMockedTest::$mockReturns['ldap_get_dn'] ?? false;
}

function ldap_errno(mixed $ds): int
{
    return LdapMockedTest::$mockReturns['ldap_errno'] ?? 0;
}

function ldap_error(mixed $ds): string
{
    return LdapMockedTest::$mockReturns['ldap_error'] ?? 'Mocked error';
}

// ── Test class ──────────────────────────────────────────────────────────────

#[CoversClass(Ldap::class)]
#[RequiresPhpExtension('ldap')]
class LdapMockedTest extends TestCase
{
    /** @var array<string, mixed> */
    public static array $mockReturns = [];

    private Configuration $configuration;

    private Ldap $ldap;

    protected function setUp(): void
    {
        parent::setUp();

        self::$mockReturns = [];

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

        // Set a real LDAP connection and base for all tests
        $ds = \ldap_connect('ldap://127.0.0.1:9');
        $this->setPrivateProperty($this->ldap, 'ds', $ds);
        $this->setPrivateProperty($this->ldap, 'base', 'dc=example,dc=com');
    }

    protected function tearDown(): void
    {
        self::$mockReturns = [];
        parent::tearDown();
    }

    // ── getLdapData: first_entry failure ─────────────────────────────────

    public function testGetMailFirstEntryFailure(): void
    {
        self::$mockReturns['ldap_search'] = true; // search succeeds
        self::$mockReturns['ldap_first_entry'] = false; // first_entry fails
        self::$mockReturns['ldap_errno'] = 32;
        self::$mockReturns['ldap_error'] = 'No such object';

        $result = $this->ldap->getMail('testuser');

        $this->assertFalse($result);
        $this->assertEquals(32, $this->ldap->errno);
        $this->assertStringContainsString('Cannot get the value(s)', $this->ldap->error);
    }

    // ── getLdapData: successful entry retrieval ──────────────────────────

    public function testGetMailReturnsValue(): void
    {
        self::$mockReturns['ldap_search'] = true;
        self::$mockReturns['ldap_first_entry'] = true;
        self::$mockReturns['ldap_get_entries'] = [
            'count' => 1,
            0 => [
                'mail' => [
                    'count' => 1,
                    0 => 'user@example.com',
                ],
            ],
        ];

        $result = $this->ldap->getMail('testuser');

        $this->assertEquals('user@example.com', $result);
    }

    public function testGetCompleteNameReturnsValue(): void
    {
        self::$mockReturns['ldap_search'] = true;
        self::$mockReturns['ldap_first_entry'] = true;
        self::$mockReturns['ldap_get_entries'] = [
            'count' => 1,
            0 => [
                'cn' => [
                    'count' => 1,
                    0 => 'John Doe',
                ],
            ],
        ];

        $result = $this->ldap->getCompleteName('testuser');

        $this->assertEquals('John Doe', $result);
    }

    // ── getLdapData: entry without matching field (continue path) ────────

    public function testGetMailReturnsFalseWhenFieldMissing(): void
    {
        self::$mockReturns['ldap_search'] = true;
        self::$mockReturns['ldap_first_entry'] = true;
        self::$mockReturns['ldap_get_entries'] = [
            'count' => 1,
            0 => [
                'otherfld' => [
                    'count' => 1,
                    0 => 'value',
                ],
            ],
        ];

        $result = $this->ldap->getMail('testuser');

        $this->assertFalse($result);
    }

    // ── getLdapData: entry without array for field (continue path) ───────

    public function testGetMailReturnsFalseWhenFieldNotArray(): void
    {
        self::$mockReturns['ldap_search'] = true;
        self::$mockReturns['ldap_first_entry'] = true;
        self::$mockReturns['ldap_get_entries'] = [
            'count' => 1,
            0 => [
                'mail' => 'not-an-array',
            ],
        ];

        $result = $this->ldap->getMail('testuser');

        $this->assertFalse($result);
    }

    // ── getLdapData: entry without index 0 in field array ────────────────

    public function testGetMailReturnsFalseWhenFieldHasNoZeroIndex(): void
    {
        self::$mockReturns['ldap_search'] = true;
        self::$mockReturns['ldap_first_entry'] = true;
        self::$mockReturns['ldap_get_entries'] = [
            'count' => 1,
            0 => [
                'mail' => [
                    'count' => 0,
                    // no index 0
                ],
            ],
        ];

        $result = $this->ldap->getMail('testuser');

        $this->assertFalse($result);
    }

    // ── getLdapData: multiple entries, first without match ───────────────

    public function testGetMailReturnsFromSecondEntry(): void
    {
        self::$mockReturns['ldap_search'] = true;
        self::$mockReturns['ldap_first_entry'] = true;
        self::$mockReturns['ldap_get_entries'] = [
            'count' => 2,
            0 => [
                // Missing the 'mail' field entirely
                'cn' => ['count' => 1, 0 => 'User'],
            ],
            1 => [
                'mail' => ['count' => 1, 0 => 'second@example.com'],
            ],
        ];

        $result = $this->ldap->getMail('testuser');

        $this->assertEquals('second@example.com', $result);
    }

    // ── getLdapData: search failure ──────────────────────────────────────

    public function testGetMailSearchFailure(): void
    {
        self::$mockReturns['ldap_search'] = false;
        self::$mockReturns['ldap_error'] = 'Operations error';

        $result = $this->ldap->getMail('testuser');

        $this->assertFalse($result);
        $this->assertStringContainsString('Unable to search for', $this->ldap->error);
    }

    // ── getLdapData: memberOf filter path ────────────────────────────────

    public function testGetMailWithMemberOfFilterReturnsValue(): void
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
        $ds = \ldap_connect('ldap://127.0.0.1:9');
        $this->setPrivateProperty($ldap, 'ds', $ds);
        $this->setPrivateProperty($ldap, 'base', 'dc=example,dc=com');

        self::$mockReturns['ldap_search'] = true;
        self::$mockReturns['ldap_first_entry'] = true;
        self::$mockReturns['ldap_get_entries'] = [
            'count' => 1,
            0 => [
                'mail' => ['count' => 1, 0 => 'member@example.com'],
            ],
        ];

        $result = $ldap->getMail('testuser');

        $this->assertEquals('member@example.com', $result);
    }

    // ── getLdapDn: first_entry failure ───────────────────────────────────

    public function testGetDnFirstEntryFailure(): void
    {
        self::$mockReturns['ldap_search'] = true;
        self::$mockReturns['ldap_first_entry'] = false;
        self::$mockReturns['ldap_error'] = 'No such object';

        $result = $this->ldap->getDn('testuser');

        $this->assertFalse($result);
        $this->assertStringContainsString('Cannot get the value(s)', $this->ldap->error);
    }

    // ── getLdapDn: success ──────────────────────────────────────────────

    public function testGetDnReturnsValue(): void
    {
        self::$mockReturns['ldap_search'] = true;
        self::$mockReturns['ldap_first_entry'] = true;
        self::$mockReturns['ldap_get_dn'] = 'cn=testuser,dc=example,dc=com';

        $result = $this->ldap->getDn('testuser');

        $this->assertEquals('cn=testuser,dc=example,dc=com', $result);
    }

    // ── getLdapDn: search failure ────────────────────────────────────────

    public function testGetDnSearchFailure(): void
    {
        self::$mockReturns['ldap_search'] = false;
        self::$mockReturns['ldap_error'] = 'Operations error';

        $result = $this->ldap->getDn('testuser');

        $this->assertFalse($result);
        $this->assertStringContainsString('Unable to search for', $this->ldap->error);
    }

    // ── getGroupMemberships: first_entry failure ────────────────────────

    public function testGetGroupMembershipsFirstEntryFailure(): void
    {
        self::$mockReturns['ldap_search'] = true;
        self::$mockReturns['ldap_first_entry'] = false;
        self::$mockReturns['ldap_errno'] = 32;
        self::$mockReturns['ldap_error'] = 'No such object';

        $result = $this->ldap->getGroupMemberships('testuser');

        $this->assertFalse($result);
        $this->assertEquals(32, $this->ldap->errno);
        $this->assertStringContainsString('Cannot get the value(s)', $this->ldap->error);
    }

    // ── getGroupMemberships: success with groups ────────────────────────

    public function testGetGroupMembershipsReturnsGroups(): void
    {
        self::$mockReturns['ldap_search'] = true;
        self::$mockReturns['ldap_first_entry'] = true;
        self::$mockReturns['ldap_get_entries'] = [
            'count' => 1,
            0 => [
                'memberof' => [
                    'count' => 2,
                    0 => 'CN=Group1,DC=example,DC=com',
                    1 => 'CN=Group2,DC=example,DC=com',
                ],
            ],
        ];

        $result = $this->ldap->getGroupMemberships('testuser');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('CN=Group1,DC=example,DC=com', $result[0]);
        $this->assertEquals('CN=Group2,DC=example,DC=com', $result[1]);
    }

    // ── getGroupMemberships: success with no memberof key ───────────────

    public function testGetGroupMembershipsReturnsEmptyWhenNoMemberOf(): void
    {
        self::$mockReturns['ldap_search'] = true;
        self::$mockReturns['ldap_first_entry'] = true;
        self::$mockReturns['ldap_get_entries'] = [
            'count' => 1,
            0 => [
                'cn' => ['count' => 1, 0 => 'testuser'],
                // no 'memberof' key
            ],
        ];

        $result = $this->ldap->getGroupMemberships('testuser');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ── getGroupMemberships: search failure ─────────────────────────────

    public function testGetGroupMembershipsSearchFailure(): void
    {
        self::$mockReturns['ldap_search'] = false;
        self::$mockReturns['ldap_error'] = 'Operations error';

        $result = $this->ldap->getGroupMemberships('testuser');

        $this->assertFalse($result);
        $this->assertStringContainsString('Unable to search for', $this->ldap->error);
    }

    // ── getGroupMemberships: zero count entries ─────────────────────────

    public function testGetGroupMembershipsReturnsEmptyWithZeroCount(): void
    {
        self::$mockReturns['ldap_search'] = true;
        self::$mockReturns['ldap_first_entry'] = true;
        self::$mockReturns['ldap_get_entries'] = [
            'count' => 0,
        ];

        $result = $this->ldap->getGroupMemberships('testuser');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ── helpers ──────────────────────────────────────────────────────────

    private function setPrivateProperty(object $object, string $propertyName, mixed $value): void
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setValue($object, $value);
    }
}
