<?php

/**
 * Configuration update operation for migrations.
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

readonly class ConfigUpdateOperation implements OperationInterface
{
    /**
     * Create a new ConfigUpdateOperation.
     *
     * @param Configuration $configuration The configuration object to update.
     * @param string $key The configuration key to set.
     * @param mixed $value The new value for the configuration key.
     */
    public function __construct(
        private Configuration $configuration,
        private string $key,
        private mixed $value,
    ) {
    }

    /**
     * Identify the operation as a configuration update.
     *
     * @return string The operation type identifier 'config_update'.
     */
    public function getType(): string
    {
        return 'config_update';
    }

    /**
     * Produce a human-readable description of the configuration update.
     *
     * @return string A description in the form "Update configuration: {key} = {displayValue}" where `{displayValue}` is a formatted representation of the value.
     */
    public function getDescription(): string
    {
        $displayValue = $this->formatValue($this->value);
        return sprintf('Update configuration: %s = %s', $this->key, $displayValue);
    }

    /**
     * Get the configuration key targeted by this operation.
     *
     * @return string The configuration key.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Retrieve the configured value for this operation.
     *
     * @return mixed The value associated with the configuration key.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Apply the stored configuration change to the injected Configuration instance.
     *
     * @return bool `true` indicating the configuration update was invoked.
     */
    public function execute(): bool
    {
        $this->configuration->update([$this->key => $this->value]);
        return true;
    }

    /**
     * Build a serializable representation of this operation.
     *
     * The returned array contains:
     * - 'type': operation type string
     * - 'description': human-readable description
     * - 'key': configuration key being updated
     * - 'value': configured value
     *
     * @return array<string,mixed> Associative array with keys 'type', 'description', 'key', and 'value'.
     */
    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'description' => $this->getDescription(),
            'key' => $this->key,
            'value' => $this->value,
        ];
    }

    /**
     * Format a value for human-readable display in migration descriptions.
     *
     * Booleans become `true` or `false`. Strings are wrapped in single quotes and
     * truncated to 47 characters plus "..." if longer than 50 characters.
     * `null` becomes `null`. Other values are cast to string.
     *
     * @param mixed $value The value to format.
     * @return string The formatted string representation.
     */
    private function formatValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_string($value)) {
            if (strlen($value) > 50) {
                return "'" . substr($value, 0, 47) . "...'";
            }
            return "'{$value}'";
        }
        if (is_null($value)) {
            return 'null';
        }
        return (string) $value;
    }
}