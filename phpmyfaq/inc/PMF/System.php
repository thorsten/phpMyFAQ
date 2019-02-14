<?php

/**
 * Class for checking system requirements.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-01-13
 */

/**
 * PMF_System.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-01-13
 */
class PMF_System
{
    /**
     * Major version.
     */
    const VERSION_MAJOR = 2;

    /**
     * Minor version.
     */
    const VERSION_MINOR = 9;

    /**
     * Patchlevel.
     */
    const VERSION_PATCHLEVEL = 13;

    /**
     * Pre-release version.
     */
    const VERSION_PRERELEASE = null;

    /**
     * API version.
     */
    const VERSION_API = '1.1';

    /**
     * Minimum required PHP version.
     */
    const VERSION_MINIMUM_PHP = '5.5.0';

    /**
     * Array of required PHP extensions.
     *
     * @var array
     */
    private $requiredExtensions = [
        'curl',
        'gd',
        'json',
        'xmlwriter',
        'filter',
        'zip',
        'fileinfo'
    ];

    /**
     * Array of missing PHP extensions.
     *
     * @var array
     */
    private $missingExtensions = [];

    /**
     * Supported databases for phpMyFAQ.
     *
     * @var array
     */
    private $supportedDatabases = [
        'mysqli' => [ self::VERSION_MINIMUM_PHP, 'MySQL 5.x / Percona Server 5.x / MariaDB 5.x and later' ],
        'pgsql' => [ self::VERSION_MINIMUM_PHP, 'PostgreSQL 9.x' ],
        'sqlite3' => [ self::VERSION_MINIMUM_PHP, 'SQLite 3' ],
        'mssql' => [ self::VERSION_MINIMUM_PHP, 'MS SQL Server 2012 and later (deprecated, PHP 5 only)' ],
        'sqlsrv' => [ self::VERSION_MINIMUM_PHP, 'MS SQL Server 2012 Driver for PHP (PHP 5 and PHP 7)']
    ];

    /**
     * Database handle.
     *
     * @var PMF_DB_Driver
     */
    private $database = null;

    /**
     * Sets the database handler.
     *
     * @param PMF_DB_Driver $database
     *
     * @return PMF_System
     */
    public function setDatabase(PMF_DB_Driver $database)
    {
        $this->database = $database;

        return $this;
    }

    /**
     * Returns the current version of phpMyFAQ for installation and
     * version in the database.
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
     * Returns all available templates as array.
     *
     * @return array
     */
    public function getAvailableTemplates()
    {
        $templates = [];

        foreach (new DirectoryIterator(PMF_ROOT_DIR.'/assets/template') as $item) {
            $basename = $item->getBasename();
            if (!$item->isDot() && $item->isDir()) {
                $templates[$basename] = (PMF_Template::getTplSetName() === $basename ? true : false);
            }
        }

        return $templates;
    }

    /**
     * Returns the current API version of phpMyFAQ for installation and
     * version in the database.
     *
     * @return int
     */
    public static function getApiVersion()
    {
        return self::VERSION_API;
    }

    /**
     * Returns the supported databases.
     *
     * @return array
     */
    public function getSupportedDatabases()
    {
        return $this->supportedDatabases;
    }

    /**
     * Returns the locally supported databases.
     *
     * @param bool $html
     *
     * @return array
     */
    public function getSupportedSafeDatabases($html = false)
    {
        $retVal = [];
        foreach ($this->getSupportedDatabases() as $extension => $database) {
            if (extension_loaded($extension) && version_compare(PHP_VERSION, $database[0]) >= 0) {
                if ($html) {
                    $retVal[] = sprintf('<option value="%s">%s</option>', $extension, $database[1]);
                } else {
                    $retVal[$extension] = $database;
                }
            }
        }

        return $retVal;
    }

