<?php
/**
 * The phpMyFAQ instances basic database class
 *
 * PHP Version 5.4
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Instance
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2015-02-14
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Instance_Database
 *
 * @category  phpMyFAQ
 * @package   PMF_Instance
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2015-02-14
 */
class PMF_Instance_Database
{
    /**
     * @var PMF_Configuration
     */
    private $config;

    /**
     * DROP TABLE statements
     * 
     * @var array
     */
    private $dropTableStmts = [
        'DROP TABLE %sfaqadminlog',
        'DROP TABLE %sfaqattachment',
        'DROP TABLE %sfaqattachment_file',
        'DROP TABLE %sfaqcaptcha',
        'DROP TABLE %sfaqcategories',
        'DROP TABLE %sfaqcategoryrelations',
        'DROP TABLE %sfaqcategory_group',
        'DROP TABLE %sfaqcategory_user',
        'DROP TABLE %sfaqchanges',
        'DROP TABLE %sfaqcomments',
        'DROP TABLE %sfaqconfig',
        'DROP TABLE %sfaqdata',
        'DROP TABLE %sfaqdata_revisions',
        'DROP TABLE %sfaqdata_group',
        'DROP TABLE %sfaqdata_tags',
        'DROP TABLE %sfaqdata_user',
        'DROP TABLE %sfaqglossary',
        'DROP TABLE %sfaqgroup',
        'DROP TABLE %sfaqgroup_right',
        'DROP TABLE %sfaqinstances',
        'DROP TABLE %sfaqinstances_config',
        'DROP TABLE %sfaqnews',
        'DROP TABLE %sfaqquestions',
        'DROP TABLE %sfaqright',
        'DROP TABLE %sfaqsearches',
        'DROP TABLE %sfaqsessions',
        'DROP TABLE %sfaqstopwords',
        'DROP TABLE %sfaqtags',
        'DROP TABLE %sfaquser',
        'DROP TABLE %sfaquserdata',
        'DROP TABLE %sfaquserlogin',
        'DROP TABLE %sfaquser_group',
        'DROP TABLE %sfaquser_right',
        'DROP TABLE %sfaqvisits',
        'DROP TABLE %sfaqvoting',
    ];

    /**
     * Constructor
     *
     * @param PMF_Configuration $config
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Executes all DROP TABLE statements
     *
     * @param string $prefix
     *
     * @return boolean
     */
    public function dropTables($prefix = '')
    {
        foreach ($this->dropTableStmts as $stmt) {

            $result = $this->config->getDb()->query(sprintf($stmt, $prefix));

            if (!$result) {
                return false;
            }
        }
        return true;
    }
}
