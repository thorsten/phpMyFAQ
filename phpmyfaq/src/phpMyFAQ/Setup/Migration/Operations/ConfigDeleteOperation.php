<?php

/**
 * Configuration delete operation for migrations.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-25
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Operations;

use phpMyFAQ\Configuration;

readonly class ConfigDeleteOperation implements OperationInterface
{
    /**
     * Create a ConfigDeleteOperation that will delete the given configuration key.
     *
     * @param Configuration $configuration The Configuration instance used to perform the deletion.
     * @param string        $key           The configuration key to delete.
     */
    public function __construct(
        private Configuration $configuration,
        private string $key,
    ) {
    }

    /**
     * Operation type identifier for this operation.
     *
     * @return string The operation type identifier: 'config_delete'.
     */
    public function getType(): string
    {
        return 'config_delete';
    }

    /**
     * Provide a human-readable description of the configuration deletion operation.
     *
     * @return string A string describing the configuration key to delete.
     */
    public function getDescription(): string
    {
        return sprintf('Delete configuration: %s', $this->key);
    }

    /**
     * Retrieve the configuration key targeted by this operation.
     *
     * @return string The configuration key targeted for deletion.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Delete the configuration key represented by this operation.
     *
     * @return bool true after the delete call is invoked.
     */
    public function execute(): bool
    {
        $this->configuration->delete($this->key);
        return true;
    }

    /**
     * Serialize the operation into an associative array for storage or transport.
     *
     * The returned array contains the operation `type`, a human-readable `description`,
     * and the configuration `key` targeted by the operation.
     *
     * @return array{type:string,description:string,key:string} The serialized representation of the operation.
     */
    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'description' => $this->getDescription(),
            'key' => $this->key,
        ];
    }
}