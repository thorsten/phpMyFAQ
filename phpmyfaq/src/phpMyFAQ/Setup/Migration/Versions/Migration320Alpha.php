<?php

/**
 * Migration for phpMyFAQ 3.2.0-alpha.
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

readonly class Migration320Alpha extends AbstractMigration
{
    public function getVersion(): string
    {
        return '3.2.0-alpha';
    }

    public function getDescription(): string
    {
        return 'Microsoft Entra ID support, 2FA support, backup table, Google ReCAPTCHA v2, remove section tables';
    }

    public function up(OperationRecorder $recorder): void
    {
        // Microsoft Entra ID support and 2FA-support
        $recorder->addConfig('security.enableSignInWithMicrosoft', false);

        // Add columns for OAuth tokens
        if ($this->isSqlite()) {
            $recorder->addSql(
                sprintf('ALTER TABLE %sfaquser ADD COLUMN refresh_token TEXT NULL DEFAULT NULL', $this->tablePrefix),
                'Add OAuth refresh_token to faquser (SQLite)',
            );
            $recorder->addSql(
                sprintf('ALTER TABLE %sfaquser ADD COLUMN access_token TEXT NULL DEFAULT NULL', $this->tablePrefix),
                'Add OAuth access_token to faquser (SQLite)',
            );
            $recorder->addSql(
                sprintf(
                    'ALTER TABLE %sfaquser ADD COLUMN code_verifier VARCHAR(255) NULL DEFAULT NULL',
                    $this->tablePrefix,
                ),
                'Add OAuth code_verifier to faquser (SQLite)',
            );
            $recorder->addSql(
                sprintf('ALTER TABLE %sfaquser ADD COLUMN jwt TEXT NULL DEFAULT NULL', $this->tablePrefix),
                'Add OAuth jwt to faquser (SQLite)',
            );

            $recorder->addSql(
                sprintf(
                    'ALTER TABLE %sfaquserdata ADD COLUMN twofactor_enabled INT(1) NULL DEFAULT 0',
                    $this->tablePrefix,
                ),
                'Add 2FA twofactor_enabled to faquserdata (SQLite)',
            );
            $recorder->addSql(
                sprintf(
                    'ALTER TABLE %sfaquserdata ADD COLUMN secret VARCHAR(128) NULL DEFAULT NULL',
                    $this->tablePrefix,
                ),
                'Add 2FA secret to faquserdata (SQLite)',
            );
        }

        if ($this->isPostgreSql()) {
            // "IF NOT EXISTS" keeps a re-run alive after a previously failed update
            // already applied this statement - the version number is only updated
            // at the very end, so a failed run starts over from the old version.
            $recorder->addSql(sprintf(
                'ALTER TABLE %sfaquser
                    ADD COLUMN IF NOT EXISTS refresh_token TEXT NULL DEFAULT NULL,
                    ADD COLUMN IF NOT EXISTS access_token TEXT NULL DEFAULT NULL,
                    ADD COLUMN IF NOT EXISTS code_verifier VARCHAR(255) NULL DEFAULT NULL,
                    ADD COLUMN IF NOT EXISTS jwt TEXT NULL DEFAULT NULL',
                $this->tablePrefix,
            ), 'Add OAuth token columns to faquser');

            $recorder->addSql(sprintf(
                'ALTER TABLE %sfaquserdata
                    ADD COLUMN IF NOT EXISTS twofactor_enabled INT NULL DEFAULT 0,
                    ADD COLUMN IF NOT EXISTS secret VARCHAR(128) NULL DEFAULT NULL',
                $this->tablePrefix,
            ), 'Add 2FA columns to faquserdata');
        }

        if (!$this->isSqlite() && !$this->isPostgreSql()) {
            $recorder->addSql(sprintf(
                'ALTER TABLE %sfaquser
                    ADD refresh_token TEXT NULL DEFAULT NULL,
                    ADD access_token TEXT NULL DEFAULT NULL,
                    ADD code_verifier VARCHAR(255) NULL DEFAULT NULL,
                    ADD jwt TEXT NULL DEFAULT NULL',
                $this->tablePrefix,
            ), 'Add OAuth token columns to faquser');

            $recorder->addSql(sprintf(
                'ALTER TABLE %sfaquserdata
                    ADD twofactor_enabled INT NULL DEFAULT 0,
                    ADD secret VARCHAR(128) NULL DEFAULT NULL',
                $this->tablePrefix,
            ), 'Add 2FA columns to faquserdata');
        }

        // New backup table
        // SQL Server has no "CREATE TABLE IF NOT EXISTS"
        $createTable = $this->isSqlServer() ? 'CREATE TABLE' : 'CREATE TABLE IF NOT EXISTS';
        $timestampType = $this->timestampType(false);
        $recorder->addSql(
            sprintf('%s %sfaqbackup (
                id INT NOT NULL,
                filename VARCHAR(255) NOT NULL,
                authkey VARCHAR(255) NOT NULL,
                authcode VARCHAR(255) NOT NULL,
                created %s NOT NULL,
                PRIMARY KEY (id))', $createTable, $this->tablePrefix, $timestampType),
            'Create backup table',
        );

        // Migrate MySQL from MyISAM to InnoDB
        if ($this->isMySql()) {
            $recorder->addSql(
                sprintf('ALTER TABLE %sfaqdata ENGINE=INNODB', $this->tablePrefix),
                'Migrate faqdata to InnoDB',
            );
        }

        // New options
        $recorder->addConfig('main.enableAskQuestions', true);
        $recorder->addConfig('main.enableNotifications', true);

        // Update options
        $recorder->renameConfig('security.loginWithEmailAddress', 'security.loginWithEmailAddress');

        // Handle permLevel migration - note: actual update requires reading current value
        // This is handled by the configuration update operation

        // Google ReCAPTCHAv3 support
        $recorder->addConfig('security.enableGoogleReCaptchaV2', false);
        $recorder->addConfig('security.googleReCaptchaV2SiteKey', '');
        $recorder->addConfig('security.googleReCaptchaV2SecretKey', '');

        // Remove section tables ("IF EXISTS" is supported by all databases, SQL Server since 2016)
        $recorder->addSql($this->dropTableIfExists('faqsections'), 'Drop faqsections table');
        $recorder->addSql($this->dropTableIfExists('faqsection_category'), 'Drop faqsection_category table');
        $recorder->addSql($this->dropTableIfExists('faqsection_group'), 'Drop faqsection_group table');
        $recorder->addSql($this->dropTableIfExists('faqsection_news'), 'Drop faqsection_news table');
    }
}
