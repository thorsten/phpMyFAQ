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
     * Tablename for configurations
     *
     * @var string
     */
    private $_tableName = 'faqinstances_config';

    /**
     * @var FilesystemIterator
     */
    private $_fileSystemIterator;

    /**
     * @var PMF_Instance
     */
    private $_instance;

    /**
     *
     * @param PMF_Instance $instance
     */
    public function createClient(PMF_Instance $instance)
    {
        $this->_instance = $instance;
    }

    /**
     * Adds a configuration item for the database
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return boolean
     */
    public function addConfig($name, $value)
    {
        $insert = sprintf(
            "INSERT INTO
                %s%s
            VALUES
                (%d, '%s', '%s')",
            SQLPREFIX,
            $this->_tableName,
            $this->_instance->getId(),
            $this->_config->getDb()->escape(trim($name)),
            $this->_config->getDb()->escape(trim($value))
        );

        return $this->_config->getDb()->query($insert);
    }
}