<?php

/**
 * The phpMyFAQ instances setup class.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2012-04-04
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Instance.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2012-04-04
 */
class PMF_Instance_Setup
{
    /**
     * @var string
     */
    private $_rootDir;

    /**
     * Constructor.
     *
     * @return PMF_Instance_Setup
     */
    public function __construct()
    {
        $this->setRootDir(PMF_INCLUDE_DIR);
    }

    /**
     * Sets the root directory of the phpMyFAQ instance.
     *
     * @param string $rootDir
     */
    public function setRootDir($rootDir)
    {
        $this->_rootDir = $rootDir;
    }

    /**
     * Creates the anonymous default user.
     *
     * @param PMF_Configuration $faqConfig
     */
    public function createAnonymousUser(PMF_Configuration $faqConfig)
    {
        $anonymous = new PMF_User($faqConfig);
        $anonymous->createUser('anonymous', null, -1);
        $anonymous->setStatus('protected');
        $anonymousData = array(
            'display_name' => 'Anonymous User',
            'email' => null,
        );
        $anonymous->setUserData($anonymousData);
    }

    /**
     * Checks basic folders and creates them if necessary.
     *
     * @param string[] $dirs
     *
     * @return array
     */
    public function checkDirs(Array $dirs)
    {
        $failedDirs = [];

        foreach ($dirs as $dir) {
            if (false === is_writable($this->_rootDir.$dir)) {
                $failedDirs[] = $dir;
            } elseif (false === is_dir($this->_rootDir.$dir)) {
                if (false === mkdir($this->_rootDir.$dir, 0775)) {
                    $failedDirs[] = $dir;
                }
            } else {
                copy(
                    $this->_rootDir.'/setup/index.html',
                    $this->_rootDir.$dir.'/index.html'
                );
            }
        }

        return $failedDirs;
    }

    /**
     * Creates the file /config/database.php.
     *
     * @param array  $data   Array with database credentials
     * @param string $folder Folder
     *
     * @return int
     */
    public function createDatabaseFile(Array $data, $folder = '/config')
    {
        $ret = file_put_contents(
            $this->_rootDir.$folder.'/database.php',
            "<?php\n".
            "\$DB['server'] = '".$data['dbServer']."';\n".
            "\$DB['user'] = '".$data['dbUser']."';\n".
            "\$DB['password'] = '".$data['dbPassword']."';\n".
            "\$DB['db'] = '".$data['dbDatabaseName']."';\n".
            "\$DB['prefix'] = '".$data['dbPrefix']."';\n".
            "\$DB['type'] = '".$data['dbType']."';",
            LOCK_EX
        );

        return $ret;
    }

    /**
     * Creates the file /config/ldap.php.
     *
     * @param array  $data   Array with LDAP credentials
     * @param string $folder Folder
     *
     * @return int
     */
    public function createLdapFile(Array $data, $folder = '/config')
    {
        $ret = file_put_contents(
            $this->_rootDir.$folder.'/config/ldap.php',
            "<?php\n".
            "\$PMF_LDAP['ldap_server'] = '".$data['ldapServer']."';\n".
            "\$PMF_LDAP['ldap_port'] = '".$data['ldapPort']."';\n".
            "\$PMF_LDAP['ldap_user'] = '".$data['ldapUser']."';\n".
            "\$PMF_LDAP['ldap_password'] = '".$data['ldapPassword']."';\n".
            "\$PMF_LDAP['ldap_base'] = '".$data['ldapBase']."';",
            LOCK_EX
        );

        return $ret;
    }

    /**
     * Creates the file /config/elasticsearch.php
     *
     * @param array  $data   Array with LDAP credentials
     * @param string $folder Folder
     *
     * @return int
     */
    public function createElasticsearchFile(Array $data, $folder = '/config')
    {
        $ret = file_put_contents(
            $this->_rootDir.$folder.'/config/elasticsearch.php',
            "<?php\n".
            "\$PMF_ES['hosts'] = ['".implode("'], ['", $data['hosts'])."'];\n".
            "\$PMF_ES['index'] = '".$data['index']."';\n".
            "\$PMF_ES['type'] = 'faqs';",
            LOCK_EX
        );

        return $ret;
    }

    /**
     * Creates a new folder.
     *
     * @param string $name Name of the new folder
     * @param string $path Path to the new folder
     *
     * @return boolean|null
     */
    public function createFolder($name, $path)
    {
        // @todo add code here
    }
}
