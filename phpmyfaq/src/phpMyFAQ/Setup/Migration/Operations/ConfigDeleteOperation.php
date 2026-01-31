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
    public function __construct(
        private Configuration $configuration,
        private string $key,
    ) {
    }

    public function getType(): string
    {
        return 'config_delete';
    }

    public function getDescription(): string
    {
        return sprintf('Delete configuration: %s', $this->key);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function execute(): bool
    {
        return $this->configuration->delete($this->key);
    }

    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'description' => $this->getDescription(),
            'key' => $this->key,
        ];
    }
}
