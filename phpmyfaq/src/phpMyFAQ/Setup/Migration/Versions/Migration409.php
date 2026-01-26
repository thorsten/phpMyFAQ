<?php

/**
 * Migration for phpMyFAQ 4.0.9.
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

readonly class Migration409 extends AbstractMigration
{
    public function getVersion(): string
    {
        return '4.0.9';
    }

    public function getDependencies(): array
    {
        return ['4.0.7'];
    }

    public function getDescription(): string
    {
        return 'PostgreSQL sequence for faqseo table';
    }

    public function up(OperationRecorder $recorder): void
    {
        // PostgreSQL-only migration for faqseo sequence
        if ($this->isPostgreSql()) {
            $recorder->addSql(
                sprintf('CREATE SEQUENCE %sfaqseo_id_seq', $this->tablePrefix),
                'Create sequence for faqseo table (PostgreSQL)',
            );

            $recorder->addSql(
                sprintf(
                    "ALTER TABLE %sfaqseo ALTER COLUMN id SET DEFAULT nextval('faqseo_id_seq')",
                    $this->tablePrefix,
                ),
                'Set default for faqseo.id using sequence (PostgreSQL)',
            );

            $recorder->addSql(
                sprintf("SELECT setval('faqseo_id_seq', (SELECT MAX(id) FROM %sfaqseo))", $this->tablePrefix),
                'Set sequence value to max id (PostgreSQL)',
            );

            $recorder->addSql(
                sprintf('ALTER TABLE %sfaqseo ALTER COLUMN id SET NOT NULL', $this->tablePrefix),
                'Set faqseo.id as NOT NULL (PostgreSQL)',
            );
        }
    }
}
