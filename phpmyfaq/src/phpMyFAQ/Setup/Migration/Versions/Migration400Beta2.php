<?php

/**
 * Migration for phpMyFAQ 4.0.0-beta.2.
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

readonly class Migration400Beta2 extends AbstractMigration
{
    /**
     * Provide the migration version identifier for this migration.
     *
     * @return string The migration version identifier, '4.0.0-beta.2'.
     */
    public function getVersion(): string
    {
        return '4.0.0-beta.2';
    }

    /**
     * List migration versions that must be applied before this migration.
     *
     * @return string[] An array of migration version identifiers required as dependencies.
     */
    public function getDependencies(): array
    {
        return ['4.0.0-alpha.3'];
    }

    /**
     * Provide a short human-readable description of this migration.
     *
     * @return string Short description of the migration.
     */
    public function getDescription(): string
    {
        return 'WebAuthn support';
    }

    /**
     * Apply the migration: enable WebAuthn support configuration and add the `webauthnkeys` column to the `faquser` table.
     *
     * Registers the configuration key `security.enableWebAuthnSupport` with a default of `false` and records the SQL
     * to add a nullable TEXT `webauthnkeys` column to the `faquser` table (uses a SQLite-specific SQL/message when applicable).
     *
     * @param OperationRecorder $recorder The recorder used to register configuration and SQL operations for this migration.
     */
    public function up(OperationRecorder $recorder): void
    {
        // WebAuthn support
        $recorder->addConfig('security.enableWebAuthnSupport', false);

        if ($this->isSqlite()) {
            $recorder->addSql(
                sprintf('ALTER TABLE %sfaquser ADD COLUMN webauthnkeys TEXT NULL DEFAULT NULL', $this->tablePrefix),
                'Add WebAuthn keys column to faquser (SQLite)',
            );
        } else {
            $recorder->addSql(
                sprintf('ALTER TABLE %sfaquser ADD webauthnkeys TEXT NULL DEFAULT NULL', $this->tablePrefix),
                'Add WebAuthn keys column to faquser',
            );
        }
    }
}