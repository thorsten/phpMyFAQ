<?php

declare(strict_types=1);

namespace phpMyFAQ;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

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

    public function testEncryptedPasswordIsEmptyForAnExternallyAuthenticatedUser(): void
    {
        $user = new User($this->configuration);
        $this->assertTrue($user->getUserById(1, true));

        $authData = new ReflectionProperty(User::class, 'authData');
        $data = $authData->getValue($user);
        $data['authSource']['name'] = 'ldap';
        $authData->setValue($user, $data);

        $this->assertSame('', $user->getEncryptedPassword());
    }

    public function testEncryptedPasswordIsEmptyWhenTheLoginRowIsMissing(): void
    {
        $user = new User($this->configuration);
        $this->assertTrue($user->getUserById(1, true));
        $this->configuration->getDb()->query(sprintf(
            "DELETE FROM %sfaquserlogin WHERE login = '%s'",
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($user->getLogin()),
        ));

        $this->assertSame('', $user->getEncryptedPassword());
    }
}
