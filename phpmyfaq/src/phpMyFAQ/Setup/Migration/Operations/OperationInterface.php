<?php

/**
 * Contract for all recordable migration operations.
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

interface OperationInterface
{
    /**
     * Returns the type of operation (e.g., 'sql', 'config_add', 'file_copy').
     */
    public function getType(): string;

    /**
     * Returns a human-readable description of the operation.
     */
    public function getDescription(): string;

    /**
     * Executes the operation.
     *
     * @return bool True on success, false on failure
     */
    public function execute(): bool;

    /**
     * Returns an array representation of the operation for dry-run output.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
