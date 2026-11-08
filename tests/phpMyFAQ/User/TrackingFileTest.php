<?php

declare(strict_types=1);

namespace phpMyFAQ\User;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Request;

/**
 * Writes the tracking log into a temporary directory to observe how the writer reacts to the file.
 */
#[CoversClass(Tracking::class)]
#[UsesNamespace('phpMyFAQ')]
final class TrackingFileTest extends TestCase
{
    private string $trackingDirectory;

    private TestHandler $logHandler;

    protected function setUp(): void
    {
        parent::setUp();

        new ReflectionProperty(Tracking::class, 'tracking')->setValue(null, null);

        $this->trackingDirectory = sys_get_temp_dir() . '/pmf-tracking-file-' . bin2hex(random_bytes(6));
        mkdir($this->trackingDirectory, 0o755, true);
        $this->logHandler = new TestHandler();
    }

    protected function tearDown(): void
    {
        new ReflectionProperty(Tracking::class, 'tracking')->setValue(null, null);

        foreach (glob($this->trackingDirectory . '/*') ?: [] as $file) {
            chmod($file, 0o644);
            unlink($file);
        }

        rmdir($this->trackingDirectory);

        parent::tearDown();
    }

    public function testLogReportsATrackingFileThatCannotBeWritten(): void
    {
        $trackingFile = $this->trackingDirectory . '/tracking' . date('dmY');
        file_put_contents($trackingFile, "1;view;1;203.0.113.0;;;Unit;1234567890;\n");
        chmod($trackingFile, 0o444);

        $request = Request::create(
            '/',
            'GET',
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => '203.0.113.99',
                'HTTP_USER_AGENT' => 'Unit;Test/1.0',
                'REQUEST_TIME' => 1234567890,
            ],
        );
        $database = $this->createStub(DatabaseDriver::class);
        $database->method('nextId')->willReturn(41);
        $database->method('query')->willReturn('result');
        $configuration = $this->createConfiguration($database);

        $tracking = Tracking::getInstance(
            $configuration,
            $request,
            new UserSession($configuration, $request, null, null, $this->trackingDirectory),
            null,
            $this->trackingDirectory,
        );

        // PHP warns about the refused write; the warning is not the behaviour under test.
        set_error_handler(static fn(): bool => true);
        try {
            $this->assertTrue($tracking->log('view', 99));
        } finally {
            restore_error_handler();
        }

        $this->assertTrue($this->logHandler->hasErrorThatContains('Cannot write to ' . $trackingFile));
        $this->assertStringNotContainsString('41;view;99', (string) file_get_contents($trackingFile));
    }

    private function createConfiguration(DatabaseDriver $database): Configuration
    {
        $items = [
            'main.enableUserTracking' => true,
            'main.botIgnoreList' => '__no_bot__',
            'security.bannedIPs' => '',
            'security.permLevel' => 'basic',
        ];

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('get')->willReturnCallback(static fn(string $item): mixed => $items[$item] ?? null);
        $configuration->method('getDb')->willReturn($database);
        $configuration->method('getLogger')->willReturn(new Logger('test', [$this->logHandler]));

        return $configuration;
    }
}
