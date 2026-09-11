<?php

/**
 * Migration for phpMyFAQ 4.1.9.
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
 * @since     2026-09-11
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Setup\Migration\AbstractMigration;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;

readonly class Migration419 extends AbstractMigration
{
    public function getVersion(): string
    {
        return '4.1.9';
    }

    public function getDependencies(): array
    {
        return ['4.1.8'];
    }

    public function getDescription(): string
    {
        return 'Widen the faqvisits.visits counter for upgraded installations';
    }

    /**
     * faqvisits.visits was created as SMALLINT before v3.2.0, and the fix for #2124
     * only changed the CREATE TABLE statement, so upgraded installations still have
     * a counter that is capped at 32767 (see #4624).
     */
    public function up(OperationRecorder $recorder): void
    {
        $table = $this->table('faqvisits');

        if ($this->isMySql()) {
            $recorder->addSql(
                sprintf('ALTER TABLE %s MODIFY visits INT(11) NOT NULL', $table),
                'Widen faqvisits.visits to INT (MySQL)',
            );
        }

        if ($this->isPostgreSql()) {
            $recorder->addSql(
                sprintf('ALTER TABLE %s ALTER COLUMN visits TYPE INTEGER', $table),
                'Widen faqvisits.visits to INTEGER (PostgreSQL)',
            );
        }

        if ($this->isSqlServer()) {
            $recorder->addSql(
                sprintf('ALTER TABLE %s ALTER COLUMN visits INTEGER NOT NULL', $table),
                'Widen faqvisits.visits to INTEGER (SQL Server)',
            );
        }

        // SQLite: SMALLINT only sets INTEGER affinity without a range limit,
        // so there is nothing to migrate
    }
}
