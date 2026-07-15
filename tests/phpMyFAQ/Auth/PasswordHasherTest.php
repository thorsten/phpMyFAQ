<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth;

use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\ConfigurationRepository;
use phpMyFAQ\Configuration\LayoutSettings;
use phpMyFAQ\Configuration\LdapSettings;
use phpMyFAQ\Configuration\MailSettings;
use phpMyFAQ\Configuration\SearchSettings;
use phpMyFAQ\Configuration\SecuritySettings;
use phpMyFAQ\Configuration\Storage\ConfigurationStorageSettings;
use phpMyFAQ\Configuration\Storage\ConfigurationStorageSettingsResolver;
use phpMyFAQ\Configuration\Storage\DatabaseConfigurationStore;
use phpMyFAQ\Configuration\Storage\HybridConfigurationStore;
use phpMyFAQ\Configuration\UrlSettings;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Environment;
use phpMyFAQ\Plugin\PluginManager;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use phpMyFAQ\Configuration\Storage\FilesystemConfigurationCache;
use phpMyFAQ\Plugin\PluginDiscovery;

#[CoversClass(PasswordHasher::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(ConfigurationRepository::class)]
#[UsesClass(LayoutSettings::class)]
#[UsesClass(LdapSettings::class)]
#[UsesClass(MailSettings::class)]
#[UsesClass(SearchSettings::class)]
#[UsesClass(SecuritySettings::class)]
#[UsesClass(ConfigurationStorageSettings::class)]
#[UsesClass(ConfigurationStorageSettingsResolver::class)]
#[UsesClass(DatabaseConfigurationStore::class)]
#[UsesClass(HybridConfigurationStore::class)]
#[UsesClass(UrlSettings::class)]
#[UsesClass(Database::class)]
#[UsesClass(Sqlite3::class)]
#[UsesClass(Environment::class)]
#[UsesClass(PluginManager::class)]
#[UsesClass(System::class)]
#[UsesClass(Translation::class)]
#[UsesClass(FilesystemConfigurationCache::class)]
#[UsesClass(PluginDiscovery::class)]
class PasswordHasherTest extends TestCase
{
    private PasswordHasher $passwordHasher;
    private Configuration $configuration;
    private string $databaseFile;
    private string $salt;

    protected function setUp(): void
    {
        $this->databaseFile = tempnam(sys_get_temp_dir(), 'pmf-password-hasher-test-');
        copy(PMF_TEST_DIR . '/test.db', $this->databaseFile);

        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databaseFile, '', '');
        $this->configuration = new Configuration($dbHandle);

        $this->salt = (string) $this->configuration->get('security.salt');
        $this->passwordHasher = new PasswordHasher($this->configuration);
    }

    protected function tearDown(): void
    {
        if (isset($this->databaseFile) && file_exists($this->databaseFile)) {
            @unlink($this->databaseFile);
        }
    }

    public function testHashProducesBcryptHash(): void
    {
        $hash = $this->passwordHasher->hash('secret');

        static::assertStringStartsWith('$2y$', $hash);
        static::assertNotSame('unknown', password_get_info($hash)['algoName']);
    }

    public function testVerifyAcceptsBcryptPassword(): void
    {
        $hash = $this->passwordHasher->hash('secret');

        static::assertTrue($this->passwordHasher->verify('jdoe', 'secret', $hash));
        static::assertFalse($this->passwordHasher->verify('jdoe', 'wrong', $hash));
    }

    public function testVerifyAcceptsLegacySha256Password(): void
    {
        $login = 'jdoe';
        $legacyHash = hash('sha256', 'secret' . $this->salt . $login);

        static::assertTrue($this->passwordHasher->verify($login, 'secret', $legacyHash));
        static::assertFalse($this->passwordHasher->verify($login, 'wrong', $legacyHash));
    }

    public function testNeedsRehashIsTrueForLegacyHashAndFalseForBcrypt(): void
    {
        $legacyHash = hash('sha256', 'secret' . $this->salt . 'jdoe');

        static::assertTrue($this->passwordHasher->needsRehash($legacyHash));
        static::assertFalse($this->passwordHasher->needsRehash($this->passwordHasher->hash('secret')));
    }
}
