<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PasswordHasher::class)]
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
