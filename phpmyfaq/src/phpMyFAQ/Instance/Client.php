<?php

namespace phpMyFAQ\Instance;

/**
 * The main phpMyFAQ instances class for instance clients.
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-03-31
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Db;
use phpMyFAQ\Filesystem;
use phpMyFAQ\Instance;

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Instance.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      http://www.phpmyfaq.de
 * @since     2012-03-31
 */
class Client extends Instance
{
    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * URL of the client.
     *
     * @var string
     */
    private $clientUrl;

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        parent::__construct($config);
    }

    /**
     * @param Instance $instance
     */
    public function createClient(Instance $instance)
    {
        $instance->addConfig('isMaster', 'false');
    }

    /**
     * Adds a new folder named by the given hostname in /path/to/faq/multisite/.
     *
     * @param string $hostname Hostname of the client instance
     *
     * @return bool
     */
    public function createClientFolder($hostname)
    {
        $clientDir = PMF_ROOT_DIR.'/multisite/';

        if (!$this->fileSystem instanceof Filesystem) {
            $this->fileSystem = new Filesystem();
        }

        if (!is_writeable($clientDir)) {
            return false;
        }

        return $this->fileSystem->mkdir($clientDir.$hostname);
    }

    /**
     * Creates all tables with the given table prefix from the master tables.
     *
     * @param string $prefix SQL table prefix
     *
     * @return boolean|null
     */
    public function createClientTables($prefix)
    {
        // First, create the client tables
        $instanceDatabase = PMF_Instance_Database::factory($this->config, Db::getType());
        $instanceDatabase->createTables($prefix);

        // Then, copy data from the tables "faqconfig" , "faqright" and "faquser_right"
        $this->config->getDb()->query(
            sprintf(
                'INSERT INTO %sfaqconfig SELECT * FROM %sfaqconfig',
                $prefix,
                Db::getTablePrefix()
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
                Db::getTablePrefix()
            )
        );
        $this->config->getDb()->query(
            sprintf(
                'INSERT INTO %sfaquser_right SELECT * FROM %sfaquserright WHERE user_id = 1',
                $prefix,
                Db::getTablePrefix()
            )
        );
    }

    /**
     * Sets the PMF_Filesystem.
     *
     * @param Filesystem $fileSystem
     */
    public function setFileSystem(Filesystem $fileSystem)
    {
        $this->fileSystem = $fileSystem;
    }

    /**
     * Copies the config/constants.php file to a new client instance.
     *
     * @param string $dest Destination file
     *
     * @return bool
     */
    public function copyConstantsFile($dest)
    {
        return $this->fileSystem->copy(
            $this->fileSystem->getRootPath().'/config/constants.php',
            $dest
        );
    }

    /**
     * Copies a defined template folder to a new client instance, by default
     * the default template located at ./assets/themes/default/ will be copied.
     *
     * @param string $dest        Destination folder
     * @param string $templateDir Template folder
     *
     * @return boolean|null
     */
    public function copyTemplateFolder($dest, $templateDir = 'default')
    {
        $sourceTpl = $this->fileSystem->getRootPath().'/assets/themes/'.$templateDir;
        $destTpl = $dest.'/assets/themes/';

        $this->fileSystem->recursiveCopy($sourceTpl, $destTpl);
    }

    /**
     * Sets client URL.
     *
     * @param string $clientUrl
     */
    public function setClientUrl($clientUrl)
    {
        $this->clientUrl = $clientUrl;
    }

    /**
     * Returns client URL.
     *
     * @return string
     */
    public function getClientUrl()
    {
        return $this->clientUrl;
    }
}
