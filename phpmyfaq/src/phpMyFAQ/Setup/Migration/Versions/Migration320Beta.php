<?php

/**
 * Migration for phpMyFAQ 3.2.0-beta.
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

readonly class Migration320Beta extends AbstractMigration
{
    /**
     * Retrieve the migration version identifier.
     *
     * @return string The migration version "3.2.0-beta".
     */
    public function getVersion(): string
    {
        return '3.2.0-beta';
    }

    /**
     * Migration versions required before applying this migration.
     *
     * @return string[] Array of migration version identifiers required as dependencies.
     */
    public function getDependencies(): array
    {
        return ['3.2.0-alpha'];
    }

    /**
     * Human-readable summary of the migration's purpose and changes.
     *
     * @return string A short description of the migration's intent and applied changes.
     */
    public function getDescription(): string
    {
        return 'SMTP TLS config, remove link verification, config value as TEXT column';
    }

    /**
     * Apply migration changes for version 3.2.0-beta.
     *
     * Adds the mail.remoteSMTPDisableTLSPeerVerification config, removes main.enableLinkVerification,
     * drops link verification columns from faqdata and faqdata_revisions, and converts
     * faqconfig.config_value to a TEXT column using database-specific SQL.
     *
     * @param OperationRecorder $recorder Recorder used to record configuration changes and SQL statements.
     */
    public function up(OperationRecorder $recorder): void
    {
        $recorder->addConfig('mail.remoteSMTPDisableTLSPeerVerification', false);
        $recorder->deleteConfig('main.enableLinkVerification');

        // Delete link verification columns
        $recorder->addSql(
            sprintf('ALTER TABLE %sfaqdata DROP COLUMN links_state, DROP COLUMN links_check_date', $this->tablePrefix),
            'Remove link verification columns from faqdata',
        );

        $recorder->addSql(
            sprintf(
                'ALTER TABLE %sfaqdata_revisions DROP COLUMN links_state, DROP COLUMN links_check_date',
                $this->tablePrefix,
            ),
            'Remove link verification columns from faqdata_revisions',
        );

        // Configuration values in a TEXT column
        if ($this->isMySql()) {
            $recorder->addSql(
                sprintf('ALTER TABLE %sfaqconfig MODIFY config_value TEXT DEFAULT NULL', $this->tablePrefix),
                'Change faqconfig.config_value to TEXT (MySQL)',
            );
        } elseif ($this->isPostgreSql()) {
            $recorder->addSql(
                sprintf('ALTER TABLE %sfaqconfig ALTER COLUMN config_value TYPE TEXT', $this->tablePrefix),
                'Change faqconfig.config_value to TEXT (PostgreSQL)',
            );
        } elseif ($this->isSqlite()) {
            // SQLite requires table rebuild
            $recorder->addSql(sprintf('CREATE TABLE %sfaqconfig_new (
                    config_name VARCHAR(255) NOT NULL default \'\',
                    config_value TEXT DEFAULT NULL, PRIMARY KEY (config_name)
                 )', $this->tablePrefix), 'Create new faqconfig table (SQLite)');

            $recorder->addSql(
                sprintf(
                    'INSERT INTO %sfaqconfig_new SELECT config_name, config_value FROM %sfaqconfig',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Copy data to new faqconfig table (SQLite)',
            );

            $recorder->addSql(
                sprintf('DROP TABLE %sfaqconfig', $this->tablePrefix),
                'Drop old faqconfig table (SQLite)',
            );

            $recorder->addSql(
                sprintf('ALTER TABLE %sfaqconfig_new RENAME TO %sfaqconfig', $this->tablePrefix, $this->tablePrefix),
                'Rename new faqconfig table (SQLite)',
            );
        } elseif ($this->isSqlServer()) {
            $recorder->addSql(
                sprintf('ALTER TABLE %sfaqconfig ALTER COLUMN config_value TEXT', $this->tablePrefix),
                'Change faqconfig.config_value to TEXT (SQL Server)',
            );
        }
    }
}