    /**
     * Checks if the system URI is running with http or https.
     *
     * @param PMF_Configuration $faqConfig
     *
     * @return mixed
     */
    public function getSystemUri(PMF_Configuration $faqConfig)
    {
        $mainUrl = $faqConfig->getDefaultUrl();

        if (isset($_ENV['REQUEST_SCHEME']) && 'https' === $_ENV['REQUEST_SCHEME']) {
            if (false === strpos($mainUrl, 'https')) {
                $mainUrl = str_replace('http://', 'https://', $mainUrl);
            }
        }

        if ('/' !== substr($mainUrl, -1)) {
            $mainUrl .= '/';
        }

        return $mainUrl;
    }

    /**
     * Checks for installed database extensions, if the first supported
     * extension is enabled, return true.
     *
     * @return bool
     */
    public function checkDatabase()
    {
        foreach ($this->supportedDatabases as $extension => $database) {
            if (extension_loaded($extension)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks for required PHP extensions.
     *
     * @return bool
     */
    public function checkRequiredExtensions()
    {
        foreach ($this->requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                $this->missingExtensions[] = $extension;
            }
        }

        if (count($this->missingExtensions) > 0) {
            return false;
        }

        return true;
    }

    /**
     * Checks for an installed phpMyFAQ version.
     *
     * inc/data.php        -> phpMyFAQ 2.0 and 2.5
     * config/database.php -> phpMyFAQ 2.6 and later
     *
     * @return bool
     */
    public function checkphpMyFAQInstallation()
    {
        if (is_file(PMF_ROOT_DIR.'/inc/data.php') || is_file(PMF_ROOT_DIR.'/config/database.php')) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Returns all missing extensions.
     *
     * @return array
     */
    public function getMissingExtensions()
    {
        return $this->missingExtensions;
    }

    /**
     * Returns true or false on SQLite3.
     *
     * @static
     *
     * @param string $dbType
     *
     * @return bool
     */
    public static function isSqlite($dbType)
    {
        return ('sqlite3' === $dbType) ? true : false;
    }

    /**
     * Creates a JSON object with all .php files of phpMyFAQ with their sha1 hashes.
     *
     * @return string
     */
    public function createHashes()
    {
        $created = new DateTime();

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(PMF_ROOT_DIR),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $hashes = array(
            'created' => $created->format('Y-m-d H:i:sP'),
        );
        $blacklist = array(
            '/config/constants.php' => false,
            '/config/constants_ldap.php' => false,
            '/config/database.php' => false,
            '/config/ldap.php' => false,
        );
        $current = '';

        try {
            foreach ($files as $file) {
                if ('php' === pathinfo($file->getFilename(), PATHINFO_EXTENSION) &&
                !preg_match('#/tests/#', $file->getPath())
            ) {
                    $current = str_replace(PMF_ROOT_DIR, '', $file->getPathname());

                    if (isset($blacklist[$current])) {
                        continue;
                    }
                    $hashes[$current] = sha1(file_get_contents($file->getPathname()));
                }
            }
        } catch (UnexpectedValueException $e) {
            $hashes[$current.' failed'] = $e->getMessage();
        }

        return json_encode($hashes);
    }

    //
    // Methods to clean a phpMyFAQ installation
    //

    /**
     * Drops all given tables
     * @param array $queries
     */
    public function dropTables(Array $queries)
    {
        if ($this->database instanceof PMF_DB_Driver) {
            foreach ($queries as $query) {
                $this->database->query($query);
            }
        }
    }

    /**
     * Removes the database.php and the ldap.php if an installation failed.
     */
    public function cleanInstallation()
    {
        // Remove './config/database.php' file: no need of prompt anything to the user
        if (file_exists(PMF_ROOT_DIR.'/config/database.php')) {
            @unlink(PMF_ROOT_DIR.'/config/database.php');
        }
        // Remove './config/ldap.php' file: no need of prompt anything to the user
        if (file_exists(PMF_ROOT_DIR.'/config/ldap.php')) {
            @unlink(PMF_ROOT_DIR.'/config/ldap.php');
        }
    }

    /**
     * Print out the HTML5 Footer.
     *
     * @param bool $onePageBack
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
            '</div></section><footer class="footer"><div class="container"><p class="pull-right">%s</p><div></footer></body></html>',
            COPYRIGHT
        );
        exit(-1);
    }
}
