<?php

namespace phpMyFAQ\Auth;

use phpMyFAQ\Auth\PasswordHasher;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\User;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AuthDatabaseTest extends TestCase
{
    private AuthDatabase $authDatabase;

    private Configuration $configuration;
    private string $databaseFile;

    protected function setUp(): void
    {
        $this->databaseFile = tempnam(sys_get_temp_dir(), 'phpmyfaq-auth-database-test-');
        copy(PMF_TEST_DIR . '/test.db', $this->databaseFile);

        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databaseFile, '', '');
        $this->configuration = new Configuration($dbHandle);

        $this->authDatabase = new AuthDatabase($this->configuration);
        $this->authDatabase->getEncryptionContainer('sha1');

        // Clean up leftover users from previous runs
        foreach (['testUser', 'testUser2'] as $login) {
            try {
                $this->authDatabase->delete($login);
            } catch (Exception) {
                // Ignore — user may not exist
            }
        }
    }

    protected function tearDown(): void
    {
        foreach (['testUser', 'testUser2'] as $login) {
            try {
                $this->authDatabase->delete($login);
            } catch (Exception) {
                // Ignore — user may not exist
            }
        }

        if (isset($this->databaseFile) && file_exists($this->databaseFile)) {
            @unlink($this->databaseFile);
        }
    }

    /**
     * @throws Exception
     */
    public function testCreate(): void
    {
        $login = 'testUser2';
        $password = 'testPassword2';

        $this->assertTrue($this->authDatabase->create($login, $password));
    }

    /**
     * @throws Exception
     */
    public function testCreateThrowsException(): void
    {
        $login = 'testUser';
        $password = 'testPassword';
        $this->authDatabase->create($login, $password);

        $this->expectException(Exception::class);
        $this->authDatabase->create($login, $password);
    }

    /**
     * @throws Exception
     */
    public function testUpdate(): void
    {
        $login = 'testUser';
        $password = 'newTestPassword';
        $this->authDatabase->create($login, $password);

        $this->assertTrue($this->authDatabase->update($login, $password));
    }

    /**
     * @throws Exception
     */
    public function testUpdateThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->authDatabase->update('nonExistingUser', 'newPassword');
    }

    /**
     * @throws Exception
     */
    public function testDelete(): void
    {
        $login = 'testUser';
        $password = 'testPassword';
        $this->authDatabase->create($login, $password);

        $this->assertTrue($this->authDatabase->delete($login));
    }

    public function testDeleteThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->authDatabase->delete('nonExistingUser');
    }

    public function testCheckCredentials(): void
    {
        $login = 'testUser';
        $password = 'testPassword';
        $this->authDatabase->create($login, $password);

        $this->assertTrue($this->authDatabase->checkCredentials($login, $password));
    }

    public function testCheckCredentialsThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->authDatabase->checkCredentials('nonExistingUser', 'password');
    }

    public function testCheckCredentialsThrowsForIncorrectPassword(): void
    {
        $login = 'testUser';
        $this->authDatabase->create($login, 'correctPassword');

        $this->expectException(AuthException::class);
        $this->authDatabase->checkCredentials($login, 'wrongPassword');
    }

    /**
     * @throws Exception
     */
    public function testIsValidLogin(): void
    {
        $login = 'testUser';
        $password = 'testPassword';
        $this->authDatabase->create($login, $password);

        $this->assertEquals(1, $this->authDatabase->isValidLogin($login));
    }

    /**
     * @throws Exception
     */
    public function testIsValidLoginReturnsZero(): void
    {
        $this->assertEquals(0, $this->authDatabase->isValidLogin('nonExistingUser'));
    }

    /**
     * @throws Exception
     */
    public function testIsValidLoginIsNotInjectable(): void
    {
        $this->authDatabase->create('testUser', 'testPassword');

        $this->assertEquals(0, $this->authDatabase->isValidLogin("' OR '1'='1"));
    }

    public function testCheckCredentialsIsNotInjectable(): void
    {
        $this->authDatabase->create('testUser', 'testPassword');

        $this->expectException(AuthException::class);
        $this->authDatabase->checkCredentials("' OR '1'='1", 'testPassword');
    }

    /**
     * An unknown login must cost about as much time as a known login with a wrong
     * password, otherwise the response time reveals which accounts exist.
     */
    public function testUnknownLoginTakesAsLongAsAWrongPassword(): void
    {
        $authDatabase = $this->createAuthDatabaseWithMeasurableHashingCost();
        $authDatabase->create('testUser', 'correctPassword');

        $wrongPassword = PHP_FLOAT_MAX;
        $unknownLogin = PHP_FLOAT_MAX;
        for ($i = 0; $i < 3; ++$i) {
            $start = hrtime(true);
            try {
                $authDatabase->checkCredentials('testUser', 'wrongPassword');
            } catch (AuthException) {
                // expected
            }
            $wrongPassword = min($wrongPassword, hrtime(true) - $start);

            $start = hrtime(true);
            try {
                $authDatabase->checkCredentials('nonExistingUser', 'wrongPassword');
            } catch (AuthException) {
                // expected
            }
            $unknownLogin = min($unknownLogin, hrtime(true) - $start);
        }

        $this->assertGreaterThanOrEqual(
            $wrongPassword * 0.3,
            $unknownLogin,
            'Rejecting an unknown login must burn a bcrypt verification as well.',
        );
    }

    /**
     * @throws Exception
     */
    public function testIsValidLoginForUnknownLoginBurnsAPasswordVerification(): void
    {
        $authDatabase = $this->createAuthDatabaseWithMeasurableHashingCost();
        $authDatabase->create('testUser', 'correctPassword');

        $start = hrtime(true);
        $this->assertSame(1, $authDatabase->isValidLogin('testUser'));
        $knownLogin = hrtime(true) - $start;

        $start = hrtime(true);
        $this->assertSame(0, $authDatabase->isValidLogin('nonExistingUser'));
        $unknownLogin = hrtime(true) - $start;

        // bcrypt at cost 10 takes well over 10 ms; a plain SELECT takes far less.
        $this->assertGreaterThan(10_000_000, $unknownLogin);
        $this->assertGreaterThan($knownLogin, $unknownLogin);
    }

    /**
     * The suite hashes at the bcrypt minimum (see phpunit.xml), where a verification is
     * too fast to time. Timing tests need a cost that is clearly measurable.
     */
    private function createAuthDatabaseWithMeasurableHashingCost(): AuthDatabase
    {
        $previousCost = $_ENV[PasswordHasher::COST_ENVIRONMENT_VARIABLE] ?? null;
        $_ENV[PasswordHasher::COST_ENVIRONMENT_VARIABLE] = '10';

        try {
            $authDatabase = new AuthDatabase($this->configuration);
        } finally {
            if ($previousCost === null) {
                unset($_ENV[PasswordHasher::COST_ENVIRONMENT_VARIABLE]);
            } else {
                $_ENV[PasswordHasher::COST_ENVIRONMENT_VARIABLE] = $previousCost;
            }
        }

        $authDatabase->getEncryptionContainer('sha1');

        return $authDatabase;
    }

    public function testCreateStoresBcryptHash(): void
    {
        $this->authDatabase->create('testUser', 'testPassword');

        static::assertStringStartsWith('$2y$', $this->readStoredHash('testUser'));
    }

    public function testCheckCredentialsAcceptsLegacySha256AndRehashesToBcrypt(): void
    {
        $login = 'testUser';
        $password = 'legacyPassword';

        $reflection = new \ReflectionClass($this->authDatabase);
        $driver = $reflection->getProperty('databaseDriver')->getValue($this->authDatabase);
        $configuration = $reflection->getParentClass()->getProperty('configuration')->getValue($this->authDatabase);

        // Seed a legacy SHA-256 hash directly, bypassing create() (which now writes bcrypt).
        $salt = $configuration->get('security.salt') . $login;
        $legacyHash = hash('sha256', $password . $salt);
        $driver->query(sprintf(
            "INSERT INTO faquserlogin (login, pass, domain) VALUES ('%s', '%s', '')",
            $driver->escape($login),
            $driver->escape($legacyHash),
        ));

        // Legacy password is accepted...
        static::assertTrue($this->authDatabase->checkCredentials($login, $password));

        // ...and the stored hash has been transparently upgraded to bcrypt.
        static::assertStringStartsWith('$2y$', $this->readStoredHash($login));

        // ...and the same password still verifies against the new bcrypt hash.
        static::assertTrue($this->authDatabase->checkCredentials($login, $password));
    }

    private function readStoredHash(string $login): string
    {
        $reflection = new \ReflectionClass($this->authDatabase);
        $driver = $reflection->getProperty('databaseDriver')->getValue($this->authDatabase);

        $result = $driver->query(sprintf("SELECT pass FROM faquserlogin WHERE login = '%s'", $driver->escape($login)));
        $row = $driver->fetchArray($result);

        return $row['pass'];
    }

    /**
     * Builds an AuthDatabase on a driver whose error() reports the given messages in order, with the
     * given number of matching rows for the login lookups and the given result for write queries.
     *
     * @param list<string> $errors
     */
    private function createAuthDatabaseWithFailingDriver(array $errors, int $numRows, bool $writeResult): AuthDatabase
    {
        $driver = $this->createStub(DatabaseDriver::class);
        $driver->method('escape')->willReturnCallback(static fn(string $value): string => $value);
        $driver->method('queryPrepared')->willReturn(true);
        $driver->method('numRows')->willReturn($numRows);
        $driver->method('query')->willReturn($writeResult);
        $driver->method('error')->willReturnOnConsecutiveCalls(...$errors);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($driver);

        return new AuthDatabase($configuration);
    }

    public function testCreateReportsTheDatabaseError(): void
    {
        $authDatabase = $this->createAuthDatabaseWithFailingDriver(['', 'disk full'], 0, true);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('disk full');

        $authDatabase->create('testUser', 'secret');
    }

    public function testCreateFailsWhenTheInsertIsRejectedWithoutAnError(): void
    {
        $authDatabase = $this->createAuthDatabaseWithFailingDriver(['', ''], 0, false);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage(User::ERROR_USER_ADD);

        $authDatabase->create('testUser', 'secret');
    }

    public function testUpdateReportsTheDatabaseError(): void
    {
        $authDatabase = $this->createAuthDatabaseWithFailingDriver(['', 'table locked'], 1, true);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('table locked');

        $authDatabase->update('testUser', 'newSecret');
    }

    public function testUpdateFailsWhenTheChangeIsRejectedWithoutAnError(): void
    {
        $authDatabase = $this->createAuthDatabaseWithFailingDriver(['', ''], 1, false);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage(User::ERROR_USER_CHANGE);

        $authDatabase->update('testUser', 'newSecret');
    }

    public function testDeleteReportsTheDatabaseError(): void
    {
        $authDatabase = $this->createAuthDatabaseWithFailingDriver(['', 'foreign key'], 1, true);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('foreign key');

        $authDatabase->delete('testUser');
    }

    public function testDeleteFailsWhenTheDeleteIsRejectedWithoutAnError(): void
    {
        $authDatabase = $this->createAuthDatabaseWithFailingDriver(['', ''], 1, false);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage(User::ERROR_USER_DELETE);

        $authDatabase->delete('testUser');
    }

    public function testCheckCredentialsReportsTheDatabaseError(): void
    {
        $authDatabase = $this->createAuthDatabaseWithFailingDriver(['connection lost'], 1, true);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('connection lost');

        $authDatabase->checkCredentials('testUser', 'secret');
    }

    public function testCheckCredentialsRejectsAmbiguousLogins(): void
    {
        $authDatabase = $this->createAuthDatabaseWithFailingDriver([''], 2, true);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage(User::ERROR_USER_LOGIN_NOT_UNIQUE);

        $authDatabase->checkCredentials('testUser', 'secret');
    }

    public function testIsValidLoginReportsTheDatabaseError(): void
    {
        $authDatabase = $this->createAuthDatabaseWithFailingDriver(['no such table'], 0, true);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('no such table');

        $authDatabase->isValidLogin('testUser');
    }
}
