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

use Monolog\Level;
use phpMyFAQ\Configuration;
use phpMyFAQ\Setup;
use phpMyFAQ\System;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use PhpZip\ZipFile;
use PhpZip\Exception\ZipException;

class Upgrade extends Setup
{
    private const DOWNLOAD_URL_PRODUCTION = 'https://download.phpmyfaq.de/';

    private const DOWNLOAD_URL_DEVELOPMENT = 'https://github.com/thorsten/phpMyFAQ/releases/tag/';

    public function __construct(protected System $system, private readonly Configuration $configuration)
    {
        parent::__construct($this->system);
    }

    /**
     * Method to check if the filesystem is ready for the upgrade
     *
     * @return bool
     */
    public function checkFilesystem(): bool
    {
        if (!is_dir(PMF_CONTENT_DIR . '\upgrades')) {
            if (!mkdir(PMF_CONTENT_DIR . '\upgrades')) {
                return false;
            }
        }
        if (
            is_dir(PMF_CONTENT_DIR . '\user\attachments') && is_dir(PMF_CONTENT_DIR . '\user\images') && is_dir(
                PMF_CONTENT_DIR . '\core\data'
            ) && is_dir(PMF_ROOT_DIR . '\assets\themes')
        ) {
            if (
                is_file(PMF_CONTENT_DIR . '\core\config\constants.php') && is_file(
                    PMF_CONTENT_DIR . '\core\config\database.php'
                )
            ) {
                if ($this->configuration->isElasticsearchActive()) {
                    if (!is_file(PMF_CONTENT_DIR . '\core\config\elasticsearch.php')) {
                        return false;
                    }
                }
                if ($this->configuration->isLdapActive()) {
                    if (!is_file(PMF_CONTENT_DIR . '\core\config\ldap.php')) {
                        return false;
                    }
                }
                if ($this->configuration->isSignInWithMicrosoftActive()) {
                    if (!is_file(PMF_CONTENT_DIR . '\core\config\azure.php')) {
                        return false;
                    }
                }

                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Method to download a phpMyFAQ package, returns false if it doesn't work
     *
     * @param string $version
     * @return string|bool
     * @todo handle possible proxy servers
     */
    public function downloadPackage(string $version): string|bool
    {
        $zipFile = 'phpMyFAQ-' . $version . '.zip';
        $url = self::DOWNLOAD_URL_PRODUCTION . $zipFile;

        $client = HttpClient::create();

        try {
            $response = $client->request('GET', $url);

            if ($response->getStatusCode() !== 200) {
                return false;
            }

            $package = $response->getContent();

            file_put_contents(PMF_CONTENT_DIR . '/upgrades/' . $zipFile, $package);

            return PMF_CONTENT_DIR . '/upgrades/' . $zipFile;
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
     * Method to verify the downloaded phpMyFAQ package
     *
     * @return bool
     * @throws TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|JsonException
     * @param string $path | Path to zip file
     * @param string $version | Version to verify
     */
    public function verifyPackage(string $path, string $version): bool
    {
        $client = HttpClient::create();
        $response = $client->request(
            'GET',
            self::DOWNLOAD_URL_PRODUCTION . 'info/' . $version
        );

        try {
            $responseContent = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
            if (hash_file('md5', $path) === $responseContent['zip']['md5']) {
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
     * Method to unpack the downloaded phpMyFAQ package
     *
     * @return bool
     * @param string $path | Path of the package
     * @throws ZipException
     */
    public function unpackPackage(string $path): bool
    {
        $zip = new ZipFile();
        try {
            if (!is_file($path)) {
                return false;
            } else {
                $zip->openFile($path);
                $zip->extractTo(PMF_CONTENT_DIR . '/upgrades/');
                $zip->close();
                return true;
            }
        } catch (ZipException $e) {
            $this->configuration->getLogger()->log(Level::Error, $e->getMessage());

            return false;
        }
    }

    /**
     * Method to create a temporary backup of the current files
     *
     * @param string $backupName | Name of the created backup
     * @return bool
     * @throws ZipException
     */
    public function createTemporaryBackup(string $backupName): bool
    {
        try {
            $zip = new ZipFile();
            if (!is_file(PMF_CONTENT_DIR . '/upgrades/' . $backupName)) {
                $zip->addDirRecursive(PMF_ROOT_DIR);
                $zip->saveAsFile(PMF_CONTENT_DIR . '/upgrades/' . $backupName);
                $zip->close();
                return true;
            } else {
                return false;
            }
        } catch (ZipException $e) {
            $this->configuration->getLogger()->log(Level::Error, $e->getMessage());

            return false;
        }
    }

     /**
     * Method to delete the temporary created backup.
     *
     * @param string $backupName | Name of the created backup
     * @return bool
     */
    public function deleteTemporaryBackup(string $backupName): bool
    {
        if(is_file(PMF_CONTENT_DIR . '/upgrades/' . $backupName)) {
            return unlink(PMF_CONTENT_DIR . '/upgrades/' . $backupName);
        }
        else {
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
     * @return void
     */
    public function installPackage()
    {
    }
}
