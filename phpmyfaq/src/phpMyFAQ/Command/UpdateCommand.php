<?php

declare(strict_types=1);

namespace phpMyFAQ\Command;

use DateTime;
use DateTimeInterface;
use Exception;
use phpMyFAQ\Administration\Api;
use phpMyFAQ\Configuration;
use phpMyFAQ\Setup\EnvironmentConfigurator;
use phpMyFAQ\Setup\Update;
use phpMyFAQ\Setup\Upgrade;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Throwable;

class UpdateCommand extends Command
{
    protected static string $defaultName = 'phpmyfaq:update';

    private string $version;

    private Configuration $configuration;

    private System $system;

    public function __construct()
    {
        parent::__construct();

        $this->configuration = Configuration::getConfigurationInstance();
        $this->system = new System();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName(self::$defaultName)
            ->setDescription('Executes the phpMyFAQ update process')
            ->addArgument('version', InputArgument::OPTIONAL, 'Requested version for the update');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        $symfonyStyle->title('Start automatic phpMyFAQ update ...');

        try {
            if (Command::SUCCESS !== $this->taskHealthCheck($symfonyStyle)) {
                return Command::FAILURE;
            }

            if (Command::SUCCESS !== $this->taskUpdateCheck($symfonyStyle)) {
                return Command::FAILURE;
            }

            if (Command::SUCCESS !== $this->taskDownloadPackage($symfonyStyle)) {
                return Command::FAILURE;
            }

            if (Command::SUCCESS !== $this->taskExtractPackage($symfonyStyle)) {
                return Command::FAILURE;
            }

            if (Command::SUCCESS !== $this->taskCreateTemporaryBackup($symfonyStyle)) {
                return Command::FAILURE;
            }

            if (Command::SUCCESS !== $this->taskInstallPackage($symfonyStyle)) {
                return Command::FAILURE;
            }

            if (Command::SUCCESS !== $this->taskUpdateDatabase($symfonyStyle)) {
                return Command::FAILURE;
            }

            if (Command::SUCCESS !== $this->taskCleanup($symfonyStyle)) {
                return Command::FAILURE;
            }

            $symfonyStyle->success(sprintf(
                'phpMyFAQ was successfully updated to version %s on %s.',
                System::getVersion(),
                (new DateTime())->format('Y-m-d H:i:s'),
            ));
            return Command::SUCCESS;
        } catch (Throwable $throwable) {
            $symfonyStyle->error('Error during update: ' . $throwable->getMessage());
            return Command::FAILURE;
        }
    }

    private function taskHealthCheck(SymfonyStyle $symfonyStyle): int
    {
        $upgrade = new Upgrade($this->system, $this->configuration);
        if (!$upgrade->isMaintenanceEnabled()) {
            $symfonyStyle->warning(Translation::get('msgNotInMaintenanceMode'));
        }

        try {
            $upgrade->checkFilesystem();
        } catch (Throwable $throwable) {
            $symfonyStyle->error('Error during health check: ' . $throwable->getMessage());
            return Command::FAILURE;
        }

        $symfonyStyle->success('Health-Check successful.');
        return Command::SUCCESS;
    }

