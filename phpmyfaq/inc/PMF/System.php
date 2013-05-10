<?php
/**
 * Class for checking system requirements
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ 
 * @package   PMF_System
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2013 phpMyFAQ Team
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
 * @copyright 2010-2013 phpMyFAQ Team
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
    const VERSION_MINOR = 9;

    /**
     * Patchlevel
     */
    const VERSION_PATCHLEVEL = 0;

    /**
     * Pre-release version
     */
    const VERSION_PRERELEASE = 'dev';

    /**
     * API version
     */
    const VERSION_API = 1;

    /**
     * Minimum required PHP version
     */
    const VERSION_MINIMUM_PHP = '5.3.3';

    /**
     * Array of required PHP extensions
     *
     * @var array
     */
    private $_requiredExtensions = array(
        'gd',
        'json',
        'xmlwriter',
        'filter',
        'zip'
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
        'mysqli'  => 'MySQL 5.x, MariaDB 5.x (ext/mysqli)',
        'pgsql'   => 'PostgreSQL 8.x',
        'sqlite'  => 'SQLite',
        'sqlite3' => 'SQLite 3 (only PHP 5.3+, experimental)',
        'mssql'   => 'MS SQL Server (deprecated)',
        'sqlsrv'  => 'MS SQL Server Driver for PHP'
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
                self::VERSION_PRERELEASE
            );
        } else {
            return sprintf(
                '%d.%d.%d',
                self::VERSION_MAJOR,
                self::VERSION_MINOR,
                self::VERSION_PATCHLEVEL
            );
        }
    }

    /**
     * Returns all available templates as array
     *
     * @return array
     */
    public function getAvailableTemplates()
    {
        $templates = array();

        foreach (new DirectoryIterator(PMF_ROOT_DIR . '/assets/template') as $item) {

            if (! $item->isDot() && $item->isDir()) {
                $templates[$item->getBasename()] = (PMF_Template::getTplSetName() == $item->getBasename() ? true : false);
            }
        }

        return $templates;
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
     * Returns the locally supported databases.
     *
     * @param bool $html
     * @return array
     */
    public function getSupportedSafeDatabases($html = false)
    {
        $retVal = array();
        foreach ($this->getSupportedDatabases() as $extension => $database) {
            if (extension_loaded($extension)) {
                // prevent MySQLi with zend.ze1_compatibility_mode enabled due to a few cloning isssues
                if (($extension == 'mysqli') && ini_get('zend.ze1_compatibility_mode')) {
                    continue;
                }
                if ($html) {
                    $retVal[] = sprintf('<option value="%s">%s</option>', $extension, $database);
                } else {
                    $retVal[$extension] = $database;
                }
            }
        }
        return $retVal;
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
     * Checks if register_globals are activated
     *
     * @return boolean
     */
    public function checkRegisterGlobals()
    {
        if (ini_get('register_globals')) {
            return false;
        }
        return true;
    }

    /**
     * Checks if magic_quotes_gpc are activated
     *
     * @return boolean
     */
    public function checkMagicQuotesGpc()
    {
        if (ini_get('magic_quotes_gpc')) {
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
        if (is_file(PMF_ROOT_DIR . '/config/database.php')) {
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

    /**
     * Returns true or false on sqlite or sqlite3
     *
     * @static
     * @param string $dbType
     *
     * @return bool
     */
    public static function isSqlite($dbType)
    {
        return ('sqlite' === $dbType || 'sqlite3' === $dbType) ? true : false;
    }

    /**
     * Creates a JSON object with all .php files of phpMyFAQ with their sha1 hashes
     * @return string
     */
    public function createHashes()
    {
        $created = new DateTime();

        $path  = PMF_ROOT_DIR;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $hashes    = array(
            'created' => $created->format('Y-m-d H:i:sP')
        );
        $blacklist = array(
            '/config/constants.php'      => false,
            '/config/constants_ldap.php' => false,
            '/config/database.php'       => false,
            '/config/ldap.php'           => false
        );
        $current = '';

        try {

            foreach ($files as $file) {

            if ('php' === pathinfo($file->getFilename(), PATHINFO_EXTENSION) &&
                ! preg_match('#/tests/#', $file->getPath())
            ) {
                $current = str_replace($path, '', $file->getPathname());

                    if (isset($blacklist[$current])) {
                        continue;
                    }
                    $hashes[$current] = sha1(file_get_contents($file->getPathname()));
                }
            }

        } catch (UnexpectedValueException $e) {
            $hashes[$current . ' failed'] = $e->getMessage();
        }

        return json_encode($hashes);
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
        // Remove './config/database.php' file: no need of prompt anything to the user
        if (file_exists(PMF_ROOT_DIR . '/config/database.php')) {
            @unlink(PMF_ROOT_DIR . '/config/database.php');
        }
        // Remove './config/ldap.php' file: no need of prompt anything to the user
        if (file_exists(PMF_ROOT_DIR . '/config/ldap.php')) {
            @unlink(PMF_ROOT_DIR . '/config/ldap.php');
        }
    }

    /**
     * Print out the HTML5 Footer
     *
     * @deprecated
     *
     * @param bool $onePageBack
     *
     * @return void
     */
    public static function renderFooter($onePageBack = false)
    {
        if (true === $onePageBack) {
            printf(
                '<p><a href="javascript:history.back();">%s</a></p>',
                'Back to the setup page'
            );
        }
        printf(
            '</div></div></section><footer><div class="container"><p class="pull-right">%s</p><div></footer></body></html>',
            COPYRIGHT
        );
        exit(-1);
    }

}