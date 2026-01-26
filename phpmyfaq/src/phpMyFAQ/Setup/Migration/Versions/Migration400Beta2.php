<?php

/**
 * Migration for phpMyFAQ 4.0.0-beta.2.
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

readonly class Migration400Beta2 extends AbstractMigration
{
    public function getVersion(): string
    {
        return '4.0.0-beta.2';
    }

    public function getDependencies(): array
    {
        return ['4.0.0-alpha.3'];
    }

    public function getDescription(): string
    {
        return 'WebAuthn support';
    }

    public function up(OperationRecorder $recorder): void
    {
        // WebAuthn support
        $recorder->addConfig('security.enableWebAuthnSupport', false);

        if ($this->isSqlite()) {
            $recorder->addSql(
                sprintf('ALTER TABLE %sfaquser ADD COLUMN webauthnkeys TEXT NULL DEFAULT NULL', $this->tablePrefix),
                'Add WebAuthn keys column to faquser (SQLite)',
            );
        } else {
            $recorder->addSql(
                sprintf('ALTER TABLE %sfaquser ADD webauthnkeys TEXT NULL DEFAULT NULL', $this->tablePrefix),
                'Add WebAuthn keys column to faquser',
            );
        }
    }
}
