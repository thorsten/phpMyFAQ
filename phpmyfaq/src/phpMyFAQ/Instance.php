<?php

/**
 * The main phpMyFAQ instances class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-02-20
 */

namespace phpMyFAQ;

use phpMyFAQ\Entity\InstanceEntity;
use stdClass;

/**
 * Class Instance
 *
 * @package phpMyFAQ
 */
class Instance
{
    /**
     * Instance ID.
     */
    protected int $id;

    /**
     * Instance configuration.
     *
     * @var string[]
     */
    protected array $instanceConfig = [];

    /**
     * Constructor.
     */
    public function __construct(protected Configuration $config)
    {
    }

    /**
     * Adds a new instance.
     *
     * @return int $id
     */
    public function addInstance(InstanceEntity $data): int
    {
        $this->setId($this->config->getDb()->nextId(Database::getTablePrefix() . 'faqinstances', 'id'));

        $insert = sprintf(
            "INSERT INTO %sfaqinstances VALUES (%d, '%s', '%s', '%s', %s, %s)",
            Database::getTablePrefix(),
            $this->getId(),
            $this->config->getDb()->escape($data->getUrl()),
            $this->config->getDb()->escape($data->getInstance()),
            $this->config->getDb()->escape($data->getComment()),
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
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Sets the instance ID.
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Returns all instances.
     *
     * @return stdClass[]
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
     */
    public function updateInstance(int $id, InstanceEntity $data): bool
    {
        $update = sprintf(
            "UPDATE %sfaqinstances SET instance = '%s', comment = '%s', url = '%s' WHERE id = %d",
            Database::getTablePrefix(),
            $this->config->getDb()->escape($data->getInstance()),
            $this->config->getDb()->escape($data->getComment()),
            $this->config->getDb()->escape($data->getUrl()),
            $id
        );

        return $this->config->getDb()->query($update);
    }

    /**
     * Deletes an instance.
     *
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
     */
    public function addConfig(string $name, string $value): mixed
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
     */
    public function getConfig(string $name): bool|string
    {
        if (!isset($this->instanceConfig[$name])) {
            $this->getInstanceConfig($this->getId());
        }

        return match ($this->instanceConfig[$name]) {
            'true' => true,
            'false' => false,
            default => $this->instanceConfig[$name],
        };
    }

    /**
     * Returns the configuration of the given instance ID.
     *
     * @return string[]
     */
    public function getInstanceConfig(int $instanceId): array
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
            $instanceId
        );

        $result = $this->config->getDb()->query($query);
        $config = $this->config->getDb()->fetchAll($result);

        foreach ($config as $items) {
            $this->instanceConfig[$items->config_name] = $items->config_value;
        }

        return $this->instanceConfig;
    }
}
