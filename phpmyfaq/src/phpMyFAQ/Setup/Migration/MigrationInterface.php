<?php

/**
 * Contract for all database migrations.
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

namespace phpMyFAQ\Setup\Migration;

use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;

interface MigrationInterface
{
    /**
     * Returns the version string this migration upgrades to.
     * Format: semantic version (e.g., "3.2.0-alpha", "4.0.0-alpha.2")
     */
    public function getVersion(): string;

    /**
     * Returns an array of version strings that must be applied before this migration.
     *
     * @return string[]
     */
    public function getDependencies(): array;

    /**
     * Returns a human-readable description of what this migration does.
     */
    public function getDescription(): string;

    /**
     * Applies the migration, recording all operations to the recorder.
     * Operations are NOT executed immediately - they are collected for review or execution.
     */
    public function up(OperationRecorder $recorder): void;

    /**
     * Reverses the migration, recording all rollback operations to the recorder.
     * Operations are NOT executed immediately - they are collected for review or execution.
     */
    public function down(OperationRecorder $recorder): void;

    /**
     * Returns true if this migration can be safely reversed.
     */
    public function isReversible(): bool;
}
