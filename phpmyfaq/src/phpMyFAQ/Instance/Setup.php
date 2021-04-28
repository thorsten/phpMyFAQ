<?php

/**
 * The phpMyFAQ instances setup class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-04-04
 */

namespace phpMyFAQ\Instance;

use phpMyFAQ\Configuration;
use phpMyFAQ\User;

/**
 * Class Setup
 *
 * @package phpMyFAQ\Instance
 */
class Setup
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * Setup constructor.
     */
    public function __construct()
    {
        $this->setRootDir(PMF_SRC_DIR);
    }

    /**
     * Sets the root directory of the phpMyFAQ instance.
     *
     * @param string $rootDir
     */
    public function setRootDir($rootDir)
    {
        $this->rootDir = $rootDir;
    }

    /**
     * Creates the anonymous default user.
     *
     * @param Configuration $faqConfig
     */
    public function createAnonymousUser(Configuration $faqConfig)
    {
        $anonymous = new User($faqConfig);
        $anonymous->createUser('anonymous', '', '', -1);
        $anonymous->setStatus('protected');
        $anonymousData = [
            'display_name' => 'Anonymous User',
            'email' => null,
        ];
        $anonymous->setUserData($anonymousData);
    }

    /**
     * Checks basic folders and creates them if necessary.
     *
     * @param  string[] $dirs
     * @return array
     */
    public function checkDirs(array $dirs)
    {
        $failedDirs = [];

        foreach ($dirs as $dir) {
            if (false === is_dir($this->rootDir . $dir)) {
                // If the folder does not exist try to create it
                if (false === mkdir($this->rootDir . $dir)) {
                    // If the folder creation fails
                    $failedDirs[] = 'Folder [' . $dir . '] could not be created.';
                } else {
                    if (false === chmod($this->rootDir . $dir, 0775)) {
                        $failedDirs[] = 'Folder [' . $dir . '] could not be given correct permissions (775).';
                    }
                }
            } else {
                // The folder exists, check permissions
                if (false === is_writable($this->rootDir . $dir)) {
                    // If the folder exists but is not writeable
                    $failedDirs[] = 'Folder [' . $dir . '] exists but is not writable.';
                }
            }

            if (0 === count($failedDirs)) {
                // if no failed dirs exist
                copy(
                    $this->rootDir . '/setup/index.html',
                    $this->rootDir . $dir . '/index.html'
                );
            }
        }

        return $failedDirs;
    }

    /**
     * Creates the file /config/database.php.
     *
     * @param  array  $data   Array with database credentials
     * @param  string $folder Folder
     * @return int|bool
     */
    public function createDatabaseFile(array $data, $folder = '/config')
    {
        return file_put_contents(
            $this->rootDir . $folder . '/database.php',
            "<?php\n" .
            "\$DB['server'] = '" . $data['dbServer'] . "';\n" .
            "\$DB['port'] = '" . $data['dbPort'] . "';\n" .
            "\$DB['user'] = '" . $data['dbUser'] . "';\n" .
            "\$DB['password'] = '" . $data['dbPassword'] . "';\n" .
            "\$DB['db'] = '" . $data['dbDatabaseName'] . "';\n" .
            "\$DB['prefix'] = '" . $data['dbPrefix'] . "';\n" .
            "\$DB['type'] = '" . $data['dbType'] . "';",
            LOCK_EX
        );
    }

    /**
     * Creates the file /config/ldap.php.
     *
     * @param  array  $data   Array with LDAP credentials
     * @param  string $folder Folder
     * @return int|bool
     */
    public function createLdapFile(array $data, string $folder = '/config')
    {
        return file_put_contents(
            $this->rootDir . $folder . '/config/ldap.php',
            "<?php\n" .
            "\$PMF_LDAP['ldap_server'] = '" . $data['ldapServer'] . "';\n" .
            "\$PMF_LDAP['ldap_port'] = '" . $data['ldapPort'] . "';\n" .
            "\$PMF_LDAP['ldap_user'] = '" . $data['ldapUser'] . "';\n" .
            "\$PMF_LDAP['ldap_password'] = '" . $data['ldapPassword'] . "';\n" .
            "\$PMF_LDAP['ldap_base'] = '" . $data['ldapBase'] . "';",
            LOCK_EX
        );
    }

    /**
     * Creates the file /config/elasticsearch.php
     *
     * @param  array  $data   Array with LDAP credentials
     * @param  string $folder Folder
     * @return int|bool
     */
    public function createElasticsearchFile(array $data, string $folder = '/config')
    {
        return file_put_contents(
            $this->rootDir . $folder . '/config/elasticsearch.php',
            "<?php\n" .
            "\$PMF_ES['hosts'] = ['" . implode("'], ['", $data['hosts']) . "'];\n" .
            "\$PMF_ES['index'] = '" . $data['index'] . "';\n" .
            "\$PMF_ES['type'] = 'faqs';",
            LOCK_EX
        );
    }
}
