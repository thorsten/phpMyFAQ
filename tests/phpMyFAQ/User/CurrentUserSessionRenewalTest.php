<?php

declare(strict_types=1);

namespace phpMyFAQ\User;

use phpMyFAQ\Auth\AuthDatabase;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Strings;
use phpMyFAQ\TestDatabaseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use SessionHandler;
use SessionHandlerInterface;

/**
 * Drives the session renewal against a save path phpMyFAQ controls and a session handler that
 * keeps the old session file, so the removal of that file is observable.
 */
#[CoversClass(CurrentUser::class)]
#[UsesNamespace('phpMyFAQ')]
final class CurrentUserSessionRenewalTest extends TestCase
{
    use TestDatabaseTrait;

    private Configuration $configuration;

    private CurrentUser $currentUser;

    private string $sessionDirectory;

    private string $previousSavePath;

    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        $this->configuration = new Configuration($this->connectToTestDatabaseCopy(new Sqlite3()));
        $this->currentUser = new CurrentUser($this->configuration);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $this->sessionDirectory = sys_get_temp_dir() . '/pmf-session-renewal-' . bin2hex(random_bytes(6));
        mkdir($this->sessionDirectory);
        $this->previousSavePath = (string) session_save_path();
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        session_set_save_handler(new SessionHandler(), false);
        session_save_path($this->previousSavePath);
        array_map(unlink(...), glob($this->sessionDirectory . '/sess_*') ?: []);
        rmdir($this->sessionDirectory);

        parent::tearDown();
    }

    public function testUpdateSessionIdRemovesTheLeftoverFileOfTheOldSession(): void
    {
        // A handler that never touches the disk: the regenerated id leaves the old session
        // file behind, exactly the leftover updateSessionId() is meant to clean up.
        session_set_save_handler($this->createMemorySessionHandler(), false);
        session_save_path($this->sessionDirectory);
        session_id('pmfoldsession');
        session_start();

        $oldSessionFile = $this->sessionDirectory . '/sess_pmfoldsession';
        touch($oldSessionFile);

        $this->assertTrue($this->currentUser->updateSessionId());

        $this->assertNotSame('pmfoldsession', session_id());
        $this->assertFileDoesNotExist($oldSessionFile);
    }

    public function testUpdateSessionIdStripsTheHandlerPrefixFromTheSavePath(): void
    {
        session_set_save_handler($this->createMemorySessionHandler(), false);
        session_save_path('0;' . $this->sessionDirectory);
        session_id('pmfprefixedsession');
        session_start();

        $oldSessionFile = $this->sessionDirectory . '/sess_pmfprefixedsession';
        touch($oldSessionFile);

        $this->assertTrue($this->currentUser->updateSessionId());

        $this->assertFileDoesNotExist($oldSessionFile);
    }

    public function testAnUnknownLoginIsLeftToTheLocalDriver(): void
    {
        // The local driver claims the login although there is no account behind it: the
        // account check cannot refuse the password, so the driver is asked and fails it.
        $driver = $this->createMock(AuthDatabase::class);
        $driver->method('isValidLogin')->willReturn(1);
        $driver->expects($this->once())->method('checkCredentials')->with('ghost', 'secret')->willReturn(false);
        $this->currentUser->addAuth($driver, 'local');

        try {
            $this->currentUser->login('ghost', 'secret');
            $this->fail('A failed password check must not log the user in.');
        } catch (UserException $userException) {
            $this->assertSame(\phpMyFAQ\User::ERROR_USER_INCORRECT_PASSWORD, $userException->getMessage());
        }

        $this->assertFalse($this->currentUser->isLoggedIn());
    }

    private function createMemorySessionHandler(): SessionHandlerInterface
    {
        return new class implements SessionHandlerInterface {
            public function open(string $path, string $name): bool
            {
                return true;
            }

            public function close(): bool
            {
                return true;
            }

            public function read(string $id): string
            {
                return '';
            }

            public function write(string $id, string $data): bool
            {
                return true;
            }

            public function destroy(string $id): bool
            {
                return true;
            }

            public function gc(int $max_lifetime): int|false
            {
                return 0;
            }
        };
    }
}
