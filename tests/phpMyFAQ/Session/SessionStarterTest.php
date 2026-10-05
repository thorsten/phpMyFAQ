<?php

namespace phpMyFAQ\Session;

use BadMethodCallException;
use phpMyFAQ\Core\Error;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

/**
 * Stands in for a stored object whose class refuses to be unserialized, like Symfony's session handlers.
 */
final class RefusesUnserialization
{
    public function __unserialize(array $data): void
    {
        throw new BadMethodCallException('Cannot unserialize ' . self::class);
    }
}

class SessionStarterTest extends TestCase
{
    private const string SESSION_ID = 'phpmyfaqsessionstartertest';

    private string $savePath;

    private string $originalLogErrors;

    protected function setUp(): void
    {
        $this->savePath = sys_get_temp_dir() . '/pmf-session-starter-' . uniqid();
        mkdir($this->savePath);

        $this->originalLogErrors = (string) ini_get('log_errors');
        ini_set('log_errors', '0');

        // phpMyFAQ turns every PHP warning into an ErrorException
        set_error_handler([Error::class, 'errorHandler']);
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        ini_set('log_errors', $this->originalLogErrors);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        array_map('unlink', glob($this->savePath . '/sess_*') ?: []);
        rmdir($this->savePath);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testStartsSessionWithValidData(): void
    {
        $this->writeSessionFile('_sf2_attributes|a:1:{s:3:"foo";s:3:"bar";}');

        SessionStarter::start($this->sessionOptions());

        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
        $this->assertSame(['_sf2_attributes' => ['foo' => 'bar']], $_SESSION);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testRecoversFromTruncatedSessionData(): void
    {
        // What PHP writes when serializing a stored object throws halfway through
        $this->writeSessionFile('_sf2_attributes|a:2:{s:3:"foo";s:3:"bar";}_sf2_meta|');

        SessionStarter::start($this->sessionOptions());

        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
        $this->assertSame([], $_SESSION);
        $this->assertSessionIsWritable();
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testRecoversFromObjectsRefusingUnserialization(): void
    {
        $this->writeSessionFile(
            '_sf2_attributes|a:1:{s:3:"foo";O:' . strlen(RefusesUnserialization::class) . ':"'
            . RefusesUnserialization::class . '":0:{}}_sf2_meta|a:1:{s:1:"u";i:1;}'
        );

        SessionStarter::start($this->sessionOptions());

        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
        $this->assertSame([], $_SESSION);
        $this->assertSessionIsWritable();
    }

    private function assertSessionIsWritable(): void
    {
        // PHP may keep the cookie's id or hand out a new one (session.use_strict_mode), both are fine
        $sessionId = session_id();
        $this->assertNotSame('', $sessionId);

        $_SESSION['after'] = 1;
        session_write_close();

        $this->assertSame('after|i:1;', file_get_contents($this->savePath . '/sess_' . $sessionId));
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionOptions(): array
    {
        // like a browser request: the session id is taken from the cookie, also after a restart
        $_COOKIE[session_name()] = self::SESSION_ID;

        return [
            'save_path' => $this->savePath,
            'use_only_cookies' => 1,
            'cache_limiter' => '',
        ];
    }

    private function writeSessionFile(string $payload): void
    {
        file_put_contents($this->sessionFile(), $payload);
    }

    private function sessionFile(): string
    {
        return $this->savePath . '/sess_' . self::SESSION_ID;
    }
}
