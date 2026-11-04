<?php

declare(strict_types=1);

namespace phpMyFAQ;

use phpMyFAQ\Enums\AuthenticationSourceType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;

/**
 * The stored password hash doubles as the signing key of password-reset tokens.
 */
#[CoversClass(User::class)]
#[UsesNamespace('phpMyFAQ')]
final class UserPasswordKeyTest extends TestCase
{
    use TestDatabaseTrait;

    private Configuration $configuration;

    protected function setUp(): void
    {
        $this->configuration = $this->createTestConfiguration();
    }

    public function testEncryptedPasswordIsTheStoredHashOfALocalUser(): void
    {
        $user = new User($this->configuration);
        $this->assertTrue($user->getUserById(1, true));

        $db = $this->configuration->getDb();
        $row = $db->fetchArray($db->query(sprintf(
            "SELECT pass FROM %sfaquserlogin WHERE login = '%s'",
            Database::getTablePrefix(),
            $db->escape($user->getLogin()),
        )));

        $this->assertIsArray($row);
        $this->assertNotSame('', $row['pass']);
        $this->assertSame($row['pass'], $user->getEncryptedPassword());
    }

    public function testEncryptedPasswordIsEmptyWithoutALoadedUser(): void
    {
        $this->assertSame('', new User($this->configuration)->getEncryptedPassword());
    }

    /**
     * External identity providers provision accounts through createUser($login, '') and then
     * mark the account with their auth source. The random local hash that createUser() stores
     * must never become a password-reset signing key for such an account, because that hash is
     * not a password the user knows and a reset would hand the account a local password the
     * identity provider never authorized.
     */
    #[DataProvider('externalAuthSources')]
    public function testEncryptedPasswordIsEmptyForAnExternallyProvisionedUser(string $authSource): void
    {
        $user = new User($this->configuration);
        $this->assertTrue($user->createUser('external-' . $authSource, ''));
        $this->assertTrue($user->setAuthSource($authSource));

        $reloaded = new User($this->configuration);
        $this->assertTrue($reloaded->getUserByLogin('external-' . $authSource, false));
        $this->assertSame($authSource, $reloaded->getUserAuthSource());

        $this->assertSame('', $reloaded->getEncryptedPassword());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function externalAuthSources(): iterable
    {
        foreach (AuthenticationSourceType::cases() as $case) {
            if ($case === AuthenticationSourceType::AUTH_LOCAL) {
                continue;
            }

            yield $case->value => [$case->value];
        }
    }

    #[DataProvider('externalAuthSources')]
    public function testOnlyLdapAccountsMayFallBackToALocalPassword(string $authSource): void
    {
        $user = new User($this->configuration);
        $this->assertTrue($user->createUser('external-' . $authSource, ''));
        $this->assertTrue($user->setAuthSource($authSource));

        $reloaded = new User($this->configuration);
        $this->assertTrue($reloaded->getUserByLogin('external-' . $authSource, false));

        $this->assertSame(
            $authSource === AuthenticationSourceType::AUTH_LDAP->value,
            $reloaded->mayAuthenticateWithLocalPassword(),
        );
    }

    public function testLocalAndLegacyAccountsMayAuthenticateWithALocalPassword(): void
    {
        $user = new User($this->configuration);
        $this->assertTrue($user->getUserById(1, true));
        $this->assertTrue($user->mayAuthenticateWithLocalPassword());

        $this->assertTrue($user->setAuthSource(''));
        $legacy = new User($this->configuration);
        $this->assertTrue($legacy->getUserById(1, true));
        $this->assertTrue($legacy->mayAuthenticateWithLocalPassword());
    }

    public function testEncryptedPasswordIsTheStoredHashOfALocallyProvisionedUser(): void
    {
        $user = new User($this->configuration);
        $this->assertTrue($user->createUser('local-user', 'secret'));
        $this->assertTrue($user->setAuthSource(AuthenticationSourceType::AUTH_LOCAL->value));

        $reloaded = new User($this->configuration);
        $this->assertTrue($reloaded->getUserByLogin('local-user', false));

        $this->assertNotSame('', $reloaded->getEncryptedPassword());
    }

    public function testEncryptedPasswordIsEmptyWhenTheLoginRowIsMissing(): void
    {
        $user = new User($this->configuration);
        $this->assertTrue($user->getUserById(1, true));
        $this->configuration
            ->getDb()
            ->query(sprintf(
                "DELETE FROM %sfaquserlogin WHERE login = '%s'",
                Database::getTablePrefix(),
                $this->configuration->getDb()->escape($user->getLogin()),
            ));

        $this->assertSame('', $user->getEncryptedPassword());
    }
}
