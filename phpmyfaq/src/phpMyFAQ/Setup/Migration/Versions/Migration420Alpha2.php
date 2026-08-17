<?php

/**
 * Migration for phpMyFAQ 4.2.0-alpha.2.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-08-10
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Setup\Migration\AbstractMigration;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;

readonly class Migration420Alpha2 extends AbstractMigration
{
    public function getVersion(): string
    {
        return '4.2.0-alpha.2';
    }

    public function getDependencies(): array
    {
        return ['4.2.0-alpha'];
    }

    public function getDescription(): string
    {
        return (
            'Add faquser_right_language and faqgroup_right_language tables for granular '
            . 'language-based permissions, separate the FAQ read and publish rights, '
            . 'and add the faqquestion_history table for open question lifecycle metadata tracking'
            . ', and introduce the editorial workflow status on faqdata'
            . ', replace the active flag with it, and retire the unused approverec right'
            . ', and convert the lossy SQL Server character columns to Unicode types'
        );
    }

    public function up(OperationRecorder $recorder): void
    {
        $intType = $this->integerType();

        if ($this->isMySql()) {
            $recorder->addSql(
                sprintf(
                    'CREATE TABLE IF NOT EXISTS %sfaquser_right_language (
                        user_id %s NOT NULL,
                        right_id %s NOT NULL,
                        language VARCHAR(5) NOT NULL,
                        PRIMARY KEY (user_id, right_id, language)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
                    $this->tablePrefix,
                    $intType,
                    $intType,
                ),
                'Create faquser_right_language table (MySQL)',
            );

            $recorder->addSql(
                sprintf(
                    'CREATE TABLE IF NOT EXISTS %sfaqgroup_right_language (
                        group_id %s NOT NULL,
                        right_id %s NOT NULL,
                        language VARCHAR(5) NOT NULL,
                        PRIMARY KEY (group_id, right_id, language)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
                    $this->tablePrefix,
                    $intType,
                    $intType,
                ),
                'Create faqgroup_right_language table (MySQL)',
            );
        }

        if ($this->isPostgreSql()) {
            $recorder->addSql(
                sprintf('CREATE TABLE IF NOT EXISTS %sfaquser_right_language (
                        user_id %s NOT NULL,
                        right_id %s NOT NULL,
                        language VARCHAR(5) NOT NULL,
                        PRIMARY KEY (user_id, right_id, language)
                    )', $this->tablePrefix, $intType, $intType),
                'Create faquser_right_language table (PostgreSQL)',
            );

            $recorder->addSql(
                sprintf('CREATE TABLE IF NOT EXISTS %sfaqgroup_right_language (
                        group_id %s NOT NULL,
                        right_id %s NOT NULL,
                        language VARCHAR(5) NOT NULL,
                        PRIMARY KEY (group_id, right_id, language)
                    )', $this->tablePrefix, $intType, $intType),
                'Create faqgroup_right_language table (PostgreSQL)',
            );
        }

        if ($this->isSqlite()) {
            $recorder->addSql(
                sprintf('CREATE TABLE IF NOT EXISTS %sfaquser_right_language (
                        user_id %s NOT NULL,
                        right_id %s NOT NULL,
                        language VARCHAR(5) NOT NULL,
                        PRIMARY KEY (user_id, right_id, language)
                    )', $this->tablePrefix, $intType, $intType),
                'Create faquser_right_language table (SQLite)',
            );

            $recorder->addSql(
                sprintf('CREATE TABLE IF NOT EXISTS %sfaqgroup_right_language (
                        group_id %s NOT NULL,
                        right_id %s NOT NULL,
                        language VARCHAR(5) NOT NULL,
                        PRIMARY KEY (group_id, right_id, language)
                    )', $this->tablePrefix, $intType, $intType),
                'Create faqgroup_right_language table (SQLite)',
            );
        }

        if ($this->isSqlServer()) {
            $recorder->addSql(
                sprintf(
                    'IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = \'%sfaquser_right_language\') '
                    . 'CREATE TABLE %sfaquser_right_language (
                        user_id %s NOT NULL,
                        right_id %s NOT NULL,
                        language VARCHAR(5) NOT NULL,
                        PRIMARY KEY (user_id, right_id, language)
                    )',
                    $this->tablePrefix,
                    $this->tablePrefix,
                    $intType,
                    $intType,
                ),
                'Create faquser_right_language table (SQL Server)',
            );

            $recorder->addSql(
                sprintf(
                    'IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = \'%sfaqgroup_right_language\') '
                    . 'CREATE TABLE %sfaqgroup_right_language (
                        group_id %s NOT NULL,
                        right_id %s NOT NULL,
                        language VARCHAR(5) NOT NULL,
                        PRIMARY KEY (group_id, right_id, language)
                    )',
                    $this->tablePrefix,
                    $this->tablePrefix,
                    $intType,
                    $intType,
                ),
                'Create faqgroup_right_language table (SQL Server)',
            );
        }

        $this->separateReadAndPublishRights($recorder);
        $this->createQuestionHistoryTable($recorder);
        $this->introduceEditorialWorkflowStatus($recorder);
        $this->retireApproveRight($recorder);
        $this->convertSqlServerColumnsToUnicode($recorder);
    }

    /**
     * SQL Server typed several user-content columns as VARCHAR/TEXT, which silently mangles
     * characters outside the collation's code page (CJK, Thai, Cyrillic, ...) — see #1896.
     * Converts them to their NVARCHAR equivalents. ALTER COLUMN refuses to change the type of
     * a column that is used in an index (faqcustompages.slug) or bound to a default constraint
     * (seo_robots and the faquser token columns), so those are dropped first and restored
     * afterwards. Every statement is idempotent, keeping the amended-migration re-run safe.
     */
    private function convertSqlServerColumnsToUnicode(OperationRecorder $recorder): void
    {
        if (!$this->isSqlServer()) {
            return;
        }

        $unconstrainedColumns = [
            'faqcustompages' => [
                'page_title' => 'NVARCHAR(255) NOT NULL',
                'author_name' => 'NVARCHAR(255) NOT NULL',
                'author_email' => 'NVARCHAR(255) NOT NULL',
                'seo_title' => 'NVARCHAR(60) NULL',
                'seo_description' => 'NVARCHAR(160) NULL',
            ],
            'faqseo' => [
                'type' => 'NVARCHAR(32) NOT NULL',
                'reference_language' => 'NVARCHAR(5) NOT NULL',
                'title' => 'NVARCHAR(MAX) NULL',
                'description' => 'NVARCHAR(MAX) NULL',
                'slug' => 'NVARCHAR(MAX) NULL',
            ],
        ];

        foreach ($unconstrainedColumns as $table => $columns) {
            foreach ($columns as $column => $type) {
                $recorder->addSql(
                    sprintf('ALTER TABLE %s ALTER COLUMN %s %s', $this->table($table), $column, $type),
                    sprintf('Convert %s.%s to Unicode (SQL Server)', $table, $column),
                );
            }
        }

        $recorder->addSql(
            sprintf(
                "IF EXISTS (SELECT name FROM sys.indexes WHERE name = 'idx_custompages_slug'"
                . " AND object_id = OBJECT_ID('%s'))"
                . ' DROP INDEX idx_custompages_slug ON %s',
                $this->table('faqcustompages'),
                $this->table('faqcustompages'),
            ),
            'Drop the slug index before converting faqcustompages.slug (SQL Server)',
        );

        $recorder->addSql(
            sprintf('ALTER TABLE %s ALTER COLUMN slug NVARCHAR(255) NOT NULL', $this->table('faqcustompages')),
            'Convert faqcustompages.slug to Unicode (SQL Server)',
        );

        $recorder->addSql(
            sprintf(
                "IF NOT EXISTS (SELECT name FROM sys.indexes WHERE name = 'idx_custompages_slug'"
                . " AND object_id = OBJECT_ID('%s'))"
                . ' CREATE INDEX idx_custompages_slug ON %s (slug, lang)',
                $this->table('faqcustompages'),
                $this->table('faqcustompages'),
            ),
            'Recreate the slug index on faqcustompages (SQL Server)',
        );

        $recorder->addSql(
            $this->dropDefaultConstraint('faqcustompages', 'seo_robots'),
            'Drop the default constraint bound to faqcustompages.seo_robots (SQL Server)',
        );

        $recorder->addSql(
            sprintf('ALTER TABLE %s ALTER COLUMN seo_robots NVARCHAR(50) NOT NULL', $this->table('faqcustompages')),
            'Convert faqcustompages.seo_robots to Unicode (SQL Server)',
        );

        $recorder->addSql(
            sprintf(
                'IF NOT EXISTS (SELECT 1 FROM sys.default_constraints dc'
                . ' JOIN sys.columns c ON dc.parent_object_id = c.object_id'
                . ' AND dc.parent_column_id = c.column_id'
                . " WHERE dc.parent_object_id = OBJECT_ID('%s') AND c.name = 'seo_robots')"
                . " ALTER TABLE %s ADD DEFAULT 'index,follow' FOR seo_robots",
                $this->table('faqcustompages'),
                $this->table('faqcustompages'),
            ),
            'Restore the default on faqcustompages.seo_robots (SQL Server)',
        );

        // The token columns were added as the deprecated TEXT type (which also breaks equality
        // comparisons on SQL Server) with DEFAULT NULL constraints bound to them.
        $tokenColumns = [
            /* @mago-expect lint:no-literal-password - column names and SQL types, not credentials */
            'refresh_token' => 'NVARCHAR(MAX) NULL',
            /* @mago-expect lint:no-literal-password - column names and SQL types, not credentials */
            'access_token' => 'NVARCHAR(MAX) NULL',
            'jwt' => 'NVARCHAR(MAX) NULL',
            'webauthnkeys' => 'NVARCHAR(MAX) NULL',
            'code_verifier' => 'NVARCHAR(255) NULL',
        ];

        foreach ($tokenColumns as $column => $type) {
            $recorder->addSql(
                $this->dropDefaultConstraint('faquser', $column),
                sprintf('Drop the default constraint bound to faquser.%s (SQL Server)', $column),
            );

            $recorder->addSql(
                sprintf('ALTER TABLE %s ALTER COLUMN %s %s', $this->table('faquser'), $column, $type),
                sprintf('Convert faquser.%s to Unicode (SQL Server)', $column),
            );
        }
    }

    /**
     * Default constraint names are auto-generated per installation, so the constraint has to be
     * resolved and dropped dynamically. Guarded (IF NOT NULL), so re-runs stay safe.
     */
    private function dropDefaultConstraint(string $table, string $column): string
    {
        return sprintf(
            'DECLARE @constraintName nvarchar(200); '
            . 'SELECT @constraintName = dc.name FROM sys.default_constraints dc '
            . 'JOIN sys.columns c ON dc.parent_object_id = c.object_id '
            . 'AND dc.parent_column_id = c.column_id '
            . "WHERE dc.parent_object_id = OBJECT_ID('%s') AND c.name = '%s'; "
            . 'IF @constraintName IS NOT NULL '
            . "EXEC('ALTER TABLE %s DROP CONSTRAINT ' + @constraintName)",
            $this->table($table),
            $column,
            $this->table($table),
        );
    }

    /**
     * Adds the editorial workflow status column, backfills it from the legacy active flag
     * whenever both columns exist (covering a re-run that died between add and drop), and
     * then drops the legacy column. Guarded per step because this migration re-runs on
     * installations whose recorded checksum predates the amendment.
     */
    private function introduceEditorialWorkflowStatus(OperationRecorder $recorder): void
    {
        foreach (['faqdata', 'faqdata_revisions'] as $table) {
            if (!$this->columnExists($table, 'status')) {
                $recorder->addSql(
                    $this->addColumn($table, 'status', $this->varcharType(12) . ' NOT NULL', "'draft'"),
                    sprintf('Add editorial status column to %s', $table),
                );
            }

            if ($this->columnExists($table, 'active')) {
                $recorder->addSql(
                    sprintf(
                        "UPDATE %s SET status = CASE WHEN active = 'yes' THEN 'published' ELSE 'draft' END",
                        $this->table($table),
                    ),
                    sprintf('Backfill editorial status from the active flag in %s', $table),
                );

                // SQL Server refuses to drop a column while a default constraint is bound to
                // it, and constraint names are auto-generated per installation — resolve and
                // drop it dynamically. Guarded (IF NOT NULL), so re-runs stay safe.
                if ($this->isSqlServer()) {
                    $recorder->addSql(
                        sprintf(
                            'DECLARE @constraintName nvarchar(200); '
                            . 'SELECT @constraintName = dc.name FROM sys.default_constraints dc '
                            . 'JOIN sys.columns c ON dc.parent_object_id = c.object_id '
                            . 'AND dc.parent_column_id = c.column_id '
                            . "WHERE dc.parent_object_id = OBJECT_ID('%s') AND c.name = 'active'; "
                            . 'IF @constraintName IS NOT NULL '
                            . "EXEC('ALTER TABLE %s DROP CONSTRAINT ' + @constraintName)",
                            $this->table($table),
                            $this->table($table),
                        ),
                        sprintf('Drop the default constraint bound to %s.active (SQL Server)', $table),
                    );
                }

                $recorder->addSql(
                    $this->dropColumn($table, 'active'),
                    sprintf('Drop the legacy active column from %s', $table),
                );
            }
        }
    }

    /**
     * approverec was seeded and translated for years but never checked anywhere;
     * the editorial workflow gates publishing on faq_publish instead. Idempotent
     * DELETEs so the amended-migration re-run stays safe.
     */
    private function retireApproveRight(OperationRecorder $recorder): void
    {
        $rightIdSelect = sprintf("SELECT right_id FROM %sfaqright WHERE name = 'approverec'", $this->tablePrefix);

        foreach ([
            'faquser_right',
            'faqgroup_right',
            'faquser_right_language',
            'faqgroup_right_language',
            'faqgroup_right_category',
        ] as $mappingTable) {
            $recorder->addSql(
                sprintf('DELETE FROM %s WHERE right_id IN (%s)', $this->table($mappingTable), $rightIdSelect),
                sprintf('Remove approverec grants from %s', $mappingTable),
            );
        }

        $recorder->addSql(
            sprintf("DELETE FROM %sfaqright WHERE name = 'approverec'", $this->tablePrefix),
            'Remove the unused approverec right',
        );
    }

    /**
     * Record-time column check so re-running this amended migration never issues
     * an ALTER TABLE that would fail on the second pass.
     */
    private function columnExists(string $table, string $column): bool
    {
        $tableName = $this->table($table);

        $query = match (true) {
            $this->isMySql() => sprintf("SHOW COLUMNS FROM %s LIKE '%s'", $tableName, $column),
            $this->isPostgreSql() => sprintf(
                "SELECT column_name FROM information_schema.columns WHERE table_name = '%s' AND column_name = '%s'",
                $tableName,
                $column,
            ),
            $this->isSqlite() => sprintf(
                "SELECT name FROM pragma_table_info('%s') WHERE name = '%s'",
                $tableName,
                $column,
            ),
            default => sprintf(
                "SELECT name FROM sys.columns WHERE object_id = OBJECT_ID('%s') AND name = '%s'",
                $tableName,
                $column,
            ),
        };

        $result = $this->configuration->getDb()->query($query);

        return $this->configuration->getDb()->numRows($result) > 0;
    }

    /**
     * Creates the faqquestion_history table that records the open question
     * lifecycle (submitted, answered, reopened) with actor and timestamp.
     */
    private function createQuestionHistoryTable(OperationRecorder $recorder): void
    {
        $intType = $this->integerType();

        if ($this->isMySql()) {
            $recorder->addSql(
                sprintf(
                    'CREATE TABLE IF NOT EXISTS %sfaqquestion_history (
                        id %s NOT NULL,
                        question_id %s NOT NULL,
                        question_lang VARCHAR(5) NOT NULL,
                        event_type VARCHAR(20) NOT NULL,
                        user_id %s NOT NULL DEFAULT -1,
                        username VARCHAR(100) NOT NULL,
                        faq_id %s NOT NULL DEFAULT 0,
                        created VARCHAR(20) NOT NULL,
                        PRIMARY KEY (id),
                        INDEX idx_faqquestion_history (question_id, question_lang)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
                    $this->tablePrefix,
                    $intType,
                    $intType,
                    $intType,
                    $intType,
                ),
                'Create faqquestion_history table (MySQL)',
            );
        }

        if ($this->isPostgreSql()) {
            $recorder->addSql(
                sprintf('CREATE TABLE IF NOT EXISTS %sfaqquestion_history (
                        id %s NOT NULL,
                        question_id %s NOT NULL,
                        question_lang VARCHAR(5) NOT NULL,
                        event_type VARCHAR(20) NOT NULL,
                        user_id %s NOT NULL DEFAULT -1,
                        username VARCHAR(100) NOT NULL,
                        faq_id %s NOT NULL DEFAULT 0,
                        created VARCHAR(20) NOT NULL,
                        PRIMARY KEY (id)
                    )', $this->tablePrefix, $intType, $intType, $intType, $intType),
                'Create faqquestion_history table (PostgreSQL)',
            );

            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_faqquestion_history ON %sfaqquestion_history '
                    . '(question_id, question_lang)',
                    $this->tablePrefix,
                ),
                'Create faqquestion_history index (PostgreSQL)',
            );
        }

        if ($this->isSqlite()) {
            $recorder->addSql(
                sprintf('CREATE TABLE IF NOT EXISTS %sfaqquestion_history (
                        id %s NOT NULL,
                        question_id %s NOT NULL,
                        question_lang VARCHAR(5) NOT NULL,
                        event_type VARCHAR(20) NOT NULL,
                        user_id %s NOT NULL DEFAULT -1,
                        username VARCHAR(100) NOT NULL,
                        faq_id %s NOT NULL DEFAULT 0,
                        created VARCHAR(20) NOT NULL,
                        PRIMARY KEY (id)
                    )', $this->tablePrefix, $intType, $intType, $intType, $intType),
                'Create faqquestion_history table (SQLite)',
            );

            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_faqquestion_history ON %sfaqquestion_history '
                    . '(question_id, question_lang)',
                    $this->tablePrefix,
                ),
                'Create faqquestion_history index (SQLite)',
            );
        }

        if ($this->isSqlServer()) {
            $recorder->addSql(
                sprintf(
                    'IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = \'%sfaqquestion_history\') '
                    . 'CREATE TABLE %sfaqquestion_history (
                        id %s NOT NULL,
                        question_id %s NOT NULL,
                        question_lang NVARCHAR(5) NOT NULL,
                        event_type NVARCHAR(20) NOT NULL,
                        user_id %s NOT NULL DEFAULT -1,
                        username NVARCHAR(100) NOT NULL,
                        faq_id %s NOT NULL DEFAULT 0,
                        created NVARCHAR(20) NOT NULL,
                        PRIMARY KEY (id)
                    )',
                    $this->tablePrefix,
                    $this->tablePrefix,
                    $intType,
                    $intType,
                    $intType,
                    $intType,
                ),
                'Create faqquestion_history table (SQL Server)',
            );

            $recorder->addSql(
                sprintf(
                    'IF NOT EXISTS (SELECT name FROM sys.indexes WHERE name = \'idx_faqquestion_history\''
                    . ' AND object_id = OBJECT_ID(N\'%sfaqquestion_history\'))'
                    . ' CREATE INDEX idx_faqquestion_history ON %sfaqquestion_history (question_id, question_lang)',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create faqquestion_history index (SQL Server)',
            );
        }
    }

    /**
     * Splits reading and publishing off from the edit/approve mix without changing anybody's
     * effective permissions.
     *
     * view_faqs has existed since 3.0 but was never enforced, so it has to reach every existing
     * user and group before the read gate goes live — otherwise an upgrade would blank the FAQ
     * for everybody. faq_publish is new and mirrors approverec, carrying its category and
     * language restrictions across so a scoped approver becomes an equally scoped publisher.
     *
     * The read backfill runs first: if the update dies between the two operations, everybody can
     * still read, which is the more forgiving half-applied state.
     */
    private function separateReadAndPublishRights(OperationRecorder $recorder): void
    {
        $recorder->backfillPermission(
            PermissionType::FAQS_VIEW->value,
            'Right to view FAQs',
            grantToAllUsers: true,
            grantToAllGroups: true,
        );

        $recorder->backfillPermission(
            PermissionType::FAQ_PUBLISH->value,
            'Right to publish FAQs',
            mirrorFrom: 'approverec',
            mirrorRestrictions: true,
        );
    }
}
