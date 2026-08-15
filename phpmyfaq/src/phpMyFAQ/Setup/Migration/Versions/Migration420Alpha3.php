<?php

/**
 * Migration for phpMyFAQ 4.2.0-alpha.3.
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
 * @since     2026-08-15
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Setup\Migration\AbstractMigration;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;

readonly class Migration420Alpha3 extends AbstractMigration
{
    public function getVersion(): string
    {
        return '4.2.0-alpha.3';
    }

    public function getDependencies(): array
    {
        return ['4.2.0-alpha.2'];
    }

    public function getDescription(): string
    {
        return 'Add faqquestion_history table for open question lifecycle metadata tracking';
    }

    public function up(OperationRecorder $recorder): void
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
                        question_lang VARCHAR(5) NOT NULL,
                        event_type VARCHAR(20) NOT NULL,
                        user_id %s NOT NULL DEFAULT -1,
                        username VARCHAR(100) NOT NULL,
                        faq_id %s NOT NULL DEFAULT 0,
                        created VARCHAR(20) NOT NULL,
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
                    'IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = \'idx_faqquestion_history\') '
                    . 'CREATE INDEX idx_faqquestion_history ON %sfaqquestion_history (question_id, question_lang)',
                    $this->tablePrefix,
                ),
                'Create faqquestion_history index (SQL Server)',
            );
        }
    }
}
