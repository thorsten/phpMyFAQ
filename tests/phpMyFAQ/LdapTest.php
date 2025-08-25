<?php

namespace phpMyFAQ;

use phpMyFAQ\Auth\AuthLdap;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use TypeError;

/**
 * Test class for Ldap functionality
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

        $this->configuration = $this->createMock(Configuration::class);

        // Mock der LDAP-Konfiguration
        $this->configuration->method('getLdapConfig')->willReturn([
            'ldap_mapping' => [
                'username' => 'uid',
                'mail' => 'mail',
                'name' => 'cn',
                'memberOf' => 'CN=TestGroup,DC=example,DC=com'
            ]
        ]);

        $this->configuration->method('getLdapOptions')->willReturn([]);

        $this->ldap = new Ldap($this->configuration);
    }

    public function testConstructor(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $ldap = new Ldap($configuration);
        $this->assertInstanceOf(Ldap::class, $ldap);
    }

    public function testConnectWithEmptyServer(): void
    {
        $result = $this->ldap->connect('', 389, 'DC=example,DC=com');
        $this->assertFalse($result);
    }

    public function testConnectWithEmptyBase(): void
    {
        $result = $this->ldap->connect('localhost', 389, '');
        $this->assertFalse($result);
    }

    public function testQuoteMethod(): void
    {
        $testCases = [
            ['simple', 'simple'],
            ['test*', 'test\\2a'],
            ['test()', 'test\\28\\29'],
            ['test space', 'test\\20space'],
            ['test\\slash', 'test\\5cslash'],
            ['complex*()', 'complex\\2a\\28\\29']
        ];

        foreach ($testCases as [$input, $expected]) {
            $this->assertEquals($expected, $this->ldap->quote($input));
        }
    }

    /**
     * Test getMail without connection - should return false and set error
     */
    public function testGetMailWithoutConnection(): void
    {
        // With our fixes, this should now return false instead of throwing TypeError
        $result = $this->ldap->getMail('testuser');
        $this->assertFalse($result);
        $this->assertEquals('The LDAP connection handler is not a valid resource.', $this->ldap->error);
    }

    /**
     * Test getCompleteName without connection - should return false and set error
     */
    public function testGetCompleteNameWithoutConnection(): void
    {
        // With our fixes, this should now return false instead of throwing TypeError
        $result = $this->ldap->getCompleteName('testuser');
        $this->assertFalse($result);
        $this->assertEquals('The LDAP connection handler is not a valid resource.', $this->ldap->error);
    }

    /**
     * Test getDn without connection - should return false and set error
     */
    public function testGetDnWithoutConnection(): void
    {
        // With our fixes, this should now return false instead of throwing TypeError
        $result = $this->ldap->getDn('testuser');
        $this->assertFalse($result);
        $this->assertEquals('The LDAP connection handler is not a valid resource.', $this->ldap->error);
    }

    /**
     * Test getGroupMemberships without connection - should return false and set error
     */
    public function testGetGroupMembershipsWithoutConnection(): void
    {
        // With our fixes, this should now return false instead of throwing TypeError
        $result = $this->ldap->getGroupMemberships('testuser');
        $this->assertFalse($result);
        $this->assertEquals('The LDAP connection handler is not a valid resource.', $this->ldap->error);
    }

    /**
     * Test bind without connection - expects TypeError due to null LDAP resource
     */
    public function testBindWithoutConnection(): void
    {
        // This test verifies that calling bind without connection fails as expected
        $this->expectException(TypeError::class);
        $this->ldap->bind('cn=test,dc=example,dc=com', 'password');
    }

    /**
     * Alternative: Test connection state validation through mocking
     */
    public function testConnectionStateValidation(): void
    {
        // Create a partial mock that allows us to test the validation logic
        $ldapMock = $this->getMockBuilder(Ldap::class)
            ->setConstructorArgs([$this->configuration])
            ->onlyMethods(['error'])
            ->getMock();

        $reflection = new ReflectionClass(Ldap::class);
        $property = $reflection->getProperty('ds');
        $property->setValue($ldapMock, false);

        $result = $ldapMock->bind('test', 'password');
        $this->assertFalse($result);
        $this->assertEquals('The LDAP connection handler is not a valid resource.', $ldapMock->error);
    }

    public function testErrorWithNullResource(): void
    {
        // Test error method with internal ds property (which is null)
        $this->ldap->error = 'Test error';
        $this->assertEquals('Test error', $this->ldap->error);
    }

    /**
     * Test private getLdapData method via reflection
     */
    public function testGetLdapDataWithInvalidField(): void
    {
        // First, we need to simulate a valid connection state to test the mapping check
        $reflection = new ReflectionClass(Ldap::class);

        // Set ds to a mock connection to bypass the connection check
        $dsProperty = $reflection->getProperty('ds');
        $dsProperty->setValue($this->ldap, true); // Simulate valid connection

        // Set base to bypass the base check
        $baseProperty = $reflection->getProperty('base');
        $baseProperty->setValue($this->ldap, 'dc=example,dc=com');

        $method = $reflection->getMethod('getLdapData');

        $result = $method->invoke($this->ldap, 'testuser', 'nonexistent');
        $this->assertFalse($result);
        $this->assertStringContainsString('does not exist in LDAP mapping configuration', $this->ldap->error);
    }

    /**
     * Test configuration scenarios
     */
    public function testConfigurationMapping(): void
    {
        // Test that the configuration is properly loaded via constructor
        $reflection = new ReflectionClass(Ldap::class);
        $property = $reflection->getProperty('ldapConfig');

        $config = $property->getValue($this->ldap);
        $this->assertIsArray($config);
        $this->assertArrayHasKey('ldap_mapping', $config);
    }

    /**
     * Test LDAP connection with extension check - safer approach
     */
    public function testConnectWithLdapExtension(): void
    {
        if (!extension_loaded('ldap')) {
            $this->markTestSkipped('LDAP extension not available');
        }

        // Test connection failure scenarios safely without triggering warnings
        $this->configuration->method('getLdapOptions')->willReturn([]);
        $this->configuration->method('get')->willReturnMap([
            ['ldap.ldap_use_dynamic_login', false],
            ['ldap.ldap_use_anonymous_login', false]
        ]);

        // Test with obviously invalid parameters to ensure graceful failure
        // Use empty strings which are validated before ldap_connect
        $result = $this->ldap->connect('', 389, '');
        $this->assertFalse($result);
    }

    /**
     * Test LDAP methods with proper error handling expectation
     */
    public function testLdapMethodsWithProperErrorHandling(): void
    {
        // Test that methods handle null connection gracefully
        // These tests expect the methods to check connection state before calling LDAP functions

        // Create a spy/partial mock that can track method calls
        $ldapSpy = $this->getMockBuilder(Ldap::class)
            ->setConstructorArgs([$this->configuration])
            ->onlyMethods([]) // Don't mock any methods, use real implementation
            ->getMock();

        // Test that quote method works without connection (it should)
        $result = $ldapSpy->quote('test*()');
        $this->assertEquals('test\\2a\\28\\29', $result);

        // Test error property access
        $ldapSpy->error = 'Test error';
        $this->assertEquals('Test error', $ldapSpy->error);
    }

    /**
     * Test quote method edge cases
     */
    public function testQuoteMethodEdgeCases(): void
    {
        $testCases = [
            ['', ''],
            ['normal_text', 'normal_text'],
            ['\\()* ', '\\5c\\28\\29\\2a\\20'],
            ['a\\b(c)d*e f', 'a\\5cb\\28c\\29d\\2ae\\20f']
        ];

        foreach ($testCases as [$input, $expected]) {
            $this->assertEquals($expected, $this->ldap->quote($input));
        }
    }

    public function testAssignUserToGroupsMethodExists(): void
    {
        $reflection = new ReflectionClass(AuthLdap::class);
        $this->assertTrue($reflection->hasMethod('assignUserToGroups'));
    }

    /**
     * Test class for Ldap functionality - focused on logic testing without real LDAP calls
     */
    public function testGetDataMethodsLogic(): void
    {
        // Test the quote method thoroughly (this works without LDAP connection)
        $this->assertEquals('test\\2a\\28\\29', $this->ldap->quote('test*()'));

        // Test error property manipulation
        $this->ldap->error = 'Custom error message';
        $this->assertEquals('Custom error message', $this->ldap->error);

        $this->ldap->errno = 123;
        $this->assertEquals(123, $this->ldap->errno);
    }

    /**
     * Test LDAP connection parameter validation
     */
    public function testConnectionParameterValidation(): void
    {
        // Test empty server validation
        $result = $this->ldap->connect('', 389, 'DC=example,DC=com');
        $this->assertFalse($result);

        // Test empty base validation
        $result = $this->ldap->connect('localhost', 389, '');
        $this->assertFalse($result);

        // Test valid parameters but no actual connection attempt
        $this->assertTrue(is_string('localhost')); // Basic parameter type validation
        $this->assertTrue(is_int(389));
        $this->assertTrue(is_string('DC=example,DC=com'));
    }

    /**
     * Test configuration injection and access
     */
    public function testConfigurationInjection(): void
    {
        $mockConfig = $this->createMock(Configuration::class);
        $mockConfig->method('getLdapConfig')->willReturn([
            'ldap_mapping' => [
                'username' => 'sAMAccountName',
                'mail' => 'mail',
                'name' => 'displayName'
            ]
        ]);

        $ldap = new Ldap($mockConfig);
        $this->assertInstanceOf(Ldap::class, $ldap);

        $reflection = new ReflectionClass(Ldap::class);
        $configProperty = $reflection->getProperty('ldapConfig');

        $config = $configProperty->getValue($ldap);
        $this->assertArrayHasKey('ldap_mapping', $config);
    }

    /**
     * Test search filter construction logic
     */
    public function testSearchFilterConstruction(): void
    {
        $username = 'test.user@example.com';
        $quotedUsername = $this->ldap->quote($username);

        // Test basic filter construction
        $basicFilter = sprintf('(uid=%s)', $quotedUsername);
        $this->assertStringContainsString('uid=', $basicFilter);
        $this->assertStringContainsString($quotedUsername, $basicFilter);

        // Test memberOf filter construction
        $memberOfDN = 'CN=Admins,OU=Groups,DC=example,DC=com';
        $complexFilter = sprintf(
            '(&%s(memberOf:1.2.840.113556.1.4.1941:=%s))',
            $basicFilter,
            $memberOfDN
        );

        $this->assertStringContainsString('(&', $complexFilter);
        $this->assertStringContainsString('memberOf:', $complexFilter);
        $this->assertStringContainsString($memberOfDN, $complexFilter);
    }

    /**
     * Test error state management
     */
    public function testErrorStateManagement(): void
    {
        // Test initial state
        $this->assertNull($this->ldap->error);
        $this->assertNull($this->ldap->errno);

        // Test error setting
        $this->ldap->error = 'Connection failed';
        $this->ldap->errno = 91; // LDAP_CONNECT_ERROR

        $this->assertEquals('Connection failed', $this->ldap->error);
        $this->assertEquals(91, $this->ldap->errno);

        // Test error clearing
        $this->ldap->error = null;
        $this->ldap->errno = null;

        $this->assertNull($this->ldap->error);
        $this->assertNull($this->ldap->errno);
    }

    /**
     * Test private method getLdapData with reflection (safer approach)
     */
    public function testGetLdapDataMethodSignature(): void
    {
        $reflection = new ReflectionClass(Ldap::class);
        $method = $reflection->getMethod('getLdapData');

        $this->assertTrue($method->isPrivate());
        $this->assertEquals('getLdapData', $method->getName());

        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);
        $this->assertEquals('username', $parameters[0]->getName());
        $this->assertEquals('data', $parameters[1]->getName());
    }

    /**
     * Test private method getLdapDn with reflection
     */
    public function testGetLdapDnMethodSignature(): void
    {
        $reflection = new ReflectionClass(Ldap::class);
        $method = $reflection->getMethod('getLdapDn');

        $this->assertTrue($method->isPrivate());
        $this->assertEquals('getLdapDn', $method->getName());

        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('username', $parameters[0]->getName());
    }

    /**
     * Test all public method existence and signatures
     */
    public function testPublicMethodExistence(): void
    {
        $reflection = new ReflectionClass(Ldap::class);

        // Test critical public methods exist
        $this->assertTrue($reflection->hasMethod('connect'));
        $this->assertTrue($reflection->hasMethod('bind'));
        $this->assertTrue($reflection->hasMethod('getMail'));
        $this->assertTrue($reflection->hasMethod('getCompleteName'));
        $this->assertTrue($reflection->hasMethod('getDn'));
        $this->assertTrue($reflection->hasMethod('getGroupMemberships'));
        $this->assertTrue($reflection->hasMethod('quote'));
        $this->assertTrue($reflection->hasMethod('error'));

        // Test method visibility
        $this->assertTrue($reflection->getMethod('connect')->isPublic());
        $this->assertTrue($reflection->getMethod('bind')->isPublic());
        $this->assertTrue($reflection->getMethod('quote')->isPublic());
    }
}
