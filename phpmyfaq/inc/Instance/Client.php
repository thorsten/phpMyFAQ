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
     * Constructor
     *
     * @param PMF_Configuration $config
     * @param PMF_Filesystem $fileSystem
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
        $this->setId($instance->getId());
        $this->addConfig('isMaster', false);
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
            dirname(__DIR__) . '/config/constants.php',
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
            dirname(__DIR__) . '/config/constants_ldap.php',
            $dest
        );
    }

    /**
     * Copies a defined template folder to a new client instance, by default
     * the default template located at ./template/default/ will be copied
     *
     * @param string $dest        Destination folder
     * @param string $templateDir Template folder
     *
     * @return bool
     */
    public function copyTemplateFolder($dest, $templateDir = 'default')
    {
        // @todo add code here
    }
}