<?php
/**
 * The main class for fetching the configuration, update and delete items. This
 * class is also a small Dependency Injection Container for phpMyFAQ.
 *
 * PHP Version 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Configuration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
            $this->setDb($database);
        } else {
            $this->setDb(PMF_Db::getInstance());
        }
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
                %s%s",
            SQLPREFIX,
            self::TABLE_FAQCONFIG
        );
            
        $result = $this->getDb()->query($query);
        $config = $this->getDb()->fetchAll($result);
        
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
     * Sets the PMF_DB_Driver object
     *
     * @param PMF_DB_Driver $database
     *
     * @return void
     */
    public function setDb(PMF_DB_Driver $database)
    {
        $this->config['core.database'] = $database;
    }

    /**
     * Returns the PMF_DB_Driver object
     *
     * @return PMF_DB_Driver
     */
    public function getDb()
    {
        return $this->config['core.database'];
    }

    /**
     * Sets the PMF_Language object
     *
     * @param PMF_Language $language
     *
     * @return void
     */
    public function setLanguage(PMF_Language $language)
    {
        $this->config['core.language'] = $language;
    }

    /**
     * Returns the PMF_Language object
     *
     * @return PMF_Language
     */
    public function getLanguage()
    {
        return $this->config['core.language'];
    }

    /**
     * Sets the PMF_Ldap object
     *
     * @param PMF_Ldap $ldap
     *
     * @return void
     */
    public function setLdap(PMF_Ldap $ldap)
    {
        $this->config['core.ldap'] = $ldap;
    }

    /**
     * Returns the PMF_Ldap object
     *
     * @return PMF_Ldap
     */
    public function getLdap()
    {
        return $this->config['core.ldap'];
    }

    /**
     * Sets the LDAP configuration
     *
     * @param Array $ldapConfig
     *
     * @return void
     */
    public function setLdapConfig(Array $ldapConfig)
    {
        $this->config['core.ldapConfig'] = $ldapConfig;
    }

    /**
     * Returns the LDAP configuration
     *
     * @return array
     */
    public function getLdapConfig()
    {
        return $this->config['core.ldapConfig'];
    }


    /**
     * Updates all configuration items
     *
     * @param  array $newConfigs Array with new configuration values
     *
     * @return bool
     */
    public function update(Array $newConfigs)
    {
        $runtimeConfigs = array(
            'core.database',  // PMF_DB_Driver
            'core.language',  // PMF_Language
            'core.ldap',      // PMF_Ldap
            'core.ldapConfig' // $PMF_LDAP
        );
        if (is_array($newConfigs)) {
            foreach ($newConfigs as $name => $value) {
                if ($name != 'main.phpMyFAQToken' &&
                    !in_array($name, $runtimeConfigs)
                ) {
                    $update = sprintf("
                        UPDATE
                            %s%s
                        SET
                            config_value = '%s'
                        WHERE
                            config_name = '%s'",
                        SQLPREFIX,
                        self::TABLE_FAQCONFIG,
                        $this->getDb()->escape(trim($value)),
                        $name
                    );
                        
                    $this->getDb()->query($update);
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