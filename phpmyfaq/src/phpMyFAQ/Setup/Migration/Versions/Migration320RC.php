<?php

/**
 * Migration for phpMyFAQ 3.2.0-RC.
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

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Setup\Migration\AbstractMigration;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;

readonly class Migration320RC extends AbstractMigration
{
    /**
     * Provide the migration version identifier.
     *
     * @return string The migration version string "3.2.0-RC".
     */
    public function getVersion(): string
    {
        return '3.2.0-RC';
    }

    /**
     * List of migration versions required to run before this migration.
     *
     * @return string[] An array of version identifiers this migration depends on.
     */
    public function getDependencies(): array
    {
        return ['3.2.0-beta.2'];
    }

    /**
     * Provide a short human-readable description of this migration.
     *
     * @return string The migration description: 'Add mail address in export config option'.
     */
    public function getDescription(): string
    {
        return 'Add mail address in export config option';
    }

    /**
     * Record adding the `spam.mailAddressInExport` configuration option and enable it.
     *
     * Uses the provided operation recorder to register the config key `spam.mailAddressInExport` with value `true`.
     *
     * @param OperationRecorder $recorder Recorder used to record migration operations.
     */
    public function up(OperationRecorder $recorder): void
    {
        $recorder->addConfig('spam.mailAddressInExport', true);
    }
}