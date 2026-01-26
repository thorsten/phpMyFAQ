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

    private string $version = '';

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
        $update->setVersion(System::getVersion());

        $progressBar = $symfonyStyle->createProgressBar(max: 100);
        $progressBar->start();

        try {
            $result = $update->applyUpdates();
            $progressBar->finish();
            $symfonyStyle->newLine(count: 2);

            if ($result) {
                $this->configuration->set(key: 'main.maintenanceMode', value: 'false');
                $symfonyStyle->success(message: 'Database successfully updated.');
                return Command::SUCCESS;
            }

            $symfonyStyle->error(message: 'Update database failed.');
            return Command::FAILURE;
        } catch (Exception $exception) {
            $progressBar->finish();
            $symfonyStyle->newLine(count: 2);
            $symfonyStyle->error(message: 'Update database failed: ' . $exception->getMessage());
            return Command::FAILURE;
        }
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
