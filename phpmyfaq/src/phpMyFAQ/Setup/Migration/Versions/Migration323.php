<?php

/**
 * Migration for phpMyFAQ 3.2.3.
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

readonly class Migration323 extends AbstractMigration
{
    public function getVersion(): string
    {
        return '3.2.3';
    }

    public function getDependencies(): array
    {
        return ['3.2.0-RC'];
    }

    public function getDescription(): string
    {
        return 'Increase IP column size for IPv6 support';
    }

    public function up(OperationRecorder $recorder): void
    {
        if ($this->isMySql()) {
            $recorder->addSql(
                sprintf('ALTER TABLE %sfaquser CHANGE ip ip VARCHAR(64) NULL DEFAULT NULL', $this->tablePrefix),
                'Increase faquser.ip column size (MySQL)',
            );
        } elseif ($this->isPostgreSql()) {
            $recorder->addSql(
                sprintf('ALTER TABLE %sfaquser ALTER COLUMN ip TYPE VARCHAR(64)', $this->tablePrefix),
                'Increase faquser.ip column size (PostgreSQL)',
            );
        } elseif ($this->isSqlite()) {
            // SQLite requires table rebuild
            $recorder->addSql(sprintf(
                'CREATE TABLE %sfaquser_new (
                    user_id INTEGER NOT NULL,
                    login VARCHAR(128) NOT NULL,
                    session_id VARCHAR(150) NULL,
                    session_timestamp INTEGER NULL,
                    ip VARCHAR(64) NULL,
                    account_status VARCHAR(50) NULL,
                    last_login VARCHAR(14) NULL,
                    auth_source VARCHAR(100) NULL,
                    member_since VARCHAR(14) NULL,
                    remember_me VARCHAR(150) NULL,
                    success INT(1) NULL DEFAULT 1,
                    is_superadmin INT(1) NULL DEFAULT 0,
                    login_attempts INT(1) NULL DEFAULT 0,
                    refresh_token TEXT NULL DEFAULT NULL,
                    access_token TEXT NULL DEFAULT NULL,
                    code_verifier VARCHAR(255) NULL DEFAULT NULL,
                    jwt TEXT NULL DEFAULT NULL,
                    PRIMARY KEY (user_id))',
                $this->tablePrefix,
            ), 'Create new faquser table with larger IP column (SQLite)');

            $recorder->addSql(
                sprintf('INSERT INTO %sfaquser_new SELECT * FROM %sfaquser', $this->tablePrefix, $this->tablePrefix),
                'Copy data to new faquser table (SQLite)',
            );

            $recorder->addSql(sprintf('DROP TABLE %sfaquser', $this->tablePrefix), 'Drop old faquser table (SQLite)');

            $recorder->addSql(
                sprintf('ALTER TABLE %sfaquser_new RENAME TO %sfaquser', $this->tablePrefix, $this->tablePrefix),
                'Rename new faquser table (SQLite)',
            );
        } elseif ($this->isSqlServer()) {
            $recorder->addSql(
                sprintf('ALTER TABLE %sfaquser ALTER COLUMN ip VARCHAR(64)', $this->tablePrefix),
                'Increase faquser.ip column size (SQL Server)',
            );
        }
    }
}
