<?php

/**
 * Configuration rename operation for migrations.
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

readonly class ConfigRenameOperation implements OperationInterface
{
    /**
     * Create a config rename operation that will rename a configuration entry from one key to another.
     *
     * @param Configuration $configuration The configuration service used to perform the rename.
     * @param string $oldKey The existing configuration key to rename.
     * @param string $newKey The target configuration key name.
     */
    public function __construct(
        private Configuration $configuration,
        private string $oldKey,
        private string $newKey,
    ) {
    }

    /**
     * Get the operation type identifier.
     *
     * @return string The operation type identifier 'config_rename'.
     */
    public function getType(): string
    {
        return 'config_rename';
    }

    /**
     * Provides a human-readable description of the rename operation.
     *
     * @return string A description in the format "Rename configuration: {oldKey} -> {newKey}".
     */
    public function getDescription(): string
    {
        return sprintf('Rename configuration: %s -> %s', $this->oldKey, $this->newKey);
    }

    /**
     * Original configuration key to be renamed.
     *
     * @return string The stored old configuration key name.
     */
    public function getOldKey(): string
    {
        return $this->oldKey;
    }

    /**
     * Returns the target configuration key name for the rename operation.
     *
     * @return string The new configuration key.
     */
    public function getNewKey(): string
    {
        return $this->newKey;
    }

    /**
     * Execute the operation by renaming the stored configuration key from the old key to the new key.
     *
     * @return bool `true` if the operation completed (this method always returns `true`).
     */
    public function execute(): bool
    {
        $this->configuration->rename($this->oldKey, $this->newKey);
        return true;
    }

    /**
     * Serialize the operation to an associative array for migration reporting.
     *
     * @return array{
     *     type: string,
     *     description: string,
     *     oldKey: string,
     *     newKey: string
     * } Array containing operation metadata and the source and destination configuration keys.
     */
    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'description' => $this->getDescription(),
            'oldKey' => $this->oldKey,
            'newKey' => $this->newKey,
        ];
    }
}