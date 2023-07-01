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
    private const DOWNLOAD_URL = 'https://download.phpmyfaq.de/';

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
        $url = self::DOWNLOAD_URL . $zipFile;

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
     * @return void
     */
    public function verifyPackage()
    {
    }

    /**
     * Method to unpack the downloaded phpMyFAQ package
     * @return void
     */
    public function unpackPackage()
    {
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