    private function taskUpdateCheck(SymfonyStyle $symfonyStyle): int
    {
        $dateTime = new DateTime();
        $dateLastChecked = $dateTime->format(DateTimeInterface::ATOM);
        $branch = $this->configuration->get('upgrade.releaseEnvironment');

        try {
            $api = new Api($this->configuration, $this->system);
            $versions = $api->getVersions();
            $this->configuration->set('upgrade.dateLastChecked', $dateLastChecked);

            if (version_compare($versions['installed'], $versions[$branch], '<')) {
                $this->version = $versions[$branch];
                $symfonyStyle->success(Translation::get('msgCurrentVersion') . $versions[$branch]);
            }

            $this->version = $versions['installed'];
            $symfonyStyle->success(Translation::get('versionIsUpToDate') . ' (' . $this->version . ')');
        } catch (Exception|TransportExceptionInterface|DecodingExceptionInterface $exception) {
            $symfonyStyle->error('Error during update check: ' . $exception->getMessage());
        }

        return Command::SUCCESS;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \phpMyFAQ\Core\Exception
     * @throws \JsonException
     */
    private function taskDownloadPackage(SymfonyStyle $symfonyStyle): int
    {
        $upgrade = new Upgrade($this->system, $this->configuration);
        $pathToPackage = $upgrade->downloadPackage($this->version);

        if ($pathToPackage === false) {
            $symfonyStyle->error(Translation::get('downloadFailure'));
            return Command::FAILURE;
        }

        if (!$upgrade->isNightly()) {
            $result = $upgrade->verifyPackage($pathToPackage, $this->version);
            if ($result === false) {
                $symfonyStyle->error(Translation::get('verificationFailure'));
                return Command::FAILURE;
            }
        }

        $this->configuration->set('upgrade.lastDownloadedPackage', urlencode($pathToPackage));

        $symfonyStyle->success(Translation::get('downloadSuccessful'));
        return Command::SUCCESS;
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    private function taskExtractPackage(SymfonyStyle $symfonyStyle): int
    {
        $upgrade = new Upgrade($this->system, $this->configuration);
        $pathToPackage = urldecode((string) $this->configuration->get('upgrade.lastDownloadedPackage'));

        $progressBar = $symfonyStyle->createProgressBar(100);
        $progressBar->start();

        $progressCallback = static function ($progress) use ($progressBar): void {
            $progressBar->setProgress((int) $progress);
        };

        $result = $upgrade->extractPackage($pathToPackage, $progressCallback);

        $progressBar->finish();
        $symfonyStyle->newLine(2);

        if ($result) {
            $symfonyStyle->success(Translation::get('extractSuccessful'));
            return Command::SUCCESS;
        }

        $symfonyStyle->error(Translation::get('extractFailure'));
        return Command::FAILURE;
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    private function taskCreateTemporaryBackup(SymfonyStyle $symfonyStyle): int
    {
        $upgrade = new Upgrade($this->system, $this->configuration);
        $backupHash = md5(uniqid());
        $backupFile = $backupHash . '.zip';

        $progressBar = $symfonyStyle->createProgressBar(100);
        $progressBar->start();

        $progressCallback = static function ($progress) use ($progressBar): void {
            $progressBar->setProgress((int) $progress);
        };

        $result = $upgrade->createTemporaryBackup($backupFile, $progressCallback);

        $progressBar->finish();
        $symfonyStyle->newLine(2);

        if ($result) {
            $symfonyStyle->success('Backup successful: ' . $backupFile);
            return Command::SUCCESS;
        }

        $symfonyStyle->error('Backup failed.');
        return Command::FAILURE;
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    private function taskInstallPackage(SymfonyStyle $symfonyStyle): int
    {
        $upgrade = new Upgrade($this->system, $this->configuration);
        $environmentConfigurator = new EnvironmentConfigurator($this->configuration);

        $progressBar = $symfonyStyle->createProgressBar(100);
        $progressBar->start();

        $progressCallback = static function ($progress) use ($progressBar): void {
            $progressBar->setProgress((int) $progress);
        };

        $result = $upgrade->installPackage($progressCallback) && $environmentConfigurator->adjustRewriteBaseHtaccess();

        $progressBar->finish();
        $symfonyStyle->newLine(2);

        if ($result) {
            $symfonyStyle->success('Package successfully installed.');
            return Command::SUCCESS;
        }

        $symfonyStyle->error('Package installation failed.');
        return Command::FAILURE;
    }

    private function taskUpdateDatabase(SymfonyStyle $symfonyStyle): int
    {
        $update = new Update($this->system, $this->configuration);
        $update->setVersion(System::getVersion());

        $progressBar = $symfonyStyle->createProgressBar(100);
        $progressBar->start();

        try {
            $result = $update->applyUpdates();
            $progressBar->finish();
            $symfonyStyle->newLine(2);

            if ($result) {
                $this->configuration->set('main.maintenanceMode', 'false');
                $symfonyStyle->success('Database successfully updated.');
                return Command::SUCCESS;
            }

            $symfonyStyle->error('Update database failed.');
            return Command::FAILURE;
        } catch (Exception $exception) {
            $progressBar->finish();
            $symfonyStyle->newLine(2);
            $symfonyStyle->error('Update database failed: ' . $exception->getMessage());
            return Command::FAILURE;
        }
    }

    private function taskCleanup(SymfonyStyle $symfonyStyle): int
    {
        $upgrade = new Upgrade($this->system, $this->configuration);
        $upgrade->cleanUp();

        $symfonyStyle->success('Cleanup successful.');
        return Command::SUCCESS;
    }
}
