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
     * @return void
     */
    public function checkFilesystem()
    {
    }

    /**
     * Method to download a phpMyFAQ package, returns false if it doesn't work
     *
     * @todo handle possible proxy servers
     *
     * @param string $version
     * @return string|bool
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
     * @var string $version Version to verify
     * @return bool
     * @throws Exception|TransportExceptionInterface|ClientExceptionInterface|RedirectExceptionInterface|ServerExceptionInterface|JsonException
     */
    public function verifyPackage(string $version): bool
    {
        $client = HttpClient::create();
        $response = $client->request(
            'GET',
            $this->apiUrl . 'verify/' . $version
        );
        try {
            $remoteHashes = $response->getContent();
            if(json_decode($remoteHashes, null, 512, JSON_THROW_ON_ERROR) instanceof \stdClass){
                if(!is_array(json_decode($remoteHashes, true, 512, JSON_THROW_ON_ERROR))) {
                    return false;
                }
            return true;
            }
        } catch (Exception |
                 TransportExceptionInterface |
                 ClientExceptionInterface |
                 RedirectExceptionInterface |
                 ServerExceptionInterface |
                 \JsonException $e
        ) {
            $this->configuration->getLogger()->log(Level::Error, $e->getMessage());
            return false;
        }
    }
    /**
     * Method to unpack the downloaded phpMyFAQ package
     * @var string $path Path of the package
     * @return bool
     */
    public function unpackPackage(string $path): bool
    {
        $zip = new \ZipArchive();
        if (!$zip->open($path)) {
            $this->configuration->getLogger()->log(Level::Error, $zip->getStatusString());
            return false;
        } 
        else {
            if (!$zip->extractTo(PMF_CONTENT_DIR . '/upgrades/')) {
                $this->configuration->getLogger()->log(Level::Error, $zip->getStatusString());
                return false;
            }
            $zip->close();
            return true;
        }
    }

    /**
     * Method to create a temporary backup of the current files
     * @return void
     */
    public function createTemporaryBackup()
    {
    }

    /**
     * Method to restore from the temporary backup
     * @return void
     */
    public function restoreTemporaryBackup()
    {
    }

    /**
     * Method to install the package
     * @return void
     */
    public function installPackage()
    {
    }
}
