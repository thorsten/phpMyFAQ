<?php

/**
 * The Upgrade class used for upgrading/installing phpMyFAQ from a ZIP file.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-06-30
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup;

use FilesystemIterator;
use JsonException;
use Monolog\Level;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\DownloadHostType;
use phpMyFAQ\Enums\ReleaseType;
use phpMyFAQ\System;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use ZipArchive;

class Upgrade extends AbstractSetup
{
    final public const string GITHUB_PATH = 'thorsten/phpMyFAQ/releases/download/development-nightly-%s/';

    private const string GITHUB_FILENAME = 'phpMyFAQ-nightly-%s.zip';

    private const string PHPMYFAQ_FILENAME = 'phpMyFAQ-%s.zip';

    public string $upgradeDirectory = PMF_CONTENT_DIR . '/upgrades';

    private bool $isNightly;

    private HttpClientInterface $httpClient;

    public function __construct(
        protected System $system,
        private readonly Configuration $configuration,
        ?HttpClientInterface $httpClient = null,
    ) {
        parent::__construct($this->system);

        $this->isNightly =
            $this->configuration->get(item: 'upgrade.releaseEnvironment') === ReleaseType::NIGHTLY->value;

        $this->httpClient = $httpClient ?? HttpClient::create(['timeout' => 60]);
    }

    /**
     * Method to check if the filesystem is ready for the upgrade
     *
     * @throws Exception
     */
    public function checkFilesystem(): bool
    {
        if (!is_dir($this->upgradeDirectory) && !mkdir($this->upgradeDirectory)) {
            throw new Exception(message: 'The folder ' . $this->upgradeDirectory . ' is missing.');
        }

        if (!is_dir(PMF_CONTENT_DIR . '/user/attachments')) {
            throw new Exception(message: 'The folder /content/user/attachments is missing.');
        }

        if (!is_dir(PMF_CONTENT_DIR . '/user/images')) {
            throw new Exception(message: 'The folder /content/user/images is missing.');
        }

        if (!is_dir(PMF_CONTENT_DIR . '/core/data')) {
            throw new Exception(message: 'The folder /content/core/data is missing.');
        }

        if (!is_dir(PMF_ROOT_DIR . '/assets/templates')) {
            throw new Exception(message: 'The folder /phpmyfaq/assets/templates is missing.');
        }

        if (
            !is_file(PMF_CONTENT_DIR . '/core/config/constants.php')
            || !is_file(PMF_CONTENT_DIR . '/core/config/database.php')
        ) {
            throw new Exception(
                message: 'The files /content/core/config/constant.php and /content/core/config/database.php are missing.',
            );
        }

        if (
            $this->configuration->isElasticsearchActive()
            && !is_file(PMF_CONTENT_DIR . '/core/config/elasticsearch.php')
        ) {
            throw new Exception(message: 'The file /content/core/config/elasticsearch.php is missing.');
        }

        if ($this->configuration->isLdapActive() && !is_file(PMF_CONTENT_DIR . '/core/config/ldap.php')) {
            throw new Exception(message: 'The file /content/core/config/ldap.php is missing.');
        }

        if (
            $this->configuration->isSignInWithMicrosoftActive()
            && !is_file(PMF_CONTENT_DIR . '/core/config/azure.php')
        ) {
            throw new Exception(message: 'The file /content/core/config/azure.php is missing.');
        }

        return true;
    }

    /**
     * Method to download a phpMyFAQ package, throws an exception if it doesn't work
     *
     * @throws Exception
     * @todo handle possible proxy servers
     */
    public function downloadPackage(string $version): string
    {
        $url = $this->getDownloadHost() . $this->getPath() . $this->getFilename($version);

        $attempts = 3;
        $lastExceptionMessage = null;

        for ($i = 0; $i < $attempts; $i++) {
            try {
                $response = $this->httpClient->request(
                    method: 'GET',
                    url: $url,
                );

                if ($response->getStatusCode() !== 200) {
                    throw new Exception(message: 'Cannot download package (HTTP Status: '
                    . $response->getStatusCode()
                    . ').');
                }

                $package = $response->getContent();

                $targetPath = $this->upgradeDirectory . DIRECTORY_SEPARATOR . $this->getFilename($version);
                file_put_contents($targetPath, $package);

                return $targetPath;
            } catch (
                TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $exception
            ) {
                $lastExceptionMessage = $exception->getMessage();

                // After the last attempt, throw the exception outward
                if ($i === ($attempts - 1)) {
                    throw new Exception('Download failed after ' . $attempts . ' attempts: ' . $lastExceptionMessage);
                }

                // Short sleep to mitigate transient network issues
                usleep(microseconds: 250000); // 250ms
            }
        }

        // Should not be reached, but for safety
        throw new Exception('Download failed: ' . ($lastExceptionMessage ?? 'unknown error'));
    }

    /**
     * Method to verify the downloaded phpMyFAQ package
     *
     * @param string $path | Path to a zip file
     * @param string $version | Version to verify
     * @throws TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|JsonException
     */
    public function verifyPackage(string $path, string $version): bool
    {
        $response = $this->httpClient->request(
            method: 'GET',
            url: DownloadHostType::PHPMYFAQ->value . 'info/' . $version,
        );

        try {
            $responseContent = json_decode(
                $response->getContent(),
                associative: true,
                depth: 512,
                flags: JSON_THROW_ON_ERROR,
            );

            return md5_file($path) === $responseContent['zip']['md5'];
        } catch (
            TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e
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
            throw new Exception(message: 'Given path to download package is not valid.');
        }

        $zipFile = $zipArchive->open($path);

        $zipArchive->registerProgressCallback(
            rate: 0.05,
            callback: static function ($rate) use ($progressCallback): void {
                $progress = (int) ($rate * 100) . '%';
                $progressCallback($progress);
            },
        );

        if ($zipFile) {
            $zipArchive->extractTo($this->upgradeDirectory . '/new/');
            return $zipArchive->close();
        }

        throw new Exception(message: 'Cannot open zipped download package.');
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
            throw new Exception(message: 'Backup file already exists.');
        }

        $zipArchive = new ZipArchive();
        if ($zipArchive->open($outputZipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception(message: 'Cannot create backup file.');
        }

        $sourceDir = PMF_ROOT_DIR;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        $zipArchive->registerProgressCallback(
            rate: 0.05,
            callback: static function ($rate) use ($progressCallback): void {
                $progress = (int) ($rate * 100) . '%';
                $progressCallback($progress);
            },
        );

        foreach ($files as $file) {
            $file = $file->getRealPath();
            if (!str_contains($file, $this->upgradeDirectory . DIRECTORY_SEPARATOR)) {
                if (is_dir($file)) {
                    $zipArchive->addEmptyDir(str_replace(
                        $sourceDir . DIRECTORY_SEPARATOR,
                        replace: '',
                        subject: $file . DIRECTORY_SEPARATOR,
                    ));
                } elseif (is_file($file)) {
                    $zipArchive->addFile($file, str_replace(
                        $sourceDir . DIRECTORY_SEPARATOR,
                        replace: '',
                        subject: $file,
                    ));
                }
            }
        }

        $zipArchive->close();

        return file_exists($outputZipFile);
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
            RecursiveIteratorIterator::SELF_FIRST,
        );

        $totalFiles = iterator_count($sourceDirIterator);
        $currentFile = 0;

        foreach ($sourceDirIterator as $item) {
            $source = $item->getRealPath();
            $relativePath = str_replace($sourceDir, replace: '', subject: $source);
            $destination = $destinationDir . DIRECTORY_SEPARATOR . $relativePath;

            if ($item->isDir()) {
                if (!is_dir($destination)) {
                    mkdir($destination, permissions: 0o755, recursive: true);
                }
            } else {
                copy($source, $destination);
            }

            ++$currentFile;
            if (($currentFile % 10) !== 0) {
                continue;
            }

            $progress = 100;
            if ($totalFiles > 0) {
                $progress = (int) (($currentFile / $totalFiles) * 100) . '%';
            }

            $progressCallback($progress);
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
            RecursiveIteratorIterator::CHILD_FIRST,
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
            return sprintf(self::GITHUB_PATH, date(format: 'Y-m-d'));
        }

        return '';
    }

    /**
     * Returns the filename of the download package
     */
    public function getFilename(string $version): string
    {
        if ($this->isNightly()) {
            return sprintf(self::GITHUB_FILENAME, date(format: 'Y-m-d'));
        }

        return sprintf(self::PHPMYFAQ_FILENAME, $version);
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
        return $this->configuration->get(item: 'main.maintenanceMode');
    }
}
