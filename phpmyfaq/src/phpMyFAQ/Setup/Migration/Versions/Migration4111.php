<?php

/**
 * Migration for phpMyFAQ 4.1.11: drop the legacy UNIQUE index on faquser.session_id.
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
 * @since     2026-10-14
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Setup\Migration\AbstractMigration;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;

readonly class Migration4111 extends AbstractMigration
{
    public function getVersion(): string
    {
        return '4.1.11';
    }

    public function getDependencies(): array
    {
        return ['4.1.9'];
    }

    public function getDescription(): string
    {
        return 'Drop the legacy UNIQUE index "session" on faquser.session_id for installations created before v3.x';
    }

    /**
     * Installations created before v3.x carry a legacy UNIQUE index "session" on
     * faquser.session_id (only the MySQL schema ever had it). It was never dropped
     * by a migration and makes every session reset collide with the anonymous
     * user (see #4764).
     */
    public function up(OperationRecorder $recorder): void
    {
        if (!$this->isMySql()) {
            return;
        }

        $table = $this->table('faquser');
        $db = $this->configuration->getDb();
        $result = $db->query(sprintf("SHOW INDEX FROM %s WHERE Key_name = 'session'", $table));

        if ($result === false || $db->numRows($result) === 0) {
            return;
        }

        $recorder->addSql(
            sprintf('ALTER TABLE %s DROP INDEX session', $table),
            'Drop the legacy UNIQUE index "session" on faquser.session_id (MySQL)',
        );
    }
}
