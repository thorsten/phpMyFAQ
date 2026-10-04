<?php

declare(strict_types=1);

namespace phpMyFAQ\Setup;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\System;
use phpMyFAQ\TestDatabaseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use ZipArchive;

/**
 * Drives the single update steps of the runner against the test content
 * directory and a mocked HTTP client, so no step ever reaches the network or
 * the real installation.
 */
#[CoversClass(UpdateRunner::class)]
#[UsesNamespace('phpMyFAQ')]
final class UpdateRunnerTasksTest extends TestCase
{
    use TestDatabaseTrait;

    private Configuration $configuration;

    private BufferedOutput $output;

    private string $upgradeDirectory;

    private string $constantsFile;

    private bool $createdConstantsFile = false;

    private mixed $previousDatabaseDriver = null;

    private mixed $previousDatabaseType = null;

    protected function setUp(): void
    {
        $this->configuration = $this->createTestConfiguration();
        $this->configuration->set('main.currentVersion', '4.0.0');
        $this->configuration->set('main.maintenanceMode', 'true');
        $this->configuration->set('upgrade.releaseEnvironment', 'stable');

        $this->previousDatabaseDriver = new ReflectionProperty(Database::class, 'databaseDriver')->getValue();
        $this->previousDatabaseType = new ReflectionProperty(Database::class, 'dbType')->getValue();
        new ReflectionProperty(Database::class, 'databaseDriver')->setValue(null, $this->configuration->getDb());
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, 'sqlite3');

        $this->upgradeDirectory = PMF_CONTENT_DIR . '/core/upgrades';
        $this->removeUpgradeArtifacts();
        if (!is_dir($this->upgradeDirectory)) {
            mkdir($this->upgradeDirectory, 0o755, true);
        }

        // The health check requires the constants file, which the test content directory does not ship.
        $this->constantsFile = PMF_CONTENT_DIR . '/core/config/constants.php';
        $this->createdConstantsFile = !is_file($this->constantsFile) && touch($this->constantsFile);

        $this->output = new BufferedOutput();
    }

    protected function tearDown(): void
    {
        if ($this->createdConstantsFile) {
            unlink($this->constantsFile);
        }

        $this->removeUpgradeArtifacts();
        if (!is_dir($this->upgradeDirectory)) {
            mkdir($this->upgradeDirectory, 0o755, true);
        }

        new ReflectionProperty(Database::class, 'databaseDriver')->setValue(null, $this->previousDatabaseDriver);
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, $this->previousDatabaseType);
    }

    public function testRunStopsAtTheFirstFailingStep(): void
    {
        // Sign in with Microsoft requires azure.php, which the test content directory does not ship.
        $this->configuration->set('security.enableSignInWithMicrosoft', 'true');
        $httpClient = new MockHttpClient(static function (): never {
            throw new \LogicException('The update check must not run after a failed health check.');
        });

        $result = $this->runner($httpClient)->run($this->style());

        $this->assertSame(Command::FAILURE, $result);
        $rendered = $this->output->fetch();
        $this->assertStringContainsString('Error during health check:', $rendered);
        $this->assertStringContainsString('/content/core/config/azure.php', $rendered);
    }

    public function testHealthCheckWarnsWhenMaintenanceModeIsOff(): void
    {
        $this->configuration->set('main.maintenanceMode', 'false');

        $result = $this->invokeTask('taskHealthCheck', $this->runner());

        $this->assertSame(Command::SUCCESS, $result);
        $this->assertStringContainsString('Health-Check successful.', $this->output->fetch());
        $this->assertTrue(is_dir($this->upgradeDirectory));
    }

    public function testUpdateCheckRemembersTheAvailableVersion(): void
    {
        $runner = $this->runner($this->versionsApi([
            'stable' => '9.9.9',
            'development' => '9.9.9',
            'nightly' => '9.9.9',
        ]));

        $result = $this->invokeTask('taskUpdateCheck', $runner);

        $this->assertSame(Command::SUCCESS, $result);
        $this->assertSame('9.9.9', $this->property($runner, 'version'));
        $this->assertSame('4.0.0', $this->property($runner, 'installedVersion'));
        $this->assertNotSame('', (string) $this->configuration->get('upgrade.dateLastChecked'));
        $this->assertStringContainsString('9.9.9', $this->output->fetch());
    }

    public function testUpdateCheckKeepsTheInstalledVersionWhenUpToDate(): void
    {
        $runner = $this->runner($this->versionsApi([
            'stable' => '4.0.0',
            'development' => '4.0.0',
            'nightly' => '4.0.0',
        ]));

        $result = $this->invokeTask('taskUpdateCheck', $runner);

        $this->assertSame(Command::SUCCESS, $result);
        $this->assertSame('4.0.0', $this->property($runner, 'version'));
        $this->assertSame('4.0.0', $this->property($runner, 'installedVersion'));
        $this->assertStringContainsString('(4.0.0)', $this->output->fetch());
    }

    public function testUpdateCheckFailsWhenTheApiIsUnreachable(): void
    {
        $httpClient = new MockHttpClient(static function (): never {
            throw new TransportException('Could not resolve host: api.phpmyfaq.de');
        });

        $result = $this->invokeTask('taskUpdateCheck', $this->runner($httpClient));

        $this->assertSame(Command::FAILURE, $result);
        $this->assertStringContainsString(
            'Error during update check: Could not resolve host: api.phpmyfaq.de',
            $this->output->fetch(),
        );
    }

    public function testDownloadPackageStoresANightlyWithoutVerification(): void
    {
        $this->configuration->set('upgrade.releaseEnvironment', 'nightly');
        $httpClient = new MockHttpClient([new MockResponse('nightly package bytes')]);
        $runner = $this->runner($httpClient);
        $this->setProperty($runner, 'version', '4.0.0');

        $result = $this->invokeTask('taskDownloadPackage', $runner);

        $this->assertSame(Command::SUCCESS, $result);
        $this->assertSame(1, $httpClient->getRequestsCount());
        $storedPackage = urldecode((string) $this->configuration->get('upgrade.lastDownloadedPackage'));
        $this->assertStringStartsWith($this->upgradeDirectory . '/phpMyFAQ-nightly-', $storedPackage);
        $this->assertSame('nightly package bytes', file_get_contents($storedPackage));
    }

    public function testDownloadPackageAcceptsAReleaseWithMatchingChecksums(): void
    {
        $package = 'release package bytes';
        $httpClient = new MockHttpClient([
            new MockResponse($package),
            new MockResponse(json_encode(['zip' => ['sha256' => hash('sha256', $package)]], JSON_THROW_ON_ERROR)),
        ]);
        $runner = $this->runner($httpClient);
        $this->setProperty($runner, 'version', '4.0.1');

        $result = $this->invokeTask('taskDownloadPackage', $runner);

        $this->assertSame(Command::SUCCESS, $result);
        $this->assertSame(2, $httpClient->getRequestsCount());
        $this->assertSame(
            $this->upgradeDirectory . '/phpMyFAQ-4.0.1.zip',
            urldecode((string) $this->configuration->get('upgrade.lastDownloadedPackage')),
        );
    }

    public function testDownloadPackageRejectsAReleaseWithWrongChecksums(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse('tampered package bytes'),
            new MockResponse(json_encode(['zip' => ['sha256' => str_repeat('0', 64)]], JSON_THROW_ON_ERROR)),
        ]);
        $runner = $this->runner($httpClient);
        $this->setProperty($runner, 'version', '4.0.1');

        $result = $this->invokeTask('taskDownloadPackage', $runner);

        $this->assertSame(Command::FAILURE, $result);
        $this->assertEmpty($this->configuration->get('upgrade.lastDownloadedPackage'));
    }

    public function testExtractPackageUnpacksTheDownloadedArchive(): void
    {
        $archive = $this->upgradeDirectory . '/phpMyFAQ-4.0.1.zip';
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($archive, ZipArchive::CREATE));
        $zip->addFromString('phpmyfaq/index.php', '<?php echo "new version";');
        $zip->close();
        $this->configuration->set('upgrade.lastDownloadedPackage', urlencode($archive));

        $result = $this->invokeTask('taskExtractPackage', $this->runner());

        $this->assertSame(Command::SUCCESS, $result);
        $this->assertFileExists($this->upgradeDirectory . '/new/phpmyfaq/index.php');
        $this->assertStringNotContainsString('[ERROR]', $this->output->fetch());
    }

    public function testExtractPackageFailsWhenTheArchiveIsMissing(): void
    {
        $this->configuration->set('upgrade.lastDownloadedPackage', urlencode($this->upgradeDirectory . '/missing.zip'));

        $result = $this->invokeTask('taskExtractPackage', $this->runner());

        $this->assertSame(Command::FAILURE, $result);
        $this->assertStringContainsString('Given path to download package is not valid.', $this->output->fetch());
    }

    public function testCreateTemporaryBackupFailsWhenTheUpgradeDirectoryIsMissing(): void
    {
        rmdir($this->upgradeDirectory);

        $result = $this->invokeTask('taskCreateTemporaryBackup', $this->runner());

        $this->assertSame(Command::FAILURE, $result);
        $rendered = $this->output->fetch();
        $this->assertStringContainsString('Cannot create backup file: the directory', $rendered);
        $this->assertStringContainsString('Backup failed.', $rendered);
    }

    public function testInstallPackageFailsWithoutAnExtractedPackage(): void
    {
        $result = $this->invokeTask('taskInstallPackage', $this->runner());

        $this->assertSame(Command::FAILURE, $result);
        $rendered = $this->output->fetch();
        $this->assertStringContainsString('The extracted package is missing', $rendered);
        $this->assertStringContainsString('Package installation failed.', $rendered);
    }

    public function testUpdateDatabaseFromTheCurrentVersionAppliesNothingAndLeavesMaintenanceMode(): void
    {
        $this->configuration->set('main.currentVersion', System::getVersion());
        $runner = $this->runner();
        $this->setProperty($runner, 'installedVersion', System::getVersion());

        $result = $this->invokeTask('taskUpdateDatabase', $runner);

        $this->assertSame(Command::SUCCESS, $result);
        $rendered = $this->output->fetch();
        $this->assertStringContainsString('No migrations were applied.', $rendered);
        $this->assertStringContainsString('Database successfully updated.', $rendered);
        $this->assertFalse($this->configuration->get('main.maintenanceMode'));
    }

    public function testCleanupRemovesTheExtractedPackageAndTheArchives(): void
    {
        mkdir($this->upgradeDirectory . '/new/phpmyfaq', 0o755, true);
        file_put_contents($this->upgradeDirectory . '/new/phpmyfaq/index.php', '<?php');
        file_put_contents($this->upgradeDirectory . '/phpMyFAQ-4.0.1.zip', 'zip');
        file_put_contents($this->upgradeDirectory . '/backup.zip', 'zip');

        $result = $this->invokeTask('taskCleanup', $this->runner());

        $this->assertSame(Command::SUCCESS, $result);
        $this->assertDirectoryDoesNotExist($this->upgradeDirectory . '/new');
        $this->assertSame([], glob($this->upgradeDirectory . '/*.zip'));
        $this->assertStringContainsString('Cleanup successful.', $this->output->fetch());
    }

    private function runner(?HttpClientInterface $httpClient = null): UpdateRunner
    {
        return new UpdateRunner($this->configuration, new System(), $httpClient ?? new MockHttpClient());
    }

    private function style(): SymfonyStyle
    {
        return new SymfonyStyle(new ArrayInput([]), $this->output);
    }

    private function invokeTask(string $task, UpdateRunner $runner): int
    {
        return new ReflectionMethod(UpdateRunner::class, $task)->invoke($runner, $this->style());
    }

    private function property(UpdateRunner $runner, string $name): string
    {
        return new ReflectionProperty(UpdateRunner::class, $name)->getValue($runner);
    }

    private function setProperty(UpdateRunner $runner, string $name, string $value): void
    {
        new ReflectionProperty(UpdateRunner::class, $name)->setValue($runner, $value);
    }

    /**
     * @param array<string, string> $versions
     */
    private function versionsApi(array $versions): MockHttpClient
    {
        return new MockHttpClient([
            new MockResponse(json_encode($versions, JSON_THROW_ON_ERROR), [
                'response_headers' => ['content-type' => 'application/json'],
            ]),
        ]);
    }

    private function removeUpgradeArtifacts(): void
    {
        if (!is_dir($this->upgradeDirectory)) {
            return;
        }

        $entries = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->upgradeDirectory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($entries as $entry) {
            $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }
    }
}
