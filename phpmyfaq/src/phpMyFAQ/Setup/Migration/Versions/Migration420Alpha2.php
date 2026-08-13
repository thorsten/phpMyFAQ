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
            . 'language-based permissions, and separate the FAQ read and publish rights'
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
            mirrorFrom: PermissionType::FAQ_APPROVE->value,
            mirrorRestrictions: true,
        );
    }
}
