<?php

/**
 * Migration for phpMyFAQ 4.2.0-RC.2: store password-reset tokens server-side.
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
 * @since     2026-11-09
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Setup\Migration\AbstractMigration;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;

/**
 * Password-reset links used to be signed with the user's stored password hash, so anyone who
 * could read that hash could forge a link. The token is now a random secret whose SHA-256 hash
 * and expiry are stored on the user row.
 *
 * The start page hero can show an optional image, configured in layout.heroImage.
 */
readonly class Migration420RC2 extends AbstractMigration
{
    public function getVersion(): string
    {
        return '4.2.0-RC.2';
    }

    public function getDependencies(): array
    {
        return ['4.2.0-RC.1'];
    }

    public function getDescription(): string
    {
        return (
            'Add the password_reset_token and password_reset_expires columns to faquser '
            . 'and the layout.heroImage configuration'
        );
    }

    /**
     * Both column steps are guarded by a column check so the migration stays safe to re-run;
     * Configuration::add() skips keys that already exist.
     */
    public function up(OperationRecorder $recorder): void
    {
        if (!$this->columnExists('faquser', 'password_reset_token')) {
            $recorder->addSql(
                $this->addColumn('faquser', 'password_reset_token', $this->varcharType(64) . ' NULL'),
                'Add password_reset_token column to faquser',
            );
        }

        if (!$this->columnExists('faquser', 'password_reset_expires')) {
            $recorder->addSql(
                $this->addColumn('faquser', 'password_reset_expires', $this->integerType() . ' NULL'),
                'Add password_reset_expires column to faquser',
            );
        }

        $recorder->addConfig('layout.heroImage', '');
    }
}
