<?php
/**
 * Class for checking system requirements
 *
 * PHP Version 5.2
 *

 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ 
 * @package   PMF_System
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-01-13
 */

/**
 * PMF_System
 *
 * @category  phpMyFAQ 
 * @package   PMF_System
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-01-13
 */
class PMF_System
{
    /**
     * Major version
     */
    const VERSION_MAJOR = 2;

    /**
     * Minor version
     */
    const VERSION_MINOR = 8;

    /**
     * Patchlevel
     */
    const VERSION_PATCHLEVEL = 0;

    /**
     * Pre-release version
     */
    const VERSION_PRERELEASE = 'alpha2';

    /**
     * API version
     */
    const VERSION_API = 1;

    /**
     * Minimum required PHP version
     */
    const VERSION_MINIMUM_PHP = '5.2.3';

    /**
     * Array of required PHP extensions
     * @var array
     */
    private $_requiredExtensions = array(
        'gd',
        'json',
        'xmlwriter',
        'filter'
    );

    /**
     * Array of missing PHP extensions
     *
     * @var array
     */
    private $_missingExtensions = array();


    /**
     * Supported databases for phpMyFAQ.
     *
     * @var  array
     */
    private $_supportedDatabases = array(
        'mysql'   => array('5.2.0', 'MySQL 5.x (ext/mysql)'),
        'mysqli'  => array('5.2.0', 'MySQL 5.x (ext/mysqli)'),
        'mysqli'  => array('5.2.0', 'MariaDB 5.x (experimental)'),
        'pgsql'   => array('5.2.0', 'PostgreSQL 8.x'),
        'sqlite'  => array('5.2.0', 'SQLite'),
        'sqlite3' => array('5.3.0', 'SQLite 3 (only PHP 5.3+, experimental)'),
        'mssql'   => array('5.2.0', 'MS SQL Server 2005 / 2008'),
        'sqlsrv'  => array('5.2.0', 'SQL Server Driver for PHP (experimental)')
    );

    /**
     * Database handle
     *
     * @var PMF_DB_Driver
     */
    private $_database = null;

    /**
     * Sets the database handler
     *
     * @param PMF_DB_Driver $database
     *
     * @return PMF_System
     */
    public function setDatabase(PMF_DB_Driver $database)
    {
        $this->_database = $database;

        return $this;
    }

    /**
     * Returns the current version of phpMyFAQ for installation and
     * version in the database
     *
     * Releases will be numbered with the follow format:
     * <major>.<minor>.<patch>[-<prerelease>]
     *
     * @return string
     */
    public static function getVersion()
    {
        if (null !== self::VERSION_PRERELEASE) {
            return sprintf(
                '%d.%d.%d-%s',
                self::VERSION_MAJOR,
                self::VERSION_MINOR,
                self::VERSION_PATCHLEVEL,
                self::VERSION_PRERELEASE);
        } else {
            return sprintf(
                '%d.%d.%d',
                self::VERSION_MAJOR,
                self::VERSION_MINOR,
                self::VERSION_PATCHLEVEL);
        }
    }

    /**
     * Returns the current API version of phpMyFAQ for installation and
     * version in the database
     *
     * @return integer
     */
    public static function getApiVersion()
    {
        return self::VERSION_API;
    }

    /**
     * Returns the supported databases
     *
     * @return array
     */
    public function getSupportedDatabases()
    {
        return $this->_supportedDatabases;
    }

    /**
     * Checks for installed database extensions, if the first supported
     * extension is enabled, return true.
     *
     * @return boolean
     */
    public function checkDatabase()
    {
        foreach ($this->_supportedDatabases as $extension => $database) {
            if (extension_loaded ($extension)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Checks for required PHP extensions
     *
     * @return boolean
     */
    public function checkRequiredExtensions()
    {
        foreach ($this->_requiredExtensions as $extension) {
            if (!extension_loaded ( $extension)) {
                $this->_missingExtensions[] = $extension;
            }
        }
        
        if (count($this->_missingExtensions) > 0) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Checks for an installed phpMyFAQ version
     *
     * inc/data.php        -> phpMyFAQ 2.0 and 2.5
     * config/database.php -> phpMyFAQ 2.6 and later
     *
     * @return boolean
     */
    public function checkphpMyFAQInstallation()
    {
        if (is_file(PMF_ROOT_DIR . '/inc/data.php') || is_file(PMF_ROOT_DIR . '/config/database.php')) {
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * Returns all missing extensions
     *
     * @return array
     */
    public function getMissingExtensions()
    {
        return $this->_missingExtensions;
    }

    //
    // Methods to clean a phpMyFAQ installation
    //

    /**
     * @param array $queries
     */
    public function dropTables(Array $queries)
    {
        if ($this->_database instanceof PMF_DB_Driver) {
            foreach ($queries as $query) {
                $this->_database->query($query);
            }
        }
    }

    /**
     * Removes the database.php and the ldap.php if an installation failed
     *
     * @return void
     */
    public function cleanInstallation()
    {
        // Remove 'database.php' file: no need of prompt anything to the user
        if (file_exists(PMF_ROOT_DIR.'/config/database.php')) {
            @unlink(PMF_ROOT_DIR.'/config/database.php');
        }
        // Remove 'dap.php' file: no need of prompt anything to the user
        if (file_exists(PMF_ROOT_DIR.'/config/ldap.php')) {
            @unlink(PMF_ROOT_DIR.'/config/ldap.php');
        }
    }


}