<?php
/**
 * The main class for fetching the configuration, update and delete items. This
 * class is also a small Dependency Injection Container for phpMyFAQ.
 * 
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * @category  phpMyFAQ
 * @package   PMF_Configuration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2006-01-04
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Configuration
 *
 * @category  phpMyFAQ
 * @package   PMF_Configuration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2006-01-04
 */
class PMF_Configuration
{
    /**
     * Tablename
     */
    const TABLE_FAQCONFIG = 'faqconfig';

    /**
     * Instance
     * 
     * @var PMF_Configuration
     */
    private static $instance = null;
    
    /**
     * DB handle
     *
     * @var PMF_DB_Driver
     */
    protected $_db = null;

    /**
     * Configuration array
     *
     * @var array
     */
    public $config = array();

    /**
     * Constructor
     *
     * @param PMF_DB_Driver $database
     *
     * @return PMF_Configuration
     */
    public function __construct(PMF_DB_Driver $database = null)
    {
        if (! is_null($database)) {
            $this->_db = $database;
        } else {
            $this->_db = PMF_Db::getInstance();
        }
    }

    /**
     * Returns the single instance
     *
     * @deprecated
     *
     * @return PMF_Configuration
     */
    public static function getInstance()
    {
        if (null == self::$instance) {
            $className = __CLASS__;
            self::$instance = new $className();
        }
        return self::$instance;
    }
       
    /**
     * Fetches all configuration items into an array
     *
     * @return void
     */
    public function getAll()
    {
        $query = sprintf("
            SELECT
                config_name, config_value
            FROM
                %sfaqconfig",
            SQLPREFIX);
            
        $result = $this->_db->query($query);
        $config = $this->_db->fetchAll($result);
        
        foreach ($config as $items) {
            $this->config[$items->config_name] = $items->config_value;
        }
        
    }

    /**
     * Returns a configuration item
     *
     * @param  string $item Configuration item
     * 
     * @return mixed
     */
    public function get($item)
    {
        if (!isset($this->config[$item])) {
            $this->getAll();
        }
        switch ($this->config[$item]) {
            case 'true':
                return true;
                break;
            case 'false':
                return false;
                break;
            default:
                return $this->config[$item];
                break;
        }
    }

    /**
     * Updates all configuration items
     *
     * @param  array $newconfig Array with new configuration values
     * 
     * @return bool
     */
    public function update(Array $newconfig)
    {
        if (is_array($newconfig)) {
            foreach ($newconfig as $name => $value) {
                if ($name != 'main.phpMyFAQToken') {
                    $update = sprintf("
                        UPDATE
                            %sfaqconfig
                        SET
                            config_value = '%s'
                        WHERE
                            config_name = '%s'",
                        SQLPREFIX,
                        $this->_db->escape(trim($value)),
                        $name);
                        
                    $this->_db->query($update);
                    if (isset($this->config[$name])) {
                        unset($this->config[$name]);
                    }
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Returns all sorting possibilities for FAQ records
     *
     * @param string $current
     *
     * @return string
     */
    public static function sortingOptions($current)
    {
        global $PMF_LANG;

        $options = array('id', 'thema', 'visits', 'datum', 'author');
        $output = '';

        foreach ($options as $value) {
            printf('<option value="%s"%s>%s</option>',
                $value,
                ($value == $current) ? ' selected="selected"' : '',
                $PMF_LANG['ad_conf_order_'.$value]);
        }

        return $output;
    }
}