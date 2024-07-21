<?php

/**
 * The Upgrade class used for upgrading/installing phpMyFAQ from a ZIP file.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-06-30
 */

namespace phpMyFAQ\Setup;

use FilesystemIterator;
use JsonException;
use Monolog\Level;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\DownloadHostType;
use phpMyFAQ\Enums\ReleaseType;
use phpMyFAQ\Setup;
use phpMyFAQ\System;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use ZipArchive;

class Upgrade extends Setup
{
    final public const GITHUB_PATH = 'thorsten/phpMyFAQ/releases/download/development-nightly-%s/';

    private const GITHUB_FILENAME = 'phpMyFAQ-nightly-%s.zip';

    private const PHPMYFAQ_FILENAME = 'phpMyFAQ-%s.zip';

    public string $upgradeDirectory = PMF_CONTENT_DIR . '/upgrades';

    private bool $isNightly;

    private bool $isMaintenanceEnabled = false;

    public function __construct(protected System $system, private readonly Configuration $configuration)
    {
        parent::__construct($this->system);

        $this->isNightly = $this->configuration->get('upgrade.releaseEnvironment') === ReleaseType::NIGHTLY->value;
    }

    /**
     * Method to check if the filesystem is ready for the upgrade
     *
     * @throws Exception
     */
    public function checkFilesystem(): bool
    {
        if (!is_dir($this->upgradeDirectory) && !mkdir($this->upgradeDirectory)) {
            throw new Exception('The folder ' . $this->upgradeDirectory . ' is missing.');
        }

        if (!is_dir(PMF_CONTENT_DIR . '/user/attachments')) {
            throw new Exception('The folder /content/user/attachments is missing.');
        }

        if (!is_dir(PMF_CONTENT_DIR . '/user/images')) {
            throw new Exception('The folder /content/user/images is missing.');
        }

        if (!is_dir(PMF_CONTENT_DIR . '/core/data')) {
            throw new Exception('The folder /content/core/data is missing.');
        }

        if (!is_dir(PMF_ROOT_DIR . '/assets/themes')) {
            throw new Exception('The folder /phpmyfaq/assets/themes is missing.');
        }

        if (
            is_file(PMF_CONTENT_DIR . '/core/config/constants.php') &&
            is_file(PMF_CONTENT_DIR . '/core/config/database.php')
        ) {
            if (
                $this->configuration->isElasticsearchActive() &&
                !is_file(PMF_CONTENT_DIR . '/core/config/elasticsearch.php')
            ) {
                throw new Exception(
                    'The file /content/core/config/elasticsearch.php is missing.'
                );
            }

            if ($this->configuration->isLdapActive() && !is_file(PMF_CONTENT_DIR . '/core/config/ldap.php')) {
                throw new Exception('The file /content/core/config/ldap.php is missing.');
            }

            if (
                $this->configuration->isSignInWithMicrosoftActive() &&
                !is_file(PMF_CONTENT_DIR . '/core/config/azure.php')
            ) {
                throw new Exception('The file /content/core/config/azure.php is missing.');
            }

            return true;
        }

        throw new Exception(
            'The files /content/core/config/constant.php and /content/core/config/database.php are missing.'
        );
    }

