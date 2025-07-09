<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Ldap AD group functionality
 */
class LdapTest extends TestCase
{
    private Sqlite3 $dbHandle;
    private Configuration $configuration;
    private Ldap $ldap;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($this->dbHandle);
        $this->ldap = new Ldap($this->configuration);
    }

    public function testGetGroupMembershipsMethodExists(): void
    {
        $this->assertTrue(method_exists($this->ldap, 'getGroupMemberships'));
    }

    public function testGetGroupMembershipsWithoutConnection(): void
    {
        // Without a valid LDAP connection, this should return false
        $result = $this->ldap->getGroupMemberships('testuser');
        $this->assertFalse($result);
    }

    public function testExtractGroupNameFromDnInAuthLdap(): void
    {
        // Test the DN parsing functionality indirectly through reflection
        $reflection = new \ReflectionClass(\phpMyFAQ\Auth\AuthLdap::class);

        // Check if the method exists
        $this->assertTrue($reflection->hasMethod('extractGroupNameFromDn'));

        // Since it's a private method, we'll test it using reflection
        $authLdap = new \phpMyFAQ\Auth\AuthLdap($this->configuration);
        $method = $reflection->getMethod('extractGroupNameFromDn');
        $method->setAccessible(true);

        // Test various DN formats
        $testCases = [
            'CN=Domain Users,CN=Users,DC=example,DC=com' => 'Domain Users',
            'CN=Domain Admins,CN=Users,DC=example,DC=com' => 'Domain Admins',
            'CN=Test Group,OU=Groups,DC=example,DC=com' => 'Test Group',
            'InvalidDN' => 'InvalidDN',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $method->invoke($authLdap, $input);
            $this->assertEquals($expected, $result, "Failed for input: $input");
        }
    }

    public function testAssignUserToGroupsMethodExists(): void
    {
        $reflection = new \ReflectionClass(\phpMyFAQ\Auth\AuthLdap::class);
        $this->assertTrue($reflection->hasMethod('assignUserToGroups'));
    }
}
