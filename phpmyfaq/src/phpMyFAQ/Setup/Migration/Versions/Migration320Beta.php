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
    public function getVersion(): string
    {
        return '3.2.0-beta';
    }

    public function getDependencies(): array
    {
        return ['3.2.0-alpha'];
    }

    public function getDescription(): string
    {
        return 'SMTP TLS config, remove link verification, config value as TEXT column';
    }

    public function up(OperationRecorder $recorder): void
    {
        $recorder->addConfig('mail.remoteSMTPDisableTLSPeerVerification', false);
        $recorder->deleteConfig('main.enableLinkVerification');

        // Delete link verification columns - use portable syntax
        if ($this->isSqlite()) {
            // SQLite requires table rebuild for dropping columns
            $this->rebuildTableWithoutColumns($recorder, 'faqdata');
            $this->rebuildTableWithoutColumns($recorder, 'faqdata_revisions');
        } else {
            // MySQL, PostgreSQL, SQL Server - use separate DROP COLUMN statements
            $recorder->addSql(
                sprintf('ALTER TABLE %sfaqdata DROP COLUMN links_state', $this->tablePrefix),
                'Remove links_state column from faqdata',
            );
            $recorder->addSql(
                sprintf('ALTER TABLE %sfaqdata DROP COLUMN links_check_date', $this->tablePrefix),
                'Remove links_check_date column from faqdata',
            );

            $recorder->addSql(
                sprintf('ALTER TABLE %sfaqdata_revisions DROP COLUMN links_state', $this->tablePrefix),
                'Remove links_state column from faqdata_revisions',
            );
            $recorder->addSql(
                sprintf('ALTER TABLE %sfaqdata_revisions DROP COLUMN links_check_date', $this->tablePrefix),
                'Remove links_check_date column from faqdata_revisions',
            );
        }

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
                sprintf('ALTER TABLE %sfaqconfig ALTER COLUMN config_value NVARCHAR(MAX)', $this->tablePrefix),
                'Change faqconfig.config_value to TEXT (SQL Server)',
            );
        }
    }

    /**
     * Rebuilds a table without specified columns (for SQLite).
     *
     * Note: This method uses hardcoded schema definitions for faqdata and faqdata_revisions
     * tables due to SQLite limitations with ALTER TABLE DROP COLUMN.
     */
    private function rebuildTableWithoutColumns(OperationRecorder $recorder, string $tableName): void
    {
        $fullTableName = $this->tablePrefix . $tableName;

        // For faqdata and faqdata_revisions, we need to define the schema without the removed columns
        if ($tableName === 'faqdata') {
            $recorder->addSql(
                sprintf('CREATE TABLE %s_new (
                        id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                        lang VARCHAR(5) NOT NULL,
                        solution_id INTEGER NOT NULL,
                        revision_id INTEGER NOT NULL DEFAULT 0,
                        active VARCHAR(3),
                        sticky INTEGER NOT NULL,
                        keywords TEXT,
                        thema TEXT NOT NULL,
                        content TEXT,
                        author VARCHAR(255) NOT NULL,
                        email VARCHAR(255) NOT NULL,
                        comment VARCHAR(3),
                        date VARCHAR(15) NOT NULL,
                        dateStart VARCHAR(14) NOT NULL DEFAULT \'00000000000000\',
                        dateEnd VARCHAR(14) NOT NULL DEFAULT \'99991231235959\',
                        created DATETIME NOT NULL
                    )', $fullTableName),
                sprintf('Create new %s table without link verification columns (SQLite)', $tableName),
            );

            $recorder->addSql(
                sprintf('INSERT INTO %s_new 
                     SELECT id, lang, solution_id, revision_id, active, sticky, keywords, thema, content, 
                            author, email, comment, date, dateStart, dateEnd, created 
                     FROM %s', $fullTableName, $fullTableName),
                sprintf('Copy data to new %s table (SQLite)', $tableName),
            );
        } elseif ($tableName === 'faqdata_revisions') {
            $recorder->addSql(
                sprintf('CREATE TABLE %s_new (
                        id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                        faq_id INTEGER NOT NULL,
                        lang VARCHAR(5) NOT NULL,
                        solution_id INTEGER NOT NULL,
                        revision_id INTEGER NOT NULL DEFAULT 0,
                        active VARCHAR(3),
                        sticky INTEGER NOT NULL,
                        keywords TEXT,
                        thema TEXT NOT NULL,
                        content TEXT,
                        author VARCHAR(255) NOT NULL,
                        email VARCHAR(255) NOT NULL,
                        comment VARCHAR(3),
                        date VARCHAR(15) NOT NULL,
                        dateStart VARCHAR(14) NOT NULL DEFAULT \'00000000000000\',
                        dateEnd VARCHAR(14) NOT NULL DEFAULT \'99991231235959\',
                        created DATETIME NOT NULL
                    )', $fullTableName),
                sprintf('Create new %s table without link verification columns (SQLite)', $tableName),
            );

            $recorder->addSql(
                sprintf('INSERT INTO %s_new 
                     SELECT id, faq_id, lang, solution_id, revision_id, active, sticky, keywords, thema, content, 
                            author, email, comment, date, dateStart, dateEnd, created 
                     FROM %s', $fullTableName, $fullTableName),
                sprintf('Copy data to new %s table (SQLite)', $tableName),
            );
        }

        $recorder->addSql(sprintf('DROP TABLE %s', $fullTableName), sprintf('Drop old %s table (SQLite)', $tableName));

        $recorder->addSql(
            sprintf('ALTER TABLE %s_new RENAME TO %s', $fullTableName, $fullTableName),
            sprintf('Rename new %s table (SQLite)', $tableName),
        );
    }
}
