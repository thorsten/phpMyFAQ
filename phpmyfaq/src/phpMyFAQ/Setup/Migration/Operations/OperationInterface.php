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
 * Get the operation type.
 *
 * @return string The operation type (e.g., 'sql', 'config_add', 'file_copy').
 */
    public function getType(): string;

    /**
 * Get a human-readable description of the operation.
 *
 * @return string A concise human-readable description of the operation.
 */
    public function getDescription(): string;

    /**
     * Executes the operation.
     *
     * @return bool True on success, false on failure
     */
    public function execute(): bool;

    /**
 * Provide an associative array representation of the operation suitable for dry-run output.
 *
 * @return array<string, mixed> Associative array describing the operation (string keys to mixed values).
 */
    public function toArray(): array;
}