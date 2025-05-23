<?php

/**
 * The phpMyFAQ instances setup class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-04-04
 */

namespace phpMyFAQ\Instance;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\User;

/**
 * Class Setup
 *
 * @package phpMyFAQ\Instance
 */
class Setup
{
    private string $rootDir;

    /**
     * Setup constructor.
     */
    public function __construct()
    {
        $this->setRootDir(PMF_SRC_DIR);
    }

    /**
     * Sets the root directory of the phpMyFAQ instance.
     */
    public function setRootDir(string $rootDir): void
    {
        $this->rootDir = $rootDir;
    }

    /**
     * Creates the anonymous default user.
     *
     * @throws Exception
     */
    public function createAnonymousUser(Configuration $configuration): void
    {
        $user = new User($configuration);
        $user->createUser('anonymous', '', '', -1);
        $user->setStatus('protected');

        $anonymousData = [
            'display_name' => 'Anonymous User',
            'email' => '',
        ];
        $user->setUserData($anonymousData);
    }

    /**
     * Checks basic folders and creates them if necessary.
     *
     * @param  string[] $dirs
     * @return string[]
     */
    public function checkDirs(array $dirs): array
    {
        $failedDirs = [];

        foreach ($dirs as $dir) {
            if (false === is_dir($this->rootDir . $dir)) {
                // If the folder does not exist, try to create it
                if (false === mkdir($this->rootDir . $dir)) {
                    // If the folder creation fails
                    $failedDirs[] = 'Folder [' . $dir . '] could not be created.';
                } elseif (false === chmod($this->rootDir . $dir, 0775)) {
                    $failedDirs[] = 'Folder [' . $dir . '] could not be given correct permissions (775).';
                }
            } elseif (false === is_writable($this->rootDir . $dir)) {
                // The folder exists, check permissions
                // If the folder exists but is not writeable
                $failedDirs[] = 'Folder [' . $dir . '] exists but is not writable.';
            }

            if ([] === $failedDirs) {
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
     * Creates the file /content/core/config/database.php.
     *
     * @param  int[]|string[] $data   Array with database credentials
     * @param  string         $folder Folder
     * @throws Exception
     */
    public function createDatabaseFile(array $data, string $folder = '/content/core/config'): int|bool
    {
        if (!file_exists($this->rootDir . $folder)) {
            throw new Exception('File [' . $this->rootDir . $folder . '] does not exist.');
        }

        if (!is_writable($this->rootDir . $folder)) {
            throw new Exception('File [' . $this->rootDir . $folder . '] is not writable.');
        }

        return file_put_contents(
            $this->rootDir . $folder . '/database.php',
            '<?php
$DB[\'server\'] = \'' . $data['dbServer'] . "';\n" .
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
     * Creates the file /content/core/config/ldap.php.
     *
     * @param  int[]|string[] $data   Array with LDAP credentials
     * @param  string         $folder Folder
     */
    public function createLdapFile(array $data, string $folder = '/content/core/config'): int|bool
    {
        return file_put_contents(
            $this->rootDir . $folder . '/config/ldap.php',
            '<?php
$PMF_LDAP[\'ldap_server\'] = \'' . $data['ldapServer'] . "';\n" .
            "\$PMF_LDAP['ldap_port'] = '" . $data['ldapPort'] . "';\n" .
            "\$PMF_LDAP['ldap_user'] = '" . $data['ldapUser'] . "';\n" .
            "\$PMF_LDAP['ldap_password'] = '" . $data['ldapPassword'] . "';\n" .
            "\$PMF_LDAP['ldap_base'] = '" . $data['ldapBase'] . "';",
            LOCK_EX
        );
    }

    /**
     * Creates the file /content/core/config/elasticsearch.php
     *
     * @param  int[]|string[] $data   Array with Elasticsearch credentials
     * @param  string         $folder Folder
     */
    public function createElasticsearchFile(array $data, string $folder = '/content/core/config'): int|bool
    {
        return file_put_contents(
            $this->rootDir . $folder . '/config/elasticsearch.php',
            '<?php
$PMF_ES[\'hosts\'] = [\'' . implode("'], ['", $data['hosts']) . "'];\n" .
            "\$PMF_ES['index'] = '" . $data['index'] . "';\n",
            LOCK_EX
        );
    }

    /**
     * Creates the file /content/core/config/opensearch.php
     *
     * @param  int[]|string[] $data   Array with OpenSearch credentials
     * @param  string         $folder Folder
     */
    public function createOpenSearchFile(array $data, string $folder = '/content/core/config'): int|bool
    {
        return file_put_contents(
            $this->rootDir . $folder . '/config/opensearch.php',
            '<?php
$PMF_OS[\'hosts\'] = [\'' . implode("'], ['", $data['hosts']) . "'];\n" .
            "\$PMF_OS['index'] = '" . $data['index'] . "';\n",
            LOCK_EX
        );
    }
}
