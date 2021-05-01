<?php

/**
 * The main phpMyFAQ instances class.
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
 * @since     2012-02-20
 */

namespace phpMyFAQ;

/**
 * Class Instance
 *
 * @package phpMyFAQ
 */
class Instance
{
    /**
     * Configuration.
     *
     * @var Configuration
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
     * @var string[]
     */
    protected $instanceConfig = [];

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Adds a new instance.
     *
     * @param string[] $data
     *
     * @return int $id
     */
    public function addInstance(array $data): int
    {
        $this->setId($this->config->getDb()->nextId(Database::getTablePrefix() . 'faqinstances', 'id'));

        $insert = sprintf(
            "INSERT INTO %sfaqinstances VALUES (%d, '%s', '%s', '%s', %s, %s)",
            Database::getTablePrefix(),
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
     * Returns the current instance id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Sets the instance ID.
     *
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = (int)$id;
    }

    /**
     * Returns all instances.
     *
     * @return string[]
     */
    public function getAllInstances(): array
    {
        $select = sprintf(
            'SELECT * FROM %sfaqinstances ORDER BY id',
            Database::getTablePrefix()
        );

        $result = $this->config->getDb()->query($select);

        return $this->config->getDb()->fetchAll($result);
    }

    /**
     * Returns the instance.
     *
     * @param int $id
     *
     * @return object
     */
    public function getInstanceById(int $id): object
    {
        $select = sprintf(
            'SELECT * FROM %sfaqinstances WHERE id = %d',
            Database::getTablePrefix(),
            $id
        );

        $result = $this->config->getDb()->query($select);

        return $this->config->getDb()->fetchObject($result);
    }

    /**
     * Updates the instance data.
     *
     * @param int   $id
     * @param string[] $data
     *
     * @return bool
     */
    public function updateInstance(int $id, array $data): bool
    {
        $update = sprintf(
            "UPDATE %sfaqinstances SET instance = '%s', comment = '%s', url = '%s' WHERE id = %d",
            Database::getTablePrefix(),
            $data['instance'],
            $data['comment'],
            $data['url'],
            (int)$id
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
    public function removeInstance(int $id): bool
    {
        $deletes = [
            sprintf(
                'DELETE FROM %sfaqinstances WHERE id = %d',
                Database::getTablePrefix(),
                $id
            ),
            sprintf(
                'DELETE FROM %sfaqinstances_config WHERE instance_id = %d',
                Database::getTablePrefix(),
                $id
            ),
        ];

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
     * @param string $value
     *
     * @return mixed
     */
    public function addConfig(string $name, string $value)
    {
        $insert = sprintf(
            "INSERT INTO
                %sfaqinstances_config
            VALUES
                (%d, '%s', '%s')",
            Database::getTablePrefix(),
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
     * @return bool|string
     */
    public function getConfig(string $name)
    {
        if (!isset($this->instanceConfig[$name])) {
            $this->getInstanceConfig($this->getId());
        }

        switch ($this->instanceConfig[$name]) {
            case 'true':
                return true;
            case 'false':
                return false;
            default:
                return $this->instanceConfig[$name];
        }
    }

    /**
     * Returns the configuration of the given instance ID.
     *
     * @param int $id
     *
     * @return string[]
     */
    public function getInstanceConfig(int $id): array
    {
        $query = sprintf(
            '
            SELECT
                config_name, config_value
            FROM
                %sfaqinstances_config
            WHERE
                instance_id = %d',
            Database::getTablePrefix(),
            $id
        );

        $result = $this->config->getDb()->query($query);
        $config = $this->config->getDb()->fetchAll($result);

        foreach ($config as $items) {
            $this->instanceConfig[$items->config_name] = $items->config_value;
        }

        return $this->instanceConfig;
    }
}
