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
    /**
     * Migration version identifier.
     *
     * @return string The version identifier for this migration (e.g., "3.2.0-alpha").
     */
    public function getVersion(): string
    {
        return '3.2.0-alpha';
    }

    /**
     * Provide a short human-readable summary of the migration's purpose.
     *
     * @return string A concise description listing the migration's changes: Microsoft Entra ID support, two-factor authentication support, creation of a backup table, Google ReCAPTCHA v2 support, and removal of section tables.
     */
    public function getDescription(): string
    {
        return 'Microsoft Entra ID support, 2FA support, backup table, Google ReCAPTCHA v2, remove section tables';
    }

    /**
     * Apply schema and configuration changes required by the 3.2.0-alpha migration.
     *
     * Performs database schema updates and configuration additions/remappings, including:
     * - enable Microsoft Entra ID sign-in and 2FA support flag,
     * - add OAuth-related columns to faquser and 2FA columns to faquserdata,
     * - create the faqbackup table,
     * - migrate faqdata to InnoDB on MySQL,
     * - add new main.* and Google ReCAPTCHA v2 configuration entries,
     * - drop obsolete section-related tables.
     *
     * @param OperationRecorder $recorder Recorder used to schedule SQL and configuration operations for the migration.
     */
    public function up(OperationRecorder $recorder): void
    {
        // Microsoft Entra ID support and 2FA-support
        $recorder->addConfig('security.enableSignInWithMicrosoft', false);

        // Add columns for OAuth tokens
        if ($this->isSqlite()) {
            $recorder->addSql(sprintf(
                'ALTER TABLE %sfaquser
                    ADD COLUMN refresh_token TEXT NULL DEFAULT NULL,
                    ADD COLUMN access_token TEXT NULL DEFAULT NULL,
                    ADD COLUMN code_verifier VARCHAR(255) NULL DEFAULT NULL,
                    ADD COLUMN jwt TEXT NULL DEFAULT NULL',
                $this->tablePrefix,
            ), 'Add OAuth token columns to faquser (SQLite)');

            $recorder->addSql(sprintf(
                'ALTER TABLE %sfaquserdata
                    ADD COLUMN twofactor_enabled INT(1) NULL DEFAULT 0,
                    ADD COLUMN secret VARCHAR(128) NULL DEFAULT NULL',
                $this->tablePrefix,
            ), 'Add 2FA columns to faquserdata (SQLite)');
        } else {
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
        $recorder->addSql(sprintf('CREATE TABLE %sfaqbackup (
                id INT NOT NULL,
                filename VARCHAR(255) NOT NULL,
                authkey VARCHAR(255) NOT NULL,
                authcode VARCHAR(255) NOT NULL,
                created timestamp NOT NULL,
                PRIMARY KEY (id))', $this->tablePrefix), 'Create backup table');

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

        // Remove section tables
        $recorder->addSql(sprintf('DROP TABLE %sfaqsections', $this->tablePrefix), 'Drop faqsections table');
        $recorder->addSql(
            sprintf('DROP TABLE %sfaqsection_category', $this->tablePrefix),
            'Drop faqsection_category table',
        );
        $recorder->addSql(sprintf('DROP TABLE %sfaqsection_group', $this->tablePrefix), 'Drop faqsection_group table');
        $recorder->addSql(sprintf('DROP TABLE %sfaqsection_news', $this->tablePrefix), 'Drop faqsection_news table');
    }
}