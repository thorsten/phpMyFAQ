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
 * @since     2026-03-15
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Versions;

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
        return 'Add faqgroup_right_category table for granular group-based category permissions';
    }

    public function up(OperationRecorder $recorder): void
    {
        $intType = $this->integerType();

        if ($this->isMySql()) {
            $recorder->addSql(
                sprintf(
                    'CREATE TABLE IF NOT EXISTS %sfaqgroup_right_category (
                        group_id %s NOT NULL,
                        right_id %s NOT NULL,
                        category_id %s NOT NULL,
                        PRIMARY KEY (group_id, right_id, category_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
                    $this->tablePrefix,
                    $intType,
                    $intType,
                    $intType,
                ),
                'Create faqgroup_right_category table (MySQL)',
            );
        }

        if ($this->isPostgreSql()) {
            $recorder->addSql(
                sprintf('CREATE TABLE IF NOT EXISTS %sfaqgroup_right_category (
                        group_id %s NOT NULL,
                        right_id %s NOT NULL,
                        category_id %s NOT NULL,
                        PRIMARY KEY (group_id, right_id, category_id)
                    )', $this->tablePrefix, $intType, $intType, $intType),
                'Create faqgroup_right_category table (PostgreSQL)',
            );
        }

        if ($this->isSqlite()) {
            $recorder->addSql(
                sprintf('CREATE TABLE IF NOT EXISTS %sfaqgroup_right_category (
                        group_id %s NOT NULL,
                        right_id %s NOT NULL,
                        category_id %s NOT NULL,
                        PRIMARY KEY (group_id, right_id, category_id)
                    )', $this->tablePrefix, $intType, $intType, $intType),
                'Create faqgroup_right_category table (SQLite)',
            );
        }

        if ($this->isSqlServer()) {
            $recorder->addSql(
                sprintf(
                    'IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = \'%sfaqgroup_right_category\') '
                    . 'CREATE TABLE %sfaqgroup_right_category (
                        group_id %s NOT NULL,
                        right_id %s NOT NULL,
                        category_id %s NOT NULL,
                        PRIMARY KEY (group_id, right_id, category_id)
                    )',
                    $this->tablePrefix,
                    $this->tablePrefix,
                    $intType,
                    $intType,
                    $intType,
                ),
                'Create faqgroup_right_category table (SQL Server)',
            );
        }
    }

    public function isReversible(): bool
    {
        return true;
    }

    public function down(OperationRecorder $recorder): void
    {
        $recorder->addSql($this->dropTableIfExists('faqgroup_right_category'), 'Drop faqgroup_right_category table');
    }
}
