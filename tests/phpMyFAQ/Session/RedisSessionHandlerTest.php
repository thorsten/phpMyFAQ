<?php

declare(strict_types=1);

namespace phpMyFAQ\Session;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(RedisSessionHandler::class)]
#[UsesClass(Token::class)]
class RedisSessionHandlerTest extends TestCase
{
    public function testConfigureThrowsWhenRedisExtensionNotLoaded(): void
    {
        if (extension_loaded('redis')) {
            static::markTestSkipped('Test requires the redis extension to NOT be loaded.');
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Redis session handler requires the PHP redis extension');

        RedisSessionHandler::configure();
    }

    public function testConfigureWithoutValidation(): void
    {
        if (!extension_loaded('redis')) {
            static::markTestSkipped('Test requires the redis extension.');
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $originalPath = ini_get('session.save_path');

        try {
            RedisSessionHandler::configure(dsn: 'tcp://127.0.0.1:6379', validate: false);

            static::assertSame('redis', ini_get('session.save_handler'));
            static::assertSame('tcp://127.0.0.1:6379', ini_get('session.save_path'));
        } finally {
            // The original 'user' handler cannot be restored via ini_set;
            // reset to 'files' to avoid poisoning subsequent tests
            ini_set('session.save_handler', 'files');
            ini_set('session.save_path', $originalPath);
        }
    }

    public function testConfigureUsesDefaultDsnWhenEmpty(): void
    {
        if (!extension_loaded('redis')) {
            static::markTestSkipped('Test requires the redis extension.');
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $originalPath = ini_get('session.save_path');

        try {
            RedisSessionHandler::configure(dsn: '', validate: false);

            static::assertSame('redis', ini_get('session.save_handler'));
            static::assertSame(RedisSessionHandler::DEFAULT_DSN, ini_get('session.save_path'));
        } finally {
            // The original 'user' handler cannot be restored via ini_set;
            // reset to 'files' to avoid poisoning subsequent tests
            ini_set('session.save_handler', 'files');
            ini_set('session.save_path', $originalPath);
        }
    }

    public function testValidateConnectionThrowsForUnsupportedScheme(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported Redis DSN scheme "http"');

        RedisSessionHandler::validateConnection('http://redis:6379');
    }

    public function testValidateConnectionThrowsForUnreachableTarget(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Redis connection failed');

        RedisSessionHandler::validateConnection(dsn: 'tcp://127.0.0.1:1', timeoutSeconds: 0.1);
    }

    public function testValidateConnectionThrowsForInvalidDsn(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid Redis DSN for sessions.');

        RedisSessionHandler::validateConnection('not-a-valid-dsn');
    }

    public function testValidateConnectionThrowsForEmptyUnixPath(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid Redis unix socket DSN for sessions.');

        // unix: with no path triggers the empty-path guard
        RedisSessionHandler::validateConnection('unix:');
    }

    public function testValidateConnectionUsesRedisScheme(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Redis connection failed');

        RedisSessionHandler::validateConnection(dsn: 'redis://127.0.0.1:1', timeoutSeconds: 0.1);
    }

    public function testValidateConnectionUsesUnixSchemeWithUnreachableSocket(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Redis connection failed');

        // unix:/path is parsed by parse_url as scheme=unix, path=/path
        RedisSessionHandler::validateConnection(dsn: 'unix:/tmp/nonexistent-redis.sock', timeoutSeconds: 0.1);
    }

    public function testValidateConnectionThrowsForMalformedDsn(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid Redis DSN for sessions.');

        RedisSessionHandler::validateConnection('://');
    }

    public function testValidateConnectionAcceptsServerAnsweringPong(): void
    {
        [$process, $port] = $this->startFakeRedisServer("+PONG\r\n");

        try {
            RedisSessionHandler::validateConnection(sprintf('tcp://127.0.0.1:%d?database=0', $port));
            $this->addToAssertionCount(1);
        } finally {
            proc_close($process);
        }
    }

    public function testValidateConnectionRejectsServerNotAnsweringPong(): void
    {
        [$process, $port] = $this->startFakeRedisServer("-ERR unknown command\r\n");

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage(sprintf('Redis connection failed for 127.0.0.1:%d.', $port));
            RedisSessionHandler::validateConnection(sprintf('tcp://127.0.0.1:%d', $port));
        } finally {
            proc_close($process);
        }
    }

    /**
     * Starts a one-shot TCP server in a child PHP process that answers the first line with $reply.
     *
     * @return array{0: resource, 1: int}
     */
    private function startFakeRedisServer(string $reply): array
    {
        $script = <<<'PHP'
            $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
            if ($server === false) {
                fwrite(STDERR, $errstr);
                exit(1);
            }
            $name = stream_socket_get_name($server, false);
            fwrite(STDOUT, substr($name, strrpos($name, ':') + 1) . "\n");
            fflush(STDOUT);
            $connection = stream_socket_accept($server, 10);
            if ($connection !== false) {
                fgets($connection);
                fwrite($connection, $argv[1]);
                fclose($connection);
            }
            fclose($server);
            PHP;

        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, '-r', $script, '--', $reply],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        if ($process === false) {
            static::markTestSkipped('Unable to start the fake Redis server process.');
        }

        $port = (int) trim((string) fgets($pipes[1]));
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        if ($port <= 0) {
            proc_close($process);
            static::markTestSkipped('The fake Redis server did not report a port.');
        }

        return [$process, $port];
    }
}
