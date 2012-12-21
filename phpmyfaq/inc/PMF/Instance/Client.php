<?php
/**
 * The main phpMyFAQ instances class for instance clients
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Instance
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-03-31
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Instance
 *
 * @category  phpMyFAQ
 * @package   PMF_Instance
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-03-31
 */
class PMF_Instance_Client extends PMF_Instance
{
    /**
     * @var PMF_Filesystem
     */
    private $fileSystem;

    /**
     * URL of the client
     *
     * @var string
     */
    private $clientUrl;

    /**
     * Constructor
     *
     * @param PMF_Configuration $config
     *
     * @return PMF_Instance_Client
     */
    public function __construct(PMF_Configuration $config)
    {
        parent::__construct($config);
    }

    /**
     *
     * @param PMF_Instance $instance
     */
    public function createClient(PMF_Instance $instance)
    {
        $instance->addConfig('isMaster', 'false');
    }

    /**
     * Adds a new folder named by the given hostname in /path/to/faq/multisite/
     *
     * @param string $hostname Hostname of the client instance
     *
     * @return bool
     */
    public function createClientFolder($hostname)
    {
        $clientDir = PMF_ROOT_DIR . '/multisite/';

        if (!$this->fileSystem instanceof PMF_Filesystem) {
            $this->fileSystem = new PMF_Filesystem();
        }

        if (! is_writeable($clientDir)) {
            return false;
        }

        return $this->fileSystem->mkdir($clientDir . $hostname);
    }

    /**
     * Creates all tables with the given table prefix from the master tables
     *
     * @param string $prefix SQL table prefix
     *
     * @return bool
     */
    public function createClientTables($prefix)
    {
        $tableNames = $this->config->getDb()->getTableNames(PMF_Db::getTablePrefix());

        // First, create the client tables
        foreach ($tableNames as $tableName) {
            $this->config->getDb()->query(
                sprintf(
                    'CREATE TABLE %s%s SELECT * FROM %s WHERE 1 = 2',
                    $prefix,
                    str_replace(PMF_Db::getTablePrefix(), '', $tableName),
                    $tableName
                )
            );
        }

        // Then, copy data from the tables "faqconfig" , "faqright" and "faquser_right"
        $this->config->getDb()->query(
            sprintf(
                'INSERT INTO %sfaqconfig SELECT * FROM %sfaqconfig',
                $prefix,
                PMF_Db::getTablePrefix()
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
                PMF_Db::getTablePrefix()
            )
        );
        $this->config->getDb()->query(
            sprintf(
                'INSERT INTO %sfaquser_right SELECT * FROM %sfaquserright WHERE user_id = 1',
                $prefix,
                PMF_Db::getTablePrefix()
            )
        );
    }

    /**
     * Sets the PMF_Filesystem
     *
     * @param PMF_Filesystem $fileSystem
     *
     * @return void
     */
    public function setFileSystem(PMF_Filesystem $fileSystem)
    {
        $this->fileSystem = $fileSystem;
    }

    /**
     * Copies the config/constants.php file to a new client instance
     *
     * @param string $dest Destination file
     *
     * @return bool
     */
    public function copyConstantsFile($dest)
    {
        return $this->fileSystem->copy(
            $this->fileSystem->getRootPath() . '/config/constants.php',
            $dest
        );
    }

    /**
     * Copies the config/constants_ldap.php file to a new client instance
     *
     * @param string $dest Destination file
     *
     * @return bool
     */
    public function copyLdapConstantsFile($dest)
    {
        return $this->fileSystem->copy(
            $this->fileSystem->getRootPath() . '/config/constants_ldap.php',
            $dest
        );
    }

    /**
     * Copies a defined template folder to a new client instance, by default
     * the default template located at ./assets/template/default/ will be copied
     *
     * @param string $dest        Destination folder
     * @param string $templateDir Template folder
     *
     * @return bool
     */
    public function copyTemplateFolder($dest, $templateDir = 'default')
    {
        $sourceTpl = $this->fileSystem->getRootPath() . '/assets/template/' . $templateDir;
        $destTpl   = $dest . '/assets/template/';

        $this->fileSystem->recursiveCopy($sourceTpl, $destTpl);
    }

    /**
     * Sets client URL
     *
     * @param string $clientUrl
     */
    public function setClientUrl($clientUrl)
    {
        $this->clientUrl = $clientUrl;
    }

    /**
     * Returns client URL
     *
     * @return string
     */
    public function getClientUrl()
    {
        return $this->clientUrl;
    }
}