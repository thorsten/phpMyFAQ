<?php

/**
 * The Upgrade class used for upgrading/installing phpMyFAQ from a ZIP file.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023 phpMyFAQ Team
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
    public const GITHUB_PATH = 'thorsten/phpMyFAQ/releases/download/development-nightly-%s/';
    private const GITHUB_FILENAME = 'phpMyFAQ-nightly-%s.zip';
    private const PHPMYFAQ_FILENAME = 'phpMyFAQ-%s.zip';
    private const PMF_UPGRADE_DIR = PMF_CONTENT_DIR . '/upgrades';
    private bool $isNightly;

    public function __construct(protected System $system, private readonly Configuration $configuration)
    {
        parent::__construct($this->system);

        $this->isNightly = $this->configuration->get('upgrade.releaseEnvironment') === ReleaseType::NIGHTLY->value;
    }

    /**
     * Method to check if the filesystem is ready for the upgrade
     *
     * @return bool
     * @throws Exception
     */
    public function checkFilesystem(): bool
    {
        if (!is_dir(self::PMF_UPGRADE_DIR)) {
            if (!mkdir(self::PMF_UPGRADE_DIR)) {
                throw new Exception('The folder ' . self::PMF_UPGRADE_DIR . ' is missing.');
            }
        }
        if (
            is_dir(PMF_CONTENT_DIR . '/user/attachments') &&
            is_dir(PMF_CONTENT_DIR . '/user/images') &&
            is_dir(PMF_CONTENT_DIR . '/core/data') &&
            is_dir(PMF_ROOT_DIR . '/assets/themes')
        ) {
            if (
                is_file(PMF_CONTENT_DIR . '/core/config/constants.php') &&
                is_file(PMF_CONTENT_DIR . '/core/config/database.php')
            ) {
                if ($this->configuration->isElasticsearchActive()) {
                    if (!is_file(PMF_CONTENT_DIR . '/core/config/elasticsearch.php')) {
                        throw new Exception(
                            'The file ' . PMF_CONTENT_DIR . '/core/config/elasticsearch.php is missing.'
                        );
                    }
                }
                if ($this->configuration->isLdapActive()) {
                    if (!is_file(PMF_CONTENT_DIR . '/core/config/ldap.php')) {
                        throw new Exception('The file ' . PMF_CONTENT_DIR . '/core/config/ldap.php is missing.');
                    }
                }
                if ($this->configuration->isSignInWithMicrosoftActive()) {
                    if (!is_file(PMF_CONTENT_DIR . '/core/config/azure.php')) {
                        throw new Exception('The file ' . PMF_CONTENT_DIR . '/core/config/azure.php is missing.');
                    }
                }

                return true;
            } else {
                throw new Exception(
                    'The files ' . PMF_CONTENT_DIR . '/core/config/constant.php and ' .
                    PMF_CONTENT_DIR . '/core/config/database.php are missing.'
                );
            }
        } else {
            return false;
        }
    }

    /**
     * Method to download a phpMyFAQ package, returns false if it doesn't work
     *
     * @param string $version
     * @return string
     * @throws Exception
     * @todo handle possible proxy servers
     */
    public function downloadPackage(string $version): string
    {
        $url = $this->getDownloadHost() . $this->getPath() . $this->getFilename($version);

        $client = HttpClient::create();

        try {
            $response = $client->request('GET', $url);

            if ($response->getStatusCode() !== 200) {
                throw new Exception('Cannot download package.');
            }

            $package = $response->getContent();

            file_put_contents(self::PMF_UPGRADE_DIR . DIRECTORY_SEPARATOR . $this->getFilename($version), $package);

            return self::PMF_UPGRADE_DIR . DIRECTORY_SEPARATOR . $this->getFilename($version);
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
     * @return bool
     * @throws TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|JsonException
     */
    public function verifyPackage(string $path, string $version): bool
    {
        $client = HttpClient::create();
        $response = $client->request(
            'GET',
            DownloadHostType::PHPMYFAQ->value . 'info/' . $version
        );

        try {
            $responseContent = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

            if (md5_file($path) === $responseContent['zip']['md5']) {
                return true;
            } else {
                return false;
            }
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
     * @param callable $progressCallback
     * @return bool
     * @throws Exception
     */
    public function extractPackage(string $path, callable $progressCallback): bool
    {
        $zip = new ZipArchive();

        if (!is_file($path)) {
            throw new Exception('Given path to download package is not valid.');
        }

        $zipFile = $zip->open($path);

        $zip->registerProgressCallback(0.05, function ($rate) use ($progressCallback) {
            $progress = sprintf('%d%%', $rate * 100);
            $progressCallback($progress);
        });

        if ($zipFile) {
            $zip->extractTo(self::PMF_UPGRADE_DIR . '/new/');
            return $zip->close();
        } else {
            throw new Exception('Cannot open zipped download package.');
        }
    }

    /**
     * Method to create a temporary backup of the current files
     *
     * @param string   $backupName | Name of the created backup
     * @param callable $progressCallback
     * @return bool
     * @throws Exception
     */
    public function createTemporaryBackup(string $backupName, callable $progressCallback): bool
    {
        $outputZipFile = self::PMF_UPGRADE_DIR . DIRECTORY_SEPARATOR . $backupName;

        if (file_exists($outputZipFile)) {
            throw new Exception('Backup file already exists.');
        }

        $zip = new ZipArchive();
        if ($zip->open($outputZipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('Cannot create backup file.');
        }

        $sourceDir = PMF_ROOT_DIR;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $zip->registerProgressCallback(0.05, function ($rate) use ($progressCallback) {
            $progress = sprintf('%d%%', $rate * 100);
            $progressCallback($progress);
        });

        foreach ($files as $file) {
            $file = realpath($file);
            if (!str_contains($file, self::PMF_UPGRADE_DIR . DIRECTORY_SEPARATOR)) {
                if (is_dir($file)) {
                    $zip->addEmptyDir(str_replace($sourceDir . DIRECTORY_SEPARATOR, '', $file . DIRECTORY_SEPARATOR));
                } elseif (is_file($file)) {
                    $zip->addFile($file, str_replace($sourceDir . DIRECTORY_SEPARATOR, '', $file));
                }
            }
        }

        $zip->close();

        return file_exists($outputZipFile);
    }

     /**
     * Method to delete the temporary created backup.
     *
     * @param string $backupName | Name of the created backup
     * @return bool
     */
    public function deleteTemporaryBackup(string $backupName): bool
    {
        if (is_file(self::PMF_UPGRADE_DIR . DIRECTORY_SEPARATOR . $backupName)) {
            return unlink(self::PMF_UPGRADE_DIR . DIRECTORY_SEPARATOR . $backupName);
        } else {
            return false;
        }
    }

    /**
     * Method to restore from the temporary backup
     *
     * @return void
     */
    public function restoreTemporaryBackup()
    {
    }

    /**
     * Method to install the package
     *
     * @param callable $progressCallback
     * @return bool
     */
    public function installPackage(callable $progressCallback): bool
    {
        $sourceDir = self::PMF_UPGRADE_DIR . '/new/phpmyfaq/';
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

            $currentFile++;
            $progress = $totalFiles > 0 ? sprintf('%d%%', ($currentFile / $totalFiles) * 100) : 100;
            call_user_func($progressCallback, $progress);
        }
        return true;
    }

    /**
     * Returns the host for download packages, so either github.com or download.phpmyfaq.de
     * @return string
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
     * @return string
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
     * @param string $version
     * @return string
     */
    public function getFilename(string $version): string
    {
        if ($this->isNightly()) {
            return sprintf(self::GITHUB_FILENAME, date('Y-m-d', strtotime('-1 days')));
        }

        return sprintf(self::PHPMYFAQ_FILENAME, $version);
    }

    /**
     * @return bool
     */
    public function isNightly(): bool
    {
        return $this->isNightly;
    }

    /**
     * @param bool $isNightly
     */
    public function setIsNightly(bool $isNightly): void
    {
        $this->isNightly = $isNightly;
    }
}
