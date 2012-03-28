<?php
/**
 * The main phpMyFAQ instances class
 *
 * PHP Version 5.2
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
 * @since     2012-02-20
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
 * @since     2012-02-20
 */
class PMF_Configuration_Instance extends PMF_Configuration
{
    /**
     * Tablename
     *
     * @var string
     */
    protected $_tableName = 'faqinstances_config';

    /**
     * Constructor
     *
     * @return PMF_Instance
     */
    public function __construct(PMF_DB_Driver $database)
    {
        parent::__construct($database);
    }

    /**
     * Adds a configuration item for the database
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return boolean
     */
    public function add($name, $value)
    {
        $insert = sprintf(
            "INSERT INTO
                %s%s
            VALUES
                (%d, '%s', '%s')",
            SQLPREFIX,
            $this->_tableName,
            $this->getInstance()->getId(),
            $this->getDb()->escape(trim($name)),
            $this->getDb()->escape(trim($value))
        );

        return $this->getDb()->query($insert);
    }



}