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
    public function __construct(
        private Configuration $configuration,
        private string $oldKey,
        private string $newKey,
    ) {
    }

    public function getType(): string
    {
        return 'config_rename';
    }

    public function getDescription(): string
    {
        return sprintf('Rename configuration: %s -> %s', $this->oldKey, $this->newKey);
    }

    public function getOldKey(): string
    {
        return $this->oldKey;
    }

    public function getNewKey(): string
    {
        return $this->newKey;
    }

    public function execute(): bool
    {
        return $this->configuration->rename($this->oldKey, $this->newKey);
    }

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
