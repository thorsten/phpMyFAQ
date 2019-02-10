<?php

/**
 * The main phpMyFAQ instances class.
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
 * @since     2012-02-20
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
 * @since     2012-02-20
 */
class PMF_Instance
{
    /**
     * Configuration.
     *
     * @var PMF_Configuration
     */
    protected $config = null;

    /**
     * Instance ID.
     *
     * @var int
     */
    protected $id;

    /**
     * Instance configuration.
     *
     * @var array
     */
    protected $instanceConfig = [];

    /**
     * Constructor.
     *
     * @param PMF_Configuration $config
     *
     * @return PMF_Instance
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Adds a new instance.
     *
     * @param array $data
     *
     * @return int $id
     */
    public function addInstance(Array $data)
    {
        $this->setId($this->config->getDb()->nextId(PMF_Db::getTablePrefix().'faqinstances', 'id'));

        $insert = sprintf(
            "INSERT INTO %sfaqinstances VALUES (%d, '%s', '%s', '%s', %s, %s)",
            PMF_Db::getTablePrefix(),
            $this->getId(),
            $data['url'],
            $data['instance'],
            $data['comment'],
            $this->config->getDb()->now(),
            $this->config->getDb()->now()
        );

        if (!$this->config->getDb()->query($insert)) {
            return 0;
        }

        return $this->getId();
    }

    /**
     * Sets the instance ID.
     *
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = (int) $id;
    }

    /**
     * Returns the current instance id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns all instances.
     *
     * @return array
     */
    public function getAllInstances()
    {
        $select = sprintf(
            'SELECT * FROM %sfaqinstances ORDER BY id',
            PMF_Db::getTablePrefix()
        );

        $result = $this->config->getDb()->query($select);

        return $this->config->getDb()->fetchAll($result);
    }

    /**
     * Returns the instance.
     *
     * @param int $id
     *
     * @return array
     */
    public function getInstanceById($id)
    {
        $select = sprintf(
            'SELECT * FROM %sfaqinstances WHERE id = %d',
            PMF_Db::getTablePrefix(),
            (int) $id
        );

        $result = $this->config->getDb()->query($select);

        return $this->config->getDb()->fetchObject($result);
    }

    /**
     * Returns the instance.
     *
     * @param string $url
     *
     * @return array
     */
    public function getInstanceByUrl($url)
    {
        $select = sprintf(
            "SELECT * FROM %sfaqinstances WHERE url = '%s'",
            PMF_Db::getTablePrefix(),
            $url
        );

        $result = $this->config->getDb()->query($select);

        return $this->config->getDb()->fetchObject($result);
    }

    /**
     * Returns the configuration of the given instance ID.
     *
     * @param int $id
     *
     * @return array
     */
    public function getInstanceConfig($id)
    {
        $query = sprintf('
            SELECT
                config_name, config_value
            FROM
                %sfaqinstances_config
            WHERE
                instance_id = %d',
            PMF_Db::getTablePrefix(),
            $id
        );

        $result = $this->config->getDb()->query($query);
        $config = $this->config->getDb()->fetchAll($result);

        foreach ($config as $items) {
            $this->instanceConfig[$items->config_name] = $items->config_value;
        }

        return $this->instanceConfig;
    }

    /**
     * Updates the instance data.
     *
     * @param int   $id
     * @param array $data
     *
     * @return bool
     */
    public function updateInstance($id, Array $data)
    {
        $update = sprintf(
            "UPDATE %sfaqinstances SET instance = '%s', comment = '%s' WHERE id = %d",
            PMF_Db::getTablePrefix(),
            $data['instance'],
            $data['comment'],
            (int) $id
        );

        return $this->config->getDb()->query($update);
    }

    /**
     * Deletes an instance.
     *
     * @param int $id
     *
     * @return bool
     */
    public function removeInstance($id)
    {
        $deletes = array(
            sprintf(
                'DELETE FROM %sfaqinstances WHERE id = %d',
                PMF_Db::getTablePrefix(),
                (int) $id
            ),
            sprintf(
                'DELETE FROM %sfaqinstances_config WHERE instance_id = %d',
                PMF_Db::getTablePrefix(),
                (int) $id
            ),
        );

        foreach ($deletes as $delete) {
            $success = $this->config->getDb()->query($delete);
            if (!$success) {
                return false;
            }
        }

        return true;
    }

    /**
     * Adds a configuration item for the database.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return bool
     */
    public function addConfig($name, $value)
    {
        $insert = sprintf(
            "INSERT INTO
                %sfaqinstances_config
            VALUES
                (%d, '%s', '%s')",
            PMF_Db::getTablePrefix(),
            $this->getId(),
            $this->config->getDb()->escape(trim($name)),
            $this->config->getDb()->escape(trim($value))
        );

        return $this->config->getDb()->query($insert);
    }

    /**
     * Returns the configuration value.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getConfig($name)
    {
        if (!isset($this->instanceConfig[$name])) {
            $this->getInstanceConfig($this->getId());
        }

        switch ($this->instanceConfig[$name]) {
            case 'true':
                return true;
                break;
            case 'false':
                return false;
                break;
            default:
                return $this->instanceConfig[$name];
                break;
        }
    }
}
