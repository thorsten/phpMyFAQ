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
    public function __construct(
        private Configuration $configuration,
        private string $key,
        private mixed $value,
    ) {
    }

    public function getType(): string
    {
        return 'config_add';
    }

    public function getDescription(): string
    {
        $displayValue = $this->formatValue($this->value);
        return sprintf('Add configuration: %s = %s', $this->key, $displayValue);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function execute(): bool
    {
        return $this->configuration->add($this->key, $this->value);
    }

    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'description' => $this->getDescription(),
            'key' => $this->key,
            'value' => $this->value,
        ];
    }

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
        if (is_array($value)) {
            $json = json_encode($value);
            if ($json !== false && strlen($json) <= 50) {
                return "'" . $json . "'";
            }
            return "'[array]...'";
        }
        if (is_object($value)) {
            $json = json_encode($value);
            if ($json !== false && strlen($json) <= 50) {
                return "'" . $json . "'";
            }
            return "'[object " . get_class($value) . "]'";
        }
        return (string) $value;
    }
}