    /**
     * Method to download a phpMyFAQ package, returns false if it doesn't work
     *
     * @throws Exception
     * @todo handle possible proxy servers
     */
    public function downloadPackage(string $version): string
    {
        $url = $this->getDownloadHost() . $this->getPath() . $this->getFilename($version);

        $client = HttpClient::create(['timeout' => 30]);

        try {
            $response = $client->request('GET', $url);

            if ($response->getStatusCode() !== 200) {
                throw new Exception('Cannot download package.');
            }

            $package = $response->getContent();

            file_put_contents($this->upgradeDirectory . DIRECTORY_SEPARATOR . $this->getFilename($version), $package);

            return $this->upgradeDirectory . DIRECTORY_SEPARATOR . $this->getFilename($version);
        } catch (
            TransportExceptionInterface |
            ClientExceptionInterface |
            RedirectionExceptionInterface |
            ServerExceptionInterface $e
        ) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Method to verify the downloaded phpMyFAQ package
     *
     * @param string $path | Path to zip file
     * @param string $version | Version to verify
     * @throws TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|JsonException
     */
    public function verifyPackage(string $path, string $version): bool
    {
        $httpClient = HttpClient::create(['timeout' => 30]);
        $response = $httpClient->request(
            'GET',
            DownloadHostType::PHPMYFAQ->value . 'info/' . $version
        );

        try {
            $responseContent = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

            return md5_file($path) === $responseContent['zip']['md5'];
        } catch (
            TransportExceptionInterface |
            ClientExceptionInterface |
            RedirectionExceptionInterface |
            ServerExceptionInterface $e
        ) {
            $this->configuration->getLogger()->log(Level::Error, $e->getMessage());

            return false;
        }
    }

    /**
     * Method to extract the downloaded phpMyFAQ package
     *
     * @param string   $path | Path of the package
     * @throws Exception
     */
    public function extractPackage(string $path, callable $progressCallback): bool
    {
        $zipArchive = new ZipArchive();

        if (!is_file($path)) {
            throw new Exception('Given path to download package is not valid.');
        }

        $zipFile = $zipArchive->open($path);

        $zipArchive->registerProgressCallback(0.05, static function ($rate) use ($progressCallback) {
            $progress = sprintf('%d%%', $rate * 100);
            $progressCallback($progress);
        });

        if ($zipFile) {
            $zipArchive->extractTo($this->upgradeDirectory . '/new/');
            return $zipArchive->close();
        }
        throw new Exception('Cannot open zipped download package.');
    }

    /**
     * Method to create a temporary backup of the current files
     *
     * @param string   $backupName | Name of the created backup
     * @throws Exception
     */
    public function createTemporaryBackup(string $backupName, callable $progressCallback): bool
    {
        $outputZipFile = $this->upgradeDirectory . DIRECTORY_SEPARATOR . $backupName;

        if (file_exists($outputZipFile)) {
            throw new Exception('Backup file already exists.');
        }

        $zipArchive = new ZipArchive();
        if ($zipArchive->open($outputZipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('Cannot create backup file.');
        }

        $sourceDir = PMF_ROOT_DIR;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $zipArchive->registerProgressCallback(0.05, static function ($rate) use ($progressCallback) {
            $progress = sprintf('%d%%', $rate * 100);
            $progressCallback($progress);
        });

        foreach ($files as $file) {
            $file = realpath($file);
            if (!str_contains($file, $this->upgradeDirectory . DIRECTORY_SEPARATOR)) {
                if (is_dir($file)) {
                    $zipArchive->addEmptyDir(
                        str_replace($sourceDir . DIRECTORY_SEPARATOR, '', $file . DIRECTORY_SEPARATOR)
                    );
                } elseif (is_file($file)) {
                    $zipArchive->addFile($file, str_replace($sourceDir . DIRECTORY_SEPARATOR, '', $file));
                }
            }
        }

        $zipArchive->close();

        return file_exists($outputZipFile);
    }

     /**
     * Method to delete the temporary created backup.
     *
     * @param string $backupName | Name of the created backup
     */
    public function deleteTemporaryBackup(string $backupName): bool
    {
        if (is_file($this->upgradeDirectory . DIRECTORY_SEPARATOR . $backupName)) {
            return unlink($this->upgradeDirectory . DIRECTORY_SEPARATOR . $backupName);
        }
        return false;
    }

    /**
     * Method to restore from the temporary backup
     */
    public function restoreTemporaryBackup()
    {
    }

    /**
     * Method to install the package
     */
    public function installPackage(callable $progressCallback): bool
    {
        $sourceDir = $this->upgradeDirectory . '/new/phpmyfaq/';
        $destinationDir = PMF_ROOT_DIR;

        $sourceDirIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $totalFiles = iterator_count($sourceDirIterator);
        $currentFile = 0;

        foreach ($sourceDirIterator as $item) {
            $source = $item->getPathName();
            $destination = $destinationDir . DIRECTORY_SEPARATOR . $sourceDirIterator->getSubPathName();

            if ($item->isDir()) {
                if (!is_dir($destination)) {
                    mkdir($destination, 0755, true);
                }
            } else {
                copy($source, $destination);
            }

            ++$currentFile;
            if ($currentFile % 10 === 0) {
                $progress = $totalFiles > 0 ? sprintf('%d%%', ($currentFile / $totalFiles) * 100) : 100;
                call_user_func($progressCallback, $progress);
            }
        }

        return true;
    }

    /**
     * Method to clean up the upgrade directory
     */
    public function cleanUp(): bool
    {
        $directoryToDelete = $this->upgradeDirectory . '/new/phpmyfaq/';

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directoryToDelete, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        return rmdir($directoryToDelete);
    }

    /**
     * Returns the host for download packages, so either github.com or download.phpmyfaq.de
     */
    public function getDownloadHost(): string
    {
        if ($this->isNightly()) {
            return DownloadHostType::GITHUB->value;
        }

        return DownloadHostType::PHPMYFAQ->value;
    }

    /**
     * Returns the path to the download package, it's an empty string for development and production releases
     */
    public function getPath(): string
    {
        if ($this->isNightly()) {
            return sprintf(self::GITHUB_PATH, date('Y-m-d', strtotime('-1 days')));
        }

        return '';
    }

    /**
     * Returns the filename of the download package
     */
    public function getFilename(string $version): string
    {
        if ($this->isNightly()) {
            return sprintf(self::GITHUB_FILENAME, date('Y-m-d', strtotime('-1 days')));
        }

        return sprintf(self::PHPMYFAQ_FILENAME, $version);
    }

    public function getUpgradeDirectory(): string
    {
        return $this->upgradeDirectory;
    }

    public function setUpgradeDirectory(string $upgradeDirectory): void
    {
        $this->upgradeDirectory = $upgradeDirectory;
    }

    public function isNightly(): bool
    {
        return $this->isNightly;
    }

    public function setIsNightly(bool $isNightly): void
    {
        $this->isNightly = $isNightly;
    }

    public function isMaintenanceEnabled(): bool
    {
        return $this->isMaintenanceEnabled = $this->configuration->get('main.maintenanceMode');
    }

    public function setIsMaintenanceEnabled(bool $isMaintenanceEnabled): Upgrade
    {
        $this->isMaintenanceEnabled = $isMaintenanceEnabled;
        $this->configuration->set('main.maintenanceMode', $isMaintenanceEnabled);

        return $this;
    }
}
