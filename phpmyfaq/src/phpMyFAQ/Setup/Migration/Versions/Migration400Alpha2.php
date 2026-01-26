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
    public function getVersion(): string
    {
        return '4.0.0-alpha.2';
    }

    public function getDependencies(): array
    {
        return ['4.0.0-alpha'];
    }

    public function getDescription(): string
    {
        return 'Forms table and forms_edit permission';
    }

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
