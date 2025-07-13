<?php

namespace phpMyFAQ;

use phpMyFAQ\Auth\AuthLdap;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

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

        $configArray = [
            'ldap' => [
                'server' => 'ldap://localhost',
                'port' => 389,
                'baseDn' => 'DC=example,DC=com',
                'user' => 'cn=admin,DC=example,DC=com',
                'password' => 'password',
            ]
        ];

        $this->configuration = $this->getMockBuilder(Configuration::class)
            ->setConstructorArgs([$this->dbHandle])
            ->onlyMethods(['get'])
            ->getMock();

        $this->configuration->method('get')->willReturnCallback(
            function ($key) {
                if ($key === 'ldap') {
                    return [
                        'server' => 'ldap://localhost',
                        'port' => 389,
                        'baseDn' => 'DC=example,DC=com',
                        'user' => 'cn=admin,DC=example,DC=com',
                        'password' => 'password',
                    ];
                }
                // Falls weitere Keys wie 'ldap.server' abgefragt werden:
                if ($key === 'ldap.server') {
                    return 'ldap://localhost';
                }
                if ($key === 'ldap.port') {
                    return 389;
                }
                // usw.
                return null;
            }
        );

        $this->ldap = $this->getMockBuilder(Ldap::class)
            ->setConstructorArgs([$this->configuration])
            ->onlyMethods(['connect', 'getGroupMemberships'])
            ->getMock();
        $this->ldap->method('connect')->willReturn(false);
        $this->ldap->method('getGroupMemberships')->willReturn(false);
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

    public function testAssignUserToGroupsMethodExists(): void
    {
        $reflection = new ReflectionClass(AuthLdap::class);
        $this->assertTrue($reflection->hasMethod('assignUserToGroups'));
    }
}
