<?php

namespace phpMyFAQ;

/**
 * The main class for fetching the configuration, update and delete items. This
 * class is also a small Dependency Injection Container for phpMyFAQ.
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2006-01-04
 */

use Elasticsearch\Client;
use phpMyFAQ\Db\Driver;

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * Configuration.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2006-01-04
 */
class Configuration
{
    /**
     * @var string
     */
    protected $_tableName = 'faqconfig';

    /**
     * @var array
     */
    public $config = [];

    /**
     * Constructor.
     *
     * @param Driver $database
     */
    public function __construct(Driver $database)
    {
        $this->setDb($database);
    }

    /**
     * Fetches all configuration items into an array.
     */
    public function getAll()
    {
        $config = [];
        $query = sprintf('
            SELECT
                config_name, config_value
            FROM
                %s%s',
            Db::getTablePrefix(),
            $this->_tableName
        );

        $result = $this->getDb()->query($query);
        try {
            $config = $this->getDb()->fetchAll($result);
        } catch (Exception $e) {
            // @todo Added proper handling of exception
            echo $e->getMessage();
        }
        foreach ($config as $items) {
            $this->config[$items->config_name] = $items->config_value;
        }
    }

    /**
     * Returns a configuration item.
     *
     * @param string $item Configuration item
     *
     * @return mixed
     */
    public function get($item)
    {
        if (!isset($this->config[$item])) {
            $this->getAll();
        }

        if (isset($this->config[$item])) {
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

        return;
    }

    /**
     * Sets one single configuration item.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return bool
     */
    public function set($key, $value)
    {
        $query = sprintf("UPDATE %s%s SET config_value = '%s' WHERE config_name = '%s'",
            Db::getTablePrefix(),
            $this->_tableName,
            $this->getDb()->escape(trim($value)),
            $this->getDb()->escape(trim($key))
        );

        return $this->getDb()->query($query);
    }

    /**
     * Sets the phpMyFAQ\Db_Driver object.
     *
     * @param Driver $database
     */
    public function setDb(Driver $database)
    {
        $this->config['core.database'] = $database;
    }

    /**
     * Returns the phpMyFAQ\Db_Driver object.
     *
     * @return Driver
     */
    public function getDb()
    {
        return $this->config['core.database'];
    }

    /**
     * Sets the Instance object.
     *
     * @param Instance $instance
     */
    public function setInstance(Instance $instance)
    {
        $this->config['core.instance'] = $instance;
    }

    /**
     * Returns the Instance object.
     *
     * @return Instance
     */
    public function getInstance()
    {
        return $this->config['core.instance'];
    }

    /**
     * Sets the Language object.
     *
     * @param Language $language
     */
    public function setLanguage(Language $language)
    {
        $this->config['core.language'] = $language;
    }

    /**
     * Returns the Language object.
     *
     * @return Language
     */
    public function getLanguage()
    {
        return $this->config['core.language'];
    }

    /**
     * Returns the default language.
     *
     * @return string
     */
    public function getDefaultLanguage()
    {
        return str_replace(['language_', '.php'], '', $this->config['main.language']);
    }

    /**
     * Returns the default URL of the phpMyFAQ installation.
     *
     * @return string
     */
    public function getDefaultUrl()
    {
        $defaultUrl = $this->get('main.referenceURL');

        if (substr($defaultUrl, -1) !== '/') {
            return $defaultUrl.'/';
        } else {
            return $defaultUrl;
        }
    }

    /**
     * Sets the Ldap object.
     *
     * @param Ldap $ldap
     */
    public function setLdap(Ldap $ldap)
    {
        $this->config['core.ldap'] = $ldap;
    }

    /**
     * Returns the Ldap object.
     *
     * @return Ldap
     */
    public function getLdap()
    {
        return $this->config['core.ldap'];
    }

    /**
     * Sets the LDAP configuration.
     *
     * @param array $ldapConfig
     */
    public function setLdapConfig(Array $ldapConfig)
    {
        // Always add main LDAP server
        $this->config['core.ldapServer'][0] = [
            'ldap_server' => $ldapConfig['ldap_server'],
            'ldap_port' => $ldapConfig['ldap_port'],
            'ldap_user' => $ldapConfig['ldap_user'],
            'ldap_password' => $ldapConfig['ldap_password'],
            'ldap_base' => $ldapConfig['ldap_base'],
        ];

        // Add multiple LDAP servers if enabled
        if (true === $this->get('ldap.ldap_use_multiple_servers')) {
            $key = 1;
            while ($key >= 1) {
                if (isset($ldapConfig[$key])) {
                    $this->config['core.ldapServer'][$key] = $ldapConfig[$key];
                    ++$key;
                } else {
                    break;
                }
            }
        }

        // Set LDAP configuration
        $this->config['core.ldapConfig'] = [
            'ldap_use_multiple_servers' => $this->get('ldap.ldap_use_multiple_servers'),
            'ldap_mapping' => $this->getLdapMapping(),
            'ldap_use_domain_prefix' => $this->get('ldap.ldap_use_domain_prefix'),
            'ldap_options' => $this->getLdapOptions(),
            'ldap_use_memberOf' => $this->get('ldap.ldap_use_memberOf'),
            'ldap_use_sasl' => $this->get('ldap.ldap_use_sasl'),
            'ldap_use_anonymous_login' => $this->get('ldap.ldap_use_anonymous_login'),
        ];
    }

    /**
     * Returns the LDAP configuration.
     *
     * @return array
     */
    public function getLdapConfig()
    {
        return isset($this->config['core.ldapConfig']) ? $this->config['core.ldapConfig'] : [];
    }

    /**
     * Returns the LDAP mapping configuration.
     *
     * @return array
     */
    public function getLdapMapping()
    {
        return [
            'name' => $this->get('ldap.ldap_mapping.name'),
            'username' => $this->get('ldap.ldap_mapping.username'),
            'mail' => $this->get('ldap.ldap_mapping.mail'),
            'memberOf' => $this->get('ldap.ldap_mapping.memberOf')
        ];
    }

    /**
     * Returns the LDAP options configuration.
     * 
     * @return array
     */
    public function getLdapOptions()
    {
        return [
            'LDAP_OPT_PROTOCOL_VERSION' => $this->get('ldap.ldap_options.LDAP_OPT_PROTOCOL_VERSION'),
            'LDAP_OPT_REFERRALS' => $this->get('ldap.ldap_options.LDAP_OPT_REFERRALS')
        ];
    }

    /**
     * Returns the LDAP server(s).
     *
     * @return array
     */
    public function getLdapServer()
    {
        return isset($this->config['core.ldapServer']) ? $this->config['core.ldapServer'] : [];
    }

    /**
     * Sets the Elasticsearch client instance.
     *
     * @param Client $esClient
     */
    public function setElasticsearch(Client $esClient)
    {
        $this->config['core.elasticsearch'] = $esClient;
    }

    /**
     * Returns the Elasticsearch client instance.
     *
     * @return Client
     */
    public function getElasticsearch()
    {
        return $this->config['core.elasticsearch'];
    }

    /**
     * Sets the Elasticsearch configuration.
     *
     * @param array $data
     */
    public function setElasticsearchConfig(Array $data)
    {
        $this->config['core.elasticsearchConfig'] = $data;
    }

    /**
     * Returns the Elasticsearch configuration.
     *
     * @return array
     */
    public function getElasticsearchConfig()
    {
        return isset($this->config['core.elasticsearchConfig']) ? $this->config['core.elasticsearchConfig'] : [];
    }

    /**
     * Adds a configuration item for the database.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return bool
     */
    public function add($name, $value)
    {
        $insert = sprintf(
            "INSERT INTO
                %s%s
            VALUES
                ('%s', '%s')",
            Db::getTablePrefix(),
            $this->_tableName,
            $this->getDb()->escape(trim($name)),
            $this->getDb()->escape(trim($value))
        );

        return $this->getDb()->query($insert);
    }

    /**
     * Deletes a configuration item for the database.
     *
     * @param string $name
     *
     * @return bool
     */
    public function delete($name)
    {
        $delete = sprintf(
            "DELETE FROM
                %s%s
            WHERE
              config_name = '%s'",
            Db::getTablePrefix(),
            $this->_tableName,
            $this->getDb()->escape(trim($name))
        );

        return $this->getDb()->query($delete);
    }

    /**
     * Updates all configuration items.
     *
     * @param array $newConfigs Array with new configuration values
     *
     * @return bool
     */
    public function update(Array $newConfigs)
    {
        $runtimeConfigs = [
            'core.database',           // phpMyFAQ\Db_Driver
            'core.instance',           // Instance
            'core.language',           // Language
            'core.ldapServer',         // Ldap
            'core.ldapConfig',         // $LDAP
            'core.elasticsearch',      // Elasticsearch\Client
            'core.elasticsearchConfig' // $ES
        ];

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
                        Db::getTablePrefix(),
                        $this->_tableName,
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
     * Returns all sorting possibilities for FAQ records.
     *
     * @param string $current
     *
     * @return string
     */
    public static function sortingOptions($current)
    {
        global $LANG;

        $options = ['id', 'thema', 'visits', 'updated', 'author'];
        $output = '';

        foreach ($options as $value) {
            printf(
                '<option value="%s" %s>%s</option>',
                $value,
                ($value == $current) ? 'selected' : '',
                $LANG['ad_conf_order_'.$value]
            );
        }

        return $output;
    }
}
