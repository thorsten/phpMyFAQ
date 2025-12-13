<?php

namespace phpMyFAQ\Configuration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

/**
 * Test class for LdapConfiguration
 */
#[AllowMockObjectsWithoutExpectations]
class LdapConfigurationTest extends TestCase
{
    private string $testConfigFile;

    protected function setUp(): void
    {
        parent::setUp();

        // Erstelle eine temporäre Konfigurationsdatei für Tests
        $this->testConfigFile = tempnam(sys_get_temp_dir(), 'ldap_config_test_');

        $configContent = '<?php
$PMF_LDAP = [
    "ldap_server" => "ldap://test.example.com",
    "ldap_port" => 389,
    "ldap_user" => "cn=admin,dc=example,dc=com",
    "ldap_password" => "secret123",
    "ldap_base" => "dc=example,dc=com",
    "server1" => [
        "ldap_server" => "ldap://server1.example.com",
        "ldap_port" => 636,
        "ldap_user" => "cn=user1,dc=example,dc=com",
        "ldap_password" => "password1",
        "ldap_base" => "ou=users,dc=example,dc=com"
    ],
    "server2" => [
        "ldap_server" => "ldap://server2.example.com",
        "ldap_port" => 389,
        "ldap_user" => "cn=user2,dc=example,dc=com",
        "ldap_password" => "password2",
        "ldap_base" => "ou=admins,dc=example,dc=com"
    ]
];
';

        file_put_contents($this->testConfigFile, $configContent);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testConfigFile)) {
            unlink($this->testConfigFile);
        }
        parent::tearDown();
    }

    public function testConstructorWithValidConfig(): void
    {
        $config = new LdapConfiguration($this->testConfigFile);
        $this->assertInstanceOf(LdapConfiguration::class, $config);
    }

    public function testGetMainServer(): void
    {
        $config = new LdapConfiguration($this->testConfigFile);
        $this->assertEquals('ldap://test.example.com', $config->getMainServer());
    }

    public function testGetMainPort(): void
    {
        $config = new LdapConfiguration($this->testConfigFile);
        $this->assertEquals(389, $config->getMainPort());
    }

    public function testGetMainUser(): void
    {
        $config = new LdapConfiguration($this->testConfigFile);
        $this->assertEquals('cn=admin,dc=example,dc=com', $config->getMainUser());
    }

    public function testGetMainPassword(): void
    {
        $config = new LdapConfiguration($this->testConfigFile);
        $this->assertEquals('secret123', $config->getMainPassword());
    }

    public function testGetMainBase(): void
    {
        $config = new LdapConfiguration($this->testConfigFile);
        $this->assertEquals('dc=example,dc=com', $config->getMainBase());
    }

    public function testGetServers(): void
    {
        $config = new LdapConfiguration($this->testConfigFile);
        $servers = $config->getServers();

        $this->assertIsArray($servers);
        $this->assertCount(2, $servers);

        // Test server1
        $this->assertArrayHasKey('server1', $servers);
        $server1 = $servers['server1'];
        $this->assertEquals('ldap://server1.example.com', $server1['server']);
        $this->assertEquals(636, $server1['port']);
        $this->assertEquals('cn=user1,dc=example,dc=com', $server1['user']);
        $this->assertEquals('password1', $server1['password']);
        $this->assertEquals('ou=users,dc=example,dc=com', $server1['base']);

        // Test server2
        $this->assertArrayHasKey('server2', $servers);
        $server2 = $servers['server2'];
        $this->assertEquals('ldap://server2.example.com', $server2['server']);
        $this->assertEquals(389, $server2['port']);
        $this->assertEquals('cn=user2,dc=example,dc=com', $server2['user']);
        $this->assertEquals('password2', $server2['password']);
        $this->assertEquals('ou=admins,dc=example,dc=com', $server2['base']);
    }

    public function testConstructorWithMinimalConfig(): void
    {
        $minimalConfigFile = tempnam(sys_get_temp_dir(), 'ldap_minimal_config_test_');
        $configContent = '<?php
$PMF_LDAP = [
    "ldap_server" => "",
    "ldap_port" => 389,
    "ldap_user" => "",
    "ldap_password" => "",
    "ldap_base" => ""
];
';
        file_put_contents($minimalConfigFile, $configContent);

        $config = new LdapConfiguration($minimalConfigFile);

        $this->assertEquals('', $config->getMainServer());
        $this->assertEquals(389, $config->getMainPort());
        $this->assertEquals('', $config->getMainUser());
        $this->assertEquals('', $config->getMainPassword());
        $this->assertEquals('', $config->getMainBase());
        $this->assertEmpty($config->getServers());

        unlink($minimalConfigFile);
    }

    public function testConstructorWithOnlyMainServerConfig(): void
    {
        $singleServerConfigFile = tempnam(sys_get_temp_dir(), 'ldap_single_config_test_');
        $configContent = '<?php
$PMF_LDAP = [
    "ldap_server" => "ldap://single.example.com",
    "ldap_port" => 636,
    "ldap_user" => "cn=single,dc=example,dc=com",
    "ldap_password" => "singlepass",
    "ldap_base" => "ou=single,dc=example,dc=com"
];
';
        file_put_contents($singleServerConfigFile, $configContent);

        $config = new LdapConfiguration($singleServerConfigFile);

        $this->assertEquals('ldap://single.example.com', $config->getMainServer());
        $this->assertEquals(636, $config->getMainPort());
        $this->assertEquals('cn=single,dc=example,dc=com', $config->getMainUser());
        $this->assertEquals('singlepass', $config->getMainPassword());
        $this->assertEquals('ou=single,dc=example,dc=com', $config->getMainBase());
        $this->assertEmpty($config->getServers());

        unlink($singleServerConfigFile);
    }

    public function testConstructorWithMixedArrayElements(): void
    {
        $mixedConfigFile = tempnam(sys_get_temp_dir(), 'ldap_mixed_config_test_');
        $configContent = '<?php
$PMF_LDAP = [
    "ldap_server" => "ldap://main.example.com",
    "ldap_port" => 389,
    "ldap_user" => "cn=main,dc=example,dc=com",
    "ldap_password" => "mainpass",
    "ldap_base" => "dc=example,dc=com",
    "some_string_value" => "not_an_array",
    "some_number" => 12345,
    "backup_server" => [
        "ldap_server" => "ldap://backup.example.com",
        "ldap_port" => 636,
        "ldap_user" => "cn=backup,dc=example,dc=com",
        "ldap_password" => "backuppass",
        "ldap_base" => "ou=backup,dc=example,dc=com"
    ]
];
';
        file_put_contents($mixedConfigFile, $configContent);

        $config = new LdapConfiguration($mixedConfigFile);
        $servers = $config->getServers();

        // Nur Array-Elemente sollten als Server erkannt werden
        $this->assertCount(1, $servers);
        $this->assertArrayHasKey('backup_server', $servers);
        $this->assertArrayNotHasKey('some_string_value', $servers);
        $this->assertArrayNotHasKey('some_number', $servers);

        unlink($mixedConfigFile);
    }

    public function testReadonlyProperties(): void
    {
        $config = new LdapConfiguration($this->testConfigFile);

        // Test dass alle Properties readonly sind (keine Setter verfügbar)
        $reflection = new \ReflectionClass(LdapConfiguration::class);

        $mainServerProperty = $reflection->getProperty('mainServer');
        $this->assertTrue($mainServerProperty->isReadOnly());

        $mainPortProperty = $reflection->getProperty('mainPort');
        $this->assertTrue($mainPortProperty->isReadOnly());

        $mainUserProperty = $reflection->getProperty('mainUser');
        $this->assertTrue($mainUserProperty->isReadOnly());

        $mainPasswordProperty = $reflection->getProperty('mainPassword');
        $this->assertTrue($mainPasswordProperty->isReadOnly());

        $mainBaseProperty = $reflection->getProperty('mainBase');
        $this->assertTrue($mainBaseProperty->isReadOnly());
    }
}
