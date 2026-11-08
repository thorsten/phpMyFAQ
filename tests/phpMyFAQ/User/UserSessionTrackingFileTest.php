<?php

declare(strict_types=1);

namespace phpMyFAQ\User;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Network;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tracks a visit into a temporary directory to observe the reaction to a tracking file that
 * refuses the write.
 */
#[CoversClass(UserSession::class)]
#[UsesNamespace('phpMyFAQ')]
final class UserSessionTrackingFileTest extends TestCase
{
    private string $trackingDirectory;

    private TestHandler $logHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trackingDirectory = sys_get_temp_dir() . '/pmf-user-session-file-' . bin2hex(random_bytes(6));
        mkdir($this->trackingDirectory, 0o755, true);
        $this->logHandler = new TestHandler();
    }

    protected function tearDown(): void
    {
        foreach (glob($this->trackingDirectory . '/*') ?: [] as $file) {
            chmod($file, 0o644);
            unlink($file);
        }

        rmdir($this->trackingDirectory);

        parent::tearDown();
    }

    public function testUserTrackingReportsATrackingFileThatCannotBeWritten(): void
    {
        $trackingFile = $this->trackingDirectory . '/tracking' . date('dmY');
        file_put_contents($trackingFile, "1;view;1;203.0.113.0;;;Mozilla;2000;\n");
        chmod($trackingFile, 0o444);

        $database = $this->createStub(DatabaseDriver::class);
        $database->method('nextId')->willReturn(55);
        $database->method('query')->willReturn(true);

        $request = Request::create(
            '/index.php',
            'GET',
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => '203.0.113.5',
                'HTTP_USER_AGENT' => 'Mozilla/5.0',
                'REQUEST_TIME' => 2000,
                'SCRIPT_NAME' => '/index.php',
            ],
        );

        $session = new UserSession(
            $this->createConfiguration($database),
            $request,
            fn(Configuration $configuration): Network => $this->createNetwork(),
            null,
            $this->trackingDirectory,
        );

        // PHP warns about the refused write; the warning is not the behaviour under test.
        set_error_handler(static fn(): bool => true);
        try {
            $session->userTracking('view', 99);
        } finally {
            restore_error_handler();
        }

        $this->assertTrue($this->logHandler->hasErrorThatContains('Cannot write to ' . $trackingFile));
        $this->assertStringNotContainsString('55;view;99', (string) file_get_contents($trackingFile));
    }

    private function createConfiguration(DatabaseDriver $database): Configuration
    {
        $items = [
            'main.enableUserTracking' => true,
            'main.botIgnoreList' => 'crawler',
        ];

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($database);
        $configuration->method('getDefaultUrl')->willReturn('https://localhost');
        $configuration->method('getLogger')->willReturn(new Logger('test', [$this->logHandler]));
        $configuration->method('get')->willReturnCallback(static fn(string $item): mixed => $items[$item] ?? null);

        return $configuration;
    }

    private function createNetwork(): Network
    {
        $network = $this->createStub(Network::class);
        $network->method('isBanned')->willReturn(false);

        return $network;
    }
}
