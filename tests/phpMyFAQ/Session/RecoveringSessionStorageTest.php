<?php

namespace phpMyFAQ\Session;

use BadMethodCallException;
use phpMyFAQ\Core\Error;
use phpMyFAQ\Environment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

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

#[CoversClass(RecoveringSessionStorage::class)]
#[UsesClass(Error::class)]
#[UsesClass(Environment::class)]
class RecoveringSessionStorageTest extends TestCase
{
    private const string SESSION_ID = 'phpmyfaqrecoveringstoragetest';

    private string $savePath;

    private string $originalLogErrors;

    private string $originalSavePath;

    protected function setUp(): void
    {
        $this->savePath = sys_get_temp_dir() . '/pmf-recovering-session-' . uniqid();
        mkdir($this->savePath);

        $this->originalLogErrors = (string) ini_get('log_errors');
        $this->originalSavePath = (string) ini_get('session.save_path');
        ini_set('log_errors', '0');
        ini_set('session.save_path', $this->savePath);
        ini_set('session.use_only_cookies', '1');

        // phpMyFAQ turns every PHP warning into an ErrorException
        set_error_handler(Error::errorHandler(...));
    }

    protected function tearDown(): void
    {
        restore_error_handler();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        ini_set('log_errors', $this->originalLogErrors);
        ini_set('session.save_path', $this->originalSavePath);

        array_map(unlink(...), glob($this->savePath . '/sess_*') ?: []);
        rmdir($this->savePath);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testStartsSessionWithValidData(): void
    {
        $this->writeSessionFile('_sf2_attributes|a:1:{s:3:"foo";s:3:"bar";}');

        $session = $this->startSession();

        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
        $this->assertSame(['foo' => 'bar'], $session->all());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testRecoversFromTruncatedSessionData(): void
    {
        // What PHP writes when serializing a stored object throws halfway through
        $this->writeSessionFile('_sf2_attributes|a:2:{s:3:"foo";s:3:"bar";}_sf2_meta|');

        $session = $this->startSession();

        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
        $this->assertSame([], $session->all());
        $this->assertSessionIsWritable($session);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testRecoversFromObjectsRefusingUnserialization(): void
    {
        $this->writeSessionFile(
            '_sf2_attributes|a:1:{s:3:"foo";O:'
            . strlen(RefusesUnserialization::class)
            . ':"'
            . RefusesUnserialization::class
            . '":0:{}}_sf2_meta|a:1:{s:1:"u";i:1;}',
        );

        $session = $this->startSession();

        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
        $this->assertSame([], $session->all());
        $this->assertSessionIsWritable($session);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testStartingTwiceIsANoOp(): void
    {
        $this->writeSessionFile('_sf2_attributes|a:1:{s:3:"foo";s:3:"bar";}');

        $storage = new RecoveringSessionStorage();
        $session = $this->startSession($storage);

        $this->assertTrue($storage->start());
        $this->assertSame(['foo' => 'bar'], $session->all());
    }

    private function startSession(?RecoveringSessionStorage $storage = null): Session
    {
        // like a browser request: the session id is taken from the cookie, also after a restart
        $_COOKIE[session_name()] = self::SESSION_ID;

        $session = new Session($storage ?? new RecoveringSessionStorage());
        $session->start();

        return $session;
    }

    private function assertSessionIsWritable(Session $session): void
    {
        // PHP may keep the cookie's id or hand out a new one (session.use_strict_mode), both are fine
        $sessionId = session_id();
        $this->assertNotSame('', $sessionId);

        $session->set('after', 1);
        $session->save();

        $stored = file_get_contents($this->savePath . '/sess_' . $sessionId);
        $this->assertNotFalse($stored);
        $this->assertStringContainsString('s:5:"after";i:1;', $stored);
    }

    private function writeSessionFile(string $payload): void
    {
        file_put_contents($this->savePath . '/sess_' . self::SESSION_ID, $payload);
    }
}
