<?php

/**
 * The main phpMyFAQ instances class for instance clients.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-03-31
 */

namespace phpMyFAQ\Instance;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filesystem;
use phpMyFAQ\Instance;
use phpMyFAQ\Instance\Database as InstanceDatabase;

/**
 * Class Client
 *
 * @package phpMyFAQ\Instance
 */
class Client extends Instance
{
    private ?Filesystem $fileSystem = null;

    private readonly string $clientFolder;

    private string $clientUrl;

    /**
     * Constructor.
     */
    public function __construct(Configuration $config)
    {
        parent::__construct($config);

        $this->clientFolder = PMF_ROOT_DIR . '/multisite/';
    }

    /**
     * Sets client URL.
     */
    public function setClientUrl(string $clientUrl): void
    {
        $this->clientUrl = $clientUrl;
    }

    /**
     * Sets the Filesystem.
     */
    public function setFileSystem(Filesystem $fileSystem): void
    {
        $this->fileSystem = $fileSystem;
    }

    public function createClient(Instance $instance): void
    {
        $instance->addConfig('isMaster', 'false');
    }

    /**
     * Adds a new folder named by the given hostname in /path/to/faq/multisite/.
     *
     * @param string $hostname Hostname of the client instance
     */
    public function createClientFolder(string $hostname): bool
    {
        if (!$this->isMultiSiteWriteable()) {
            return false;
        }

        return $this->fileSystem->createDirectory($this->clientFolder . $hostname);
    }

    /**
     * Creates all tables with the given table prefix from the master tables.
     *
     * @param string $prefix SQL table prefix
     * @return void
     */
    public function createClientTables(string $prefix)
    {
        try {
            // First, create the client tables
            $instanceDatabase = InstanceDatabase::factory($this->config, Database::getType());
            $instanceDatabase->createTables($prefix);

            // Then, copy data from the tables "faqconfig" , "faqright" and "faquser_right"
            $this->config->getDb()->query(
                sprintf(
                    'INSERT INTO %sfaqconfig SELECT * FROM %sfaqconfig',
                    $prefix,
                    Database::getTablePrefix()
                )
            );
            $this->config->getDb()->query(
                sprintf(
                    "UPDATE %sfaqconfig SET config_value = '%s' WHERE config_name = 'main.referenceURL'",
                    $prefix,
                    $this->clientUrl
                )
            );
            $this->config->getDb()->query(
                sprintf(
                    'INSERT INTO %sfaqright SELECT * FROM %sfaqright',
                    $prefix,
                    Database::getTablePrefix()
                )
            );
            $this->config->getDb()->query(
                sprintf(
                    'INSERT INTO %sfaquser_right SELECT * FROM %sfaquser_right WHERE user_id = 1',
                    $prefix,
                    Database::getTablePrefix()
                )
            );
        } catch (Exception) {
        }
    }

    /**
     * Copies the config/constants.php file to a new client instance.
     *
     * @param string $destination Destination file
     * @throws Exception
     */
    public function copyConstantsFile(string $destination): bool
    {
        return $this->fileSystem->copy(
            $this->fileSystem->getRootPath() . '/config/constants.php',
            $destination
        );
    }

    /**
     * Copies a defined template folder to a new client instance, by default
     * the default template located at ./assets/themes/default/ will be copied.
     *
     * @param string $destination Destination folder
     * @param string $templateDir Template folder
     * @return void
     * @throws Exception
     */
    public function copyTemplateFolder(string $destination, string $templateDir = 'default')
    {
        $sourceTpl = $this->fileSystem->getRootPath() . '/assets/themes/' . $templateDir;
        $destTpl = $destination . '/assets/themes/';

        $this->fileSystem->recursiveCopy($sourceTpl, $destTpl);
    }

    /**
     * Moves the entire folder to the new destination
     */
    public function moveClientFolder(string $sourceUrl, string $destinationUrl): bool
    {
        if (!$this->isMultiSiteWriteable()) {
            return false;
        }

        $sourcePath = str_replace('https://', '', $sourceUrl);
        $destinationPath = str_replace('https://', '', $destinationUrl);

        return $this->fileSystem->moveDirectory(
            $this->clientFolder . $sourcePath,
            $this->clientFolder . $destinationPath
        );
    }

    /**
     * Deletes the given client folder
     */
    public function deleteClientFolder(string $sourceUrl): bool
    {
        if (!$this->isMultiSiteWriteable()) {
            return false;
        }

        $sourcePath = str_replace('https://', '', $sourceUrl);
        return $this->fileSystem->deleteDirectory($this->clientFolder . $sourcePath);
    }

    /**
     * Checks if the multisite/ folder is writeable
     */
    public function isMultiSiteWriteable(): bool
    {
        if (!is_writeable($this->clientFolder)) {
            return false;
        }

        return true;
    }
}
