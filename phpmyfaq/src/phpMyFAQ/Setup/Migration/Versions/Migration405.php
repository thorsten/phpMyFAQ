<?php

/**
 * Migration for phpMyFAQ 4.0.5.
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

readonly class Migration405 extends AbstractMigration
{
    /**
     * Gets the migration target version.
     *
     * @return string The migration version identifier (e.g., "4.0.5").
     */
    public function getVersion(): string
    {
        return '4.0.5';
    }

    /**
     * Migration versions that must be applied before this migration.
     *
     * @return string[] Required migration version strings.
     */
    public function getDependencies(): array
    {
        return ['4.0.0-beta.2'];
    }

    /**
     * Short human-readable description of the migration: remove old section permissions and increase the faqforms.input_label column size.
     *
     * @return string A brief description of the migration. 
     */
    public function getDescription(): string
    {
        return 'Remove old section permissions, increase forms input_label column';
    }

    /**
     * Remove legacy section permissions and increase the size of the faqforms.input_label column.
     *
     * Executes SQL statements to delete the old permissions (view_sections, add_section, edit_section,
     * delete_section) from the faqright table and updates the faqforms.input_label column to support
     * up to 500 characters using database-specific operations (ALTER for MySQL/PostgreSQL/SQL Server,
     * table rebuild for SQLite).
     *
     * @param OperationRecorder $recorder Recorder used to collect and execute migration SQL operations.
     */
    public function up(OperationRecorder $recorder): void
    {
        // Delete old permissions
        $recorder->addSql(
            sprintf("DELETE FROM %sfaqright WHERE name = 'view_sections'", $this->tablePrefix),
            'Delete view_sections permission',
        );

        $recorder->addSql(
            sprintf("DELETE FROM %sfaqright WHERE name = 'add_section'", $this->tablePrefix),
            'Delete add_section permission',
        );

        $recorder->addSql(
            sprintf("DELETE FROM %sfaqright WHERE name = 'edit_section'", $this->tablePrefix),
            'Delete edit_section permission',
        );

        $recorder->addSql(
            sprintf("DELETE FROM %sfaqright WHERE name = 'delete_section'", $this->tablePrefix),
            'Delete delete_section permission',
        );

        // Update faqforms table - increase input_label size
        if ($this->isMySql()) {
            $recorder->addSql(
                sprintf(
                    'ALTER TABLE %sfaqforms CHANGE input_label input_label VARCHAR(500) NOT NULL',
                    $this->tablePrefix,
                ),
                'Increase faqforms.input_label column size (MySQL)',
            );
        } elseif ($this->isPostgreSql()) {
            $recorder->addSql(
                sprintf('ALTER TABLE %sfaqforms ALTER COLUMN input_label SET TYPE VARCHAR(500)', $this->tablePrefix),
                'Increase faqforms.input_label column size (PostgreSQL part 1)',
            );

            $recorder->addSql(
                sprintf('ALTER TABLE %sfaqforms ALTER COLUMN input_label SET NOT NULL', $this->tablePrefix),
                'Increase faqforms.input_label column size (PostgreSQL part 2)',
            );
        } elseif ($this->isSqlite()) {
            // SQLite requires table rebuild
            $recorder->addSql(
                sprintf('ALTER TABLE %sfaqforms RENAME TO %sfaqforms_old', $this->tablePrefix, $this->tablePrefix),
                'Rename faqforms table (SQLite)',
            );

            $recorder->addSql(sprintf('CREATE TABLE %sfaqforms (
                    form_id INTEGER NOT NULL,
                    input_id INTEGER NOT NULL,
                    input_type VARCHAR(1000) NOT NULL,
                    input_label VARCHAR(500) NOT NULL,
                    input_active INTEGER NOT NULL,
                    input_required INTEGER NOT NULL,
                    input_lang VARCHAR(11) NOT NULL
                )', $this->tablePrefix), 'Create new faqforms table (SQLite)');

            $recorder->addSql(
                sprintf('INSERT INTO %sfaqforms
                    SELECT
                        form_id, input_id, input_type, input_label, input_active, input_required, input_lang
                    FROM %sfaqforms_old', $this->tablePrefix, $this->tablePrefix),
                'Copy data to new faqforms table (SQLite)',
            );

            $recorder->addSql(
                sprintf('DROP TABLE %sfaqforms_old', $this->tablePrefix),
                'Drop old faqforms table (SQLite)',
            );
        } elseif ($this->isSqlServer()) {
            $recorder->addSql(
                sprintf('ALTER TABLE %sfaqforms ALTER COLUMN input_label NVARCHAR(500) NOT NULL', $this->tablePrefix),
                'Increase faqforms.input_label column size (SQL Server)',
            );
        }
    }
}