<?php
/**
 * The phpMyFAQ instances setup class
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
 * @since     2012-04-04
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
 * @since     2012-04-04
 */
class PMF_Instance_Setup
{
    /**
     * @var string
     */
    private $_rootDir;

    /**
     * Constructor
     *
     * @return PMF_Instance_Setup
     */
    public function __construct()
    {
        $this->setRootDir(dirname(__DIR__));
    }

    /**
     * Sets the root directory of the phpMyFAQ instance
     *
     * @param string $rootDir
     */
    public function setRootDir($rootDir)
    {
        $this->_rootDir = $rootDir;
    }

    /**
     * Checks basic folders and creates them if necessary
     *
     * @param array $dirs
     *
     * @return array
     */
    public function checkDirs(Array $dirs)
    {
        $failedDirs = array();

        foreach ($dirs as $dir) {
            if (! @is_dir($this->_rootDir . $dir)) {
                if (! @mkdir ($this->_rootDir . $dir, 0755)) {
                    $faileddirs[] = $dir;
                }
            } elseif (! @is_writable($this->_rootDir . $dir)) {
                $faileddirs[] = $dir;
            } else {
                @copy(
                    $this->_rootDir . '/install/index.html',
                    $this->_rootDir . $dir . '/index.html'
                );
            }
        }

        return $failedDirs;
    }

    /**
     * Creates the file /config/database.php
     *
     * @param array $data
     *
     * @return integer
     */
    public function createDatabaseFile(Array $data)
    {
        $ret = file_put_contents(
            $this->_rootDir  . '/config/database.php',
            "<?php\n" .
            "\$DB['server'] = '" . $data['dbServer'] . "';\n" .
            "\$DB['user'] = '" . $data['dbUser'] . "';\n" .
            "\$DB['password'] = '" . $data['dbPassword'] . "';\n" .
            "\$DB['db'] = '" . $data['dbDatabaseName'] . "';\n" .
            "\$DB['prefix'] = '" . $data['dbPrefix'] . "';\n" .
            "\$DB['type'] = '" . $data['dbType'] . "';",
            LOCK_EX
        );

        return $ret;
    }

    /**
     * Creates the file /config/ldap.php
     *
     * @param array $data
     *
     * @return integer
     */
    public function createLdapFile(Array $data)
    {
        $ret = file_put_contents(
            $this->_rootDir  . '/config/database.php',
            "<?php\n" .
            "\$PMF_LDAP['ldap_server'] = '" . $data['ldapServer'] . "';\n" .
            "\$PMF_LDAP['ldap_port'] = '" . $data['ldapPort'] . "';\n" .
            "\$PMF_LDAP['ldap_user'] = '" . $data['ldapUser'] . "';\n" .
            "\$PMF_LDAP['ldap_password'] = '" . $data['ldapPassword'] . "';\n" .
            "\$PMF_LDAP['ldap_base'] = '" . $data['ldapBase'] . "';",
            LOCK_EX
        );

        return $ret;
    }

    /**
     * Creates a new folder
     *
     * @param string $name Name of the new folder
     * @param string $path Path to the new folder
     *
     * @return bool
     */
    public function createFolder($name, $path)
    {
        // @todo add code here
    }
}