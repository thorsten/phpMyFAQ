<?php

declare(strict_types=1);

/**
 * The main phpMyFAQ instances class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2025 phpMyFAQ Team
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
    public function __construct(
        protected Configuration $configuration,
    ) {
    }

    /**
     * Adds a new instance.
     *
     * @return int $id
     */
    public function create(InstanceEntity $instanceEntity): int
    {
        $this->setId($this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqinstances', 'id'));

        $insert = sprintf(
            "INSERT INTO %sfaqinstances VALUES (%d, '%s', '%s', '%s', %s, %s)",
            Database::getTablePrefix(),
            $this->getId(),
            $this->configuration->getDb()->escape($instanceEntity->getUrl()),
            $this->configuration->getDb()->escape($instanceEntity->getInstance()),
            $this->configuration->getDb()->escape($instanceEntity->getComment()),
            $this->configuration->getDb()->now(),
            $this->configuration->getDb()->now(),
        );

        if (!$this->configuration->getDb()->query($insert)) {
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
    public function getAll(): array
    {
        $select = sprintf('SELECT * FROM %sfaqinstances ORDER BY id', Database::getTablePrefix());

        $result = $this->configuration->getDb()->query($select);

        return $this->configuration->getDb()->fetchAll($result);
    }

    /**
     * Returns the instance.
     */
    public function getById(int $id): object
    {
        $select = sprintf('SELECT * FROM %sfaqinstances WHERE id = %d', Database::getTablePrefix(), $id);

        $result = $this->configuration->getDb()->query($select);

        return $this->configuration->getDb()->fetchObject($result);
    }

    /**
     * Updates the instance data.
     */
    public function update(int $id, InstanceEntity $instanceEntity): bool
    {
        $update = sprintf(
            "UPDATE %sfaqinstances SET instance = '%s', comment = '%s', url = '%s' WHERE id = %d",
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($instanceEntity->getInstance()),
            $this->configuration->getDb()->escape($instanceEntity->getComment()),
            $this->configuration->getDb()->escape($instanceEntity->getUrl()),
            $id,
        );

        return (bool) $this->configuration->getDb()->query($update);
    }

    /**
     * Deletes an instance.
     *
     */
    public function delete(int $id): bool
    {
        $deletes = [
            sprintf('DELETE FROM %sfaqinstances WHERE id = %d', Database::getTablePrefix(), $id),
            sprintf('DELETE FROM %sfaqinstances_config WHERE instance_id = %d', Database::getTablePrefix(), $id),
        ];

        foreach ($deletes as $delete) {
            $success = $this->configuration->getDb()->query($delete);
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
            $this->configuration->getDb()->escape(trim($name)),
            $this->configuration->getDb()->escape(trim($value)),
        );

        return (bool) $this->configuration->getDb()->query($insert);
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
        $query = sprintf('
            SELECT
                config_name, config_value
            FROM
                %sfaqinstances_config
            WHERE
                instance_id = %d', Database::getTablePrefix(), $instanceId);

        $result = $this->configuration->getDb()->query($query);
        $config = $this->configuration->getDb()->fetchAll($result);

        foreach ($config as $items) {
            $this->instanceConfig[$items->config_name] = $items->config_value;
        }

        return $this->instanceConfig;
    }
}
