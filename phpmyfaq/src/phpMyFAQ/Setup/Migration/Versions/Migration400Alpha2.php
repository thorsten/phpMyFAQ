<?php

/**
 * Migration for phpMyFAQ 4.0.0-alpha.2.
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

readonly class Migration400Alpha2 extends AbstractMigration
{
    /**
     * Migration version identifier for this migration.
     *
     * @return string The semantic version string "4.0.0-alpha.2".
     */
    public function getVersion(): string
    {
        return '4.0.0-alpha.2';
    }

    /**
     * List migration version identifiers that must be applied before this migration.
     *
     * @return string[] Array of migration version strings required to run before this migration.
     */
    public function getDependencies(): array
    {
        return ['4.0.0-alpha'];
    }

    /**
     * Short human-readable description of this migration.
     *
     * @return string A brief description of the migration's purpose: "Forms table and forms_edit permission".
     */
    public function getDescription(): string
    {
        return 'Forms table and forms_edit permission';
    }

    /**
     * Applies the migration by removing a deprecated configuration key, granting the forms edit permission, and creating the forms table.
     *
     * The migration deletes the `main.optionalMailAddress` config key, grants the `forms_edit` permission, and creates a table named `{tablePrefix}faqforms`
     * with the columns: `form_id`, `input_id`, `input_type`, `input_label`, `input_active`, `input_required`, and `input_lang`.
     * The exact DDL for the table depends on the database dialect (MySQL, SQL Server, or generic).
     *
     * Note: insertion of form inputs is handled separately by the Forms class.
     */
    public function up(OperationRecorder $recorder): void
    {
        $recorder->deleteConfig('main.optionalMailAddress');

        // Add new permission for editing forms
        $recorder->grantPermission('forms_edit', 'Right to edit forms');

        // Create forms table
        if ($this->isMySql()) {
            $recorder->addSql(sprintf(
                'CREATE TABLE %sfaqforms (
                    form_id INT(1) NOT NULL,
                    input_id INT(11) NOT NULL,
                    input_type VARCHAR(1000) NOT NULL,
                    input_label VARCHAR(100) NOT NULL,
                    input_active INT(1) NOT NULL,
                    input_required INT(1) NOT NULL,
                    input_lang VARCHAR(11) NOT NULL)',
                $this->tablePrefix,
            ), 'Create forms table (MySQL)');
        } elseif ($this->isSqlServer()) {
            $recorder->addSql(sprintf(
                'CREATE TABLE %sfaqforms (
                    form_id INTEGER NOT NULL,
                    input_id INTEGER NOT NULL,
                    input_type NVARCHAR(1000) NOT NULL,
                    input_label NVARCHAR(100) NOT NULL,
                    input_active INTEGER NOT NULL,
                    input_required INTEGER NOT NULL,
                    input_lang NVARCHAR(11) NOT NULL)',
                $this->tablePrefix,
            ), 'Create forms table (SQL Server)');
        } else {
            $recorder->addSql(sprintf(
                'CREATE TABLE %sfaqforms (
                    form_id INTEGER NOT NULL,
                    input_id INTEGER NOT NULL,
                    input_type VARCHAR(1000) NOT NULL,
                    input_label VARCHAR(100) NOT NULL,
                    input_active INTEGER NOT NULL,
                    input_required INTEGER NOT NULL,
                    input_lang VARCHAR(11) NOT NULL)',
                $this->tablePrefix,
            ), 'Create forms table');
        }

        // Note: The form inputs insertion is handled separately through the Forms class
        // because it requires complex business logic that varies by installation
    }
}