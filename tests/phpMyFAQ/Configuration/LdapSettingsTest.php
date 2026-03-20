<?php

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(LdapSettings::class)]
#[UsesClass(LdapConfiguration::class)]
final class LdapSettingsTest extends TestCase
{
    private string $ldapConfigFile;

    protected function tearDown(): void
    {
        if (isset($this->ldapConfigFile) && is_file($this->ldapConfigFile)) {
            @unlink($this->ldapConfigFile);
        }

        parent::tearDown();
    }

    public function testIsActiveReturnsFalseForStringFalse(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->once())->method('get')->with('ldap.ldapSupport')->willReturn('false');

        $settings = new LdapSettings($configuration);

        self::assertFalse($settings->isActive());
    }

    public function testIsActiveReturnsTrueForStringTrue(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->once())->method('get')->with('ldap.ldapSupport')->willReturn('true');

        $settings = new LdapSettings($configuration);

        self::assertTrue($settings->isActive());
    }

    public function testBuildServersReturnsMainServerOnlyWhenMultipleServersDisabled(): void
    {
        $configuration = $this->createConfigurationStub([
            'ldap.ldap_use_multiple_servers' => false,
        ]);

        $settings = new LdapSettings($configuration);
        $servers = $settings->buildServers($this->createLdapConfiguration());

        self::assertSame(
            [
                [
                    'ldap_server' => 'ldap.example.org',
                    'ldap_port' => 636,
                    'ldap_user' => 'cn=admin,dc=example,dc=org',
                    'ldap_password' => 'secret',
                    'ldap_base' => 'dc=example,dc=org',
                ],
            ],
            $servers,
        );
    }

    public function testBuildServersAppendsAdditionalServersWhenEnabled(): void
    {
        $configuration = $this->createConfigurationStub([
            'ldap.ldap_use_multiple_servers' => true,
        ]);

        $settings = new LdapSettings($configuration);
        $servers = $settings->buildServers($this->createLdapConfiguration());

        self::assertCount(2, $servers);
        self::assertSame('ldap2.example.org', $servers[1]['server']);
        self::assertSame(389, $servers[1]['port']);
        self::assertSame('ou=users,dc=example,dc=org', $servers[1]['base']);
    }

    public function testBuildConfigReturnsNestedLdapConfigurationArrays(): void
    {
        $configuration = $this->createConfigurationStub([
            'ldap.ldap_use_multiple_servers' => true,
            'ldap.ldap_mapping.name' => 'cn',
            'ldap.ldap_mapping.username' => 'uid',
            'ldap.ldap_mapping.mail' => 'mail',
            'ldap.ldap_mapping.memberOf' => 'memberOf',
            'ldap.ldap_use_domain_prefix' => true,
            'ldap.ldap_options.LDAP_OPT_PROTOCOL_VERSION' => 3,
            'ldap.ldap_options.LDAP_OPT_REFERRALS' => 0,
            'ldap.ldap_use_memberOf' => true,
            'ldap.ldap_use_sasl' => false,
            'ldap.ldap_use_anonymous_login' => false,
            'ldap.ldap_use_group_restriction' => true,
            'ldap.ldap_group_allowed_groups' => 'admins,editors',
            'ldap.ldap_group_auto_assign' => true,
            'ldap.ldap_group_mapping' => '{"admins":1,"editors":2}',
        ]);

        $settings = new LdapSettings($configuration);
        $config = $settings->buildConfig();

        self::assertTrue($config['ldap_use_multiple_servers']);
        self::assertSame(
            [
                'name' => 'cn',
                'username' => 'uid',
                'mail' => 'mail',
                'memberOf' => 'memberOf',
            ],
            $config['ldap_mapping'],
        );
        self::assertSame(
            [
                'LDAP_OPT_PROTOCOL_VERSION' => 3,
                'LDAP_OPT_REFERRALS' => 0,
            ],
            $config['ldap_options'],
        );
        self::assertSame(
            [
                'use_group_restriction' => true,
                'allowed_groups' => ['admins', 'editors'],
                'auto_assign' => true,
                'group_mapping' => ['admins' => 1, 'editors' => 2],
            ],
            $config['ldap_group_config'],
        );
    }

    public function testGetLdapGroupConfigReturnsEmptyCollectionsWhenOptionalValuesAreMissing(): void
    {
        $configuration = $this->createConfigurationStub([
            'ldap.ldap_use_group_restriction' => false,
            'ldap.ldap_group_allowed_groups' => '',
            'ldap.ldap_group_auto_assign' => false,
            'ldap.ldap_group_mapping' => '',
        ]);

        $settings = new LdapSettings($configuration);

        self::assertSame(
            [
                'use_group_restriction' => false,
                'allowed_groups' => [],
                'auto_assign' => false,
                'group_mapping' => [],
            ],
            $settings->getLdapGroupConfig(),
        );
    }

    private function createConfigurationStub(array $values): Configuration
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('get')->willReturnCallback(static fn(string $item): mixed => $values[$item] ?? null);

        return $configuration;
    }

    private function createLdapConfiguration(): LdapConfiguration
    {
        $this->ldapConfigFile = PMF_TEST_DIR . '/ldap-settings-' . uniqid('', true) . '.php';
        file_put_contents($this->ldapConfigFile, <<<'PHP'
            <?php
            $PMF_LDAP = [
                'ldap_server' => 'ldap.example.org',
                'ldap_port' => 636,
                'ldap_user' => 'cn=admin,dc=example,dc=org',
                'ldap_password' => 'secret',
                'ldap_base' => 'dc=example,dc=org',
                1 => [
                    'ldap_server' => 'ldap2.example.org',
                    'ldap_port' => 389,
                    'ldap_user' => 'cn=readonly,dc=example,dc=org',
                    'ldap_password' => 'readonly-secret',
                    'ldap_base' => 'ou=users,dc=example,dc=org',
                ],
            ];
            PHP);

        return new LdapConfiguration($this->ldapConfigFile);
    }
}
