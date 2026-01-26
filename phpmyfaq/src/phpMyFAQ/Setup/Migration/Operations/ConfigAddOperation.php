<?php

/**
 * Configuration add operation for migrations.
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

readonly class ConfigAddOperation implements OperationInterface
{
    /**
     * Create a new ConfigAddOperation for adding a configuration entry.
     *
     * @param string $key The configuration key to add.
     * @param mixed  $value The value to assign to the configuration key.
     */
    public function __construct(
        private Configuration $configuration,
        private string $key,
        private mixed $value,
    ) {
    }

    /**
     * Get the operation type identifier.
     *
     * @return string The operation type identifier 'config_add'.
     */
    public function getType(): string
    {
        return 'config_add';
    }

    /**
     * Build a human-readable description of the configuration addition.
     *
     * @return string The formatted description in the form "Add configuration: {key} = {formatted_value}".
     */
    public function getDescription(): string
    {
        $displayValue = $this->formatValue($this->value);
        return sprintf('Add configuration: %s = %s', $this->key, $displayValue);
    }

    /**
     * Get the configuration key targeted by this operation.
     *
     * @return string The configuration key being added.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Retrieve the value associated with the configuration key.
     *
     * @return mixed The configuration value stored for the key.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Apply the configuration-add operation to the injected Configuration.
     *
     * Adds the configured key and value to the configuration store.
     *
     * @return bool `true` if the configuration was added; currently always `true`.
     */
    public function execute(): bool
    {
        $this->configuration->add($this->key, $this->value);
        return true;
    }

    /**
     * Get a serializable associative array representing this operation.
     *
     * @return array{type:string,description:string,key:string,value:mixed} Associative array with keys:
     *               - `type`: operation type identifier,
     *               - `description`: human-readable description,
     *               - `key`: configuration key to add,
     *               - `value`: configuration value to set.
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
     * Format a value into a human-readable string for operation descriptions.
     *
     * @param mixed $value The value to format.
     * @return string A string representation suitable for descriptions:
     *                `true`/`false` for booleans, single-quoted strings (truncated to 47 characters plus "..." if longer than 50),
     *                `null` for null, or the result of casting to string for other types.
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