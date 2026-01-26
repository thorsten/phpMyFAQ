<?php

/**
 * The Upgrade runner class
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-10-18
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup;

use DateTime;
use DateTimeInterface;
use Exception;
use phpMyFAQ\Administration\Api;
use phpMyFAQ\Configuration;
use phpMyFAQ\Setup\Migration\MigrationResult;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Throwable;

final class UpdateRunner
{
    public function __construct(
        private readonly Configuration $configuration,
        private readonly System $system,
    ) {
    }

    public function run(SymfonyStyle $symfonyStyle): int
    {
        $steps = [
            'taskHealthCheck',
            'taskUpdateCheck',
            'taskDownloadPackage',
            'taskExtractPackage',
            'taskCreateTemporaryBackup',
            'taskInstallPackage',
            'taskUpdateDatabase',
            'taskCleanup',
        ];

        foreach ($steps as $step) {
            $result = $this->{$step}($symfonyStyle);
            if (Command::SUCCESS !== $result) {
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Runs the update in dry-run mode, showing what would be executed without making changes.
     */
    public function runDryRun(SymfonyStyle $symfonyStyle, string $fromVersion): int
    {
        $symfonyStyle->title('phpMyFAQ Migration Dry-Run Report');
        $symfonyStyle->text(sprintf('Current version: %s', $fromVersion));
        $symfonyStyle->text(sprintf('Target version: %s', System::getVersion()));
        $symfonyStyle->newLine();

        $update = new Update($this->system, $this->configuration);
        $update->version = $fromVersion;
        $update->dryRun = true;

        try {
            $update->applyUpdates();
            $report = $update->getDryRunResults();

            $this->displayDryRunReport($symfonyStyle, $report);

            return Command::SUCCESS;
        } catch (Exception $exception) {
            $symfonyStyle->error('Dry-run failed: ' . $exception->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Displays the dry-run report with all operations grouped by type.
     *
     * @param array<string, mixed> $report
     */
    private function displayDryRunReport(SymfonyStyle $symfonyStyle, array $report): void
    {
        if (empty($report['migrations'])) {
            $symfonyStyle->success('No migrations to apply. Database is up to date.');
            return;
        }

        foreach ($report['migrations'] as $version => $migrationData) {
            $symfonyStyle->section(sprintf('Migration: %s', $version));
            $symfonyStyle->text($migrationData['description']);
            $symfonyStyle->newLine();

            // Group operations by type
            $byType = [];
            foreach ($migrationData['operations'] as $op) {
                $byType[$op['type']][] = $op;
            }

            // SQL Operations
            if (!empty($byType['sql'])) {
                $symfonyStyle->text(sprintf('<fg=cyan>SQL Operations (%d):</>', count($byType['sql'])));
                $rows = [];
                foreach ($byType['sql'] as $i => $op) {
                    $query = $this->truncateString($op['query'], 80);
                    $rows[] = [$i + 1, $op['description'], $query];
                }
                $symfonyStyle->table(['#', 'Description', 'Query'], $rows);
            }

            // Config Add Operations
            if (!empty($byType['config_add'])) {
                $symfonyStyle->text(sprintf(
                    '<fg=green>Configuration Additions (%d):</>',
                    count($byType['config_add']),
                ));
                $rows = [];
                foreach ($byType['config_add'] as $i => $op) {
                    $rows[] = [$i + 1, $op['key'], $this->formatValue($op['value'])];
                }
                $symfonyStyle->table(['#', 'Key', 'Value'], $rows);
            }

            // Config Delete Operations
            if (!empty($byType['config_delete'])) {
                $symfonyStyle->text(sprintf(
                    '<fg=red>Configuration Deletions (%d):</>',
                    count($byType['config_delete']),
                ));
                $rows = [];
                foreach ($byType['config_delete'] as $i => $op) {
                    $rows[] = [$i + 1, $op['key']];
                }
                $symfonyStyle->table(['#', 'Key'], $rows);
            }

            // Config Rename Operations
            if (!empty($byType['config_rename'])) {
                $symfonyStyle->text(sprintf(
                    '<fg=yellow>Configuration Renames (%d):</>',
                    count($byType['config_rename']),
                ));
                $rows = [];
                foreach ($byType['config_rename'] as $i => $op) {
                    $rows[] = [$i + 1, $op['oldKey'], $op['newKey']];
                }
                $symfonyStyle->table(['#', 'Old Key', 'New Key'], $rows);
            }

            // Config Update Operations
            if (!empty($byType['config_update'])) {
                $symfonyStyle->text(sprintf(
                    '<fg=blue>Configuration Updates (%d):</>',
                    count($byType['config_update']),
                ));
                $rows = [];
                foreach ($byType['config_update'] as $i => $op) {
                    $rows[] = [$i + 1, $op['key'], $this->formatValue($op['value'])];
                }
                $symfonyStyle->table(['#', 'Key', 'New Value'], $rows);
            }

            // File Operations
            $fileOps = array_merge($byType['file_copy'] ?? [], $byType['directory_copy'] ?? []);
            if (!empty($fileOps)) {
                $symfonyStyle->text(sprintf('<fg=magenta>File Operations (%d):</>', count($fileOps)));
                $rows = [];
                foreach ($fileOps as $i => $op) {
                    $source = $this->shortenPath($op['source']);
                    $dest = $this->shortenPath($op['destination']);
                    $rows[] = [$i + 1, $op['type'], $source, $dest];
                }
                $symfonyStyle->table(['#', 'Type', 'Source', 'Destination'], $rows);
            }

            // Permission Operations
            if (!empty($byType['permission_grant'])) {
                $symfonyStyle->text(sprintf(
                    '<fg=white>Permission Grants (%d):</>',
                    count($byType['permission_grant']),
                ));
                $rows = [];
                foreach ($byType['permission_grant'] as $i => $op) {
                    $rows[] = [$i + 1, $op['permissionName'], $op['permissionDescription']];
                }
                $symfonyStyle->table(['#', 'Permission', 'Description'], $rows);
            }
        }

        // Summary
        $symfonyStyle->section('Summary');
        $symfonyStyle->text(sprintf('Total Migrations: %d', $report['summary']['migrationCount']));
        $symfonyStyle->text(sprintf('Total Operations: %d', $report['summary']['totalOperations']));

        if (!empty($report['summary']['operationsByType'])) {
            $symfonyStyle->newLine();
            $symfonyStyle->text('Operations by Type:');
            foreach ($report['summary']['operationsByType'] as $type => $count) {
                $symfonyStyle->text(sprintf('  - %s: %d', $type, $count));
            }
        }

        $symfonyStyle->newLine();
        $symfonyStyle->warning('This was a dry-run. No changes were made to the database.');
    }

    private function truncateString(string $str, int $maxLength): string
    {
        $str = preg_replace('/\s+/', ' ', trim($str)) ?? '';
        if (strlen($str) > $maxLength) {
            return substr($str, 0, $maxLength - 3) . '...';
        }
        return $str;
    }

    private function formatValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_string($value)) {
            if (strlen($value) > 40) {
                return "'" . substr($value, 0, 37) . "...'";
            }
            return "'{$value}'";
        }
        if (is_null($value)) {
            return 'null';
        }
        return (string) $value;
    }

    private function shortenPath(string $path): string
    {
        if (defined('PMF_ROOT_DIR')) {
            $path = str_replace(PMF_ROOT_DIR, '', $path);
        }
        if (strlen($path) > 50) {
            return '...' . substr($path, -47);
        }
        return $path;
    }

    private string $version = '';
    private string $installedVersion = '';

    private function taskHealthCheck(SymfonyStyle $symfonyStyle): int
    {
        $upgrade = new Upgrade($this->system, $this->configuration);
        if (!$upgrade->isMaintenanceEnabled()) {
            $symfonyStyle->warning(Translation::get(key: 'msgNotInMaintenanceMode'));
        }

        try {
            $upgrade->checkFilesystem();
        } catch (Throwable $throwable) {
            $symfonyStyle->error(message: 'Error during health check: ' . $throwable->getMessage());
            return Command::FAILURE;
        }

        $symfonyStyle->success(message: 'Health-Check successful.');
        return Command::SUCCESS;
    }

    private function taskUpdateCheck(SymfonyStyle $symfonyStyle): int
    {
        $dateLastChecked = new DateTime()->format(DateTimeInterface::ATOM);
        $branch = $this->configuration->get(item: 'upgrade.releaseEnvironment');

        try {
            $api = new Api($this->configuration, $this->system);
            $versions = $api->getVersions();
            $this->configuration->set(key: 'upgrade.dateLastChecked', value: $dateLastChecked);

            $available = version_compare(version1: $versions['installed'], version2: $versions[$branch], operator: '<');

            // Always store the installed version for migrations
            $this->installedVersion = $versions['installed'];

            if ($available) {
                $this->version = $versions[$branch];
                $symfonyStyle->success(message: Translation::get(key: 'msgCurrentVersion') . $versions[$branch]);
            } else {
                $this->version = $versions['installed'];
                $symfonyStyle->success(
                    message: Translation::get(key: 'versionIsUpToDate') . ' (' . $this->version . ')',
                );
            }
        } catch (Exception|TransportExceptionInterface|DecodingExceptionInterface $exception) {
            $symfonyStyle->error(message: 'Error during update check: ' . $exception->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws \phpMyFAQ\Core\Exception
     * @throws ClientExceptionInterface
     * @throws \JsonException
     */ private function taskDownloadPackage(SymfonyStyle $symfonyStyle): int
    {
        $upgrade = new Upgrade($this->system, $this->configuration);
        $pathToPackage = $upgrade->downloadPackage($this->version);

        if (!$upgrade->isNightly()) {
            $result = $upgrade->verifyPackage($pathToPackage, $this->version);
            if (!$result) {
                $symfonyStyle->error(message: Translation::get(key: 'verificationFailure'));
                return Command::FAILURE;
            }
        }

        $this->configuration->set(key: 'upgrade.lastDownloadedPackage', value: urlencode($pathToPackage));

        $symfonyStyle->success(message: Translation::get(key: 'downloadSuccessful'));
        return Command::SUCCESS;
    }

    private function taskExtractPackage(SymfonyStyle $symfonyStyle): int
    {
        $upgrade = new Upgrade($this->system, $this->configuration);
        $pathToPackage = urldecode((string) $this->configuration->get(item: 'upgrade.lastDownloadedPackage'));

        $result = $this->withProgress($symfonyStyle, static function (callable $setProgress) use (
            $upgrade,
            $pathToPackage,
        ): bool {
            $progressCallback = static function (int $progress) use ($setProgress): void {
                $setProgress($progress);
            };

            return $upgrade->extractPackage($pathToPackage, $progressCallback);
        });

        if ($result) {
            $symfonyStyle->success(message: Translation::get(key: 'extractSuccessful'));
            return Command::SUCCESS;
        }

        $symfonyStyle->error(message: Translation::get(key: 'extractFailure'));
        return Command::FAILURE;
    }

    private function taskCreateTemporaryBackup(SymfonyStyle $symfonyStyle): int
    {
        $upgrade = new Upgrade($this->system, $this->configuration);
        $backupHash = md5(uniqid());
        $backupFile = $backupHash . '.zip';

        $result = $this->withProgress($symfonyStyle, static function (callable $setProgress) use (
            $upgrade,
            $backupFile,
        ): bool {
            $progressCallback = static function (int $progress) use ($setProgress): void {
                $setProgress($progress);
            };

            return $upgrade->createTemporaryBackup($backupFile, $progressCallback);
        });

        if ($result) {
            $symfonyStyle->success(message: 'Backup successful: ' . $backupFile);
            return Command::SUCCESS;
        }

        $symfonyStyle->error(message: 'Backup failed.');
        return Command::FAILURE;
    }

    private function taskInstallPackage(SymfonyStyle $symfonyStyle): int
    {
        $upgrade = new Upgrade($this->system, $this->configuration);
        $environmentConfigurator = new EnvironmentConfigurator($this->configuration);

        $result = $this->withProgress($symfonyStyle, static function (callable $setProgress) use (
            $upgrade,
            $environmentConfigurator,
        ): bool {
            $progressCallback = static function (int $progress) use ($setProgress): void {
                $setProgress($progress);
            };

            $installed = $upgrade->installPackage($progressCallback);
            return $installed && $environmentConfigurator->adjustRewriteBaseHtaccess();
        });

        if ($result) {
            $symfonyStyle->success(message: 'Package successfully installed.');
            return Command::SUCCESS;
        }

        $symfonyStyle->error(message: 'Package installation failed.');
        return Command::FAILURE;
    }

    private function taskUpdateDatabase(SymfonyStyle $symfonyStyle): int
    {
        $update = new Update($this->system, $this->configuration);
        // Use the installed version (current version) for migrations, not the target version
        $update->version = $this->installedVersion;

        $progressBar = $symfonyStyle->createProgressBar(max: 100);
        $progressBar->start();

        try {
            $result = $update->applyUpdates();
            $progressBar->finish();
            $symfonyStyle->newLine(count: 2);

            if ($result) {
                $this->configuration->set(key: 'main.maintenanceMode', value: 'false');
                $this->displayMigrationResults($symfonyStyle, $update->migrationResults);
                $symfonyStyle->success(message: 'Database successfully updated.');
                return Command::SUCCESS;
            }

            $this->displayMigrationResults($symfonyStyle, $update->migrationResults);
            $symfonyStyle->error(message: 'Update database failed.');
            return Command::FAILURE;
        } catch (Exception $exception) {
            $progressBar->finish();
            $symfonyStyle->newLine(count: 2);
            $symfonyStyle->error(message: 'Update database failed: ' . $exception->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Displays migration results in a formatted table.
     *
     * @param MigrationResult[] $results
     */
    private function displayMigrationResults(SymfonyStyle $symfonyStyle, array $results): void
    {
        if (empty($results)) {
            $symfonyStyle->note('No migrations were applied.');
            return;
        }

        $tableRows = [];
        foreach ($results as $result) {
            $status = $result->isSuccess() ? '<fg=green>SUCCESS</>' : '<fg=red>FAILED</>';
            $tableRows[] = [
                $result->getVersion(),
                $result->getDescription(),
                $result->getOperationCount(),
                sprintf('%.2fms', $result->getExecutionTimeMs()),
                $status,
            ];
        }

        $symfonyStyle->table(['Version', 'Description', 'Operations', 'Time', 'Status'], $tableRows);
    }

    private function taskCleanup(SymfonyStyle $symfonyStyle): int
    {
        $upgrade = new Upgrade($this->system, $this->configuration);
        $upgrade->cleanUp();

        $symfonyStyle->success(message: 'Cleanup successful.');
        return Command::SUCCESS;
    }

    private function withProgress(SymfonyStyle $symfonyStyle, callable $fn): bool
    {
        $progressBar = $symfonyStyle->createProgressBar(max: 100);
        $progressBar->start();

        $setProgress = static function (int $progress) use ($progressBar): void {
            $progressBar->setProgress($progress);
        };

        try {
            $result = (bool) $fn($setProgress);
        } finally {
            $progressBar->finish();
            $symfonyStyle->newLine(count: 2);
        }

        return $result;
    }
}
