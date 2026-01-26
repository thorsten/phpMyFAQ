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
 * The target semantic version this migration upgrades to.
 *
 * The version must follow semantic versioning and may include pre-release or build metadata
 * (for example: "3.2.0-alpha", "4.0.0-alpha.2").
 *
 * @return string The target semantic version string.
 */
    public function getVersion(): string;

    /**
 * List version strings this migration depends on.
 *
 * @return string[] An array of version strings that must be applied before this migration.
 */
    public function getDependencies(): array;

    /**
 * Provide a human-readable description of the migration's purpose or effects.
 *
 * @return string A short, human-readable description of what the migration does.
 */
    public function getDescription(): string;

    /**
 * Record the operations required to apply this migration into the provided recorder.
 *
 * The recorder collects operations; they are not executed by this method and may be reviewed or applied later.
 *
 * @param OperationRecorder $recorder The recorder that will collect migration operations.
 */
    public function up(OperationRecorder $recorder): void;

    /**
 * Perform the reverse migration by recording rollback operations to the provided recorder.
 *
 * The recorder collects operations for review or later execution; operations are not executed immediately.
 *
 * @param OperationRecorder $recorder Recorder that will collect the rollback operations.
 */
    public function down(OperationRecorder $recorder): void;

    /**
 * Indicates whether the migration can be safely reversed.
 *
 * @return bool `true` if the migration can be reversed, `false` otherwise.
 */
    public function isReversible(): bool;
}