<?php

/**
 * Migration for phpMyFAQ 3.2.0-beta.2.
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

readonly class Migration320Beta2 extends AbstractMigration
{
    /**
     * Migration version identifier.
     *
     * @return string The migration version identifier '3.2.0-beta.2'.
     */
    public function getVersion(): string
    {
        return '3.2.0-beta.2';
    }

    /**
     * Migration version identifiers required before applying this migration.
     *
     * @return string[] An array of version identifiers this migration depends on.
     */
    public function getDependencies(): array
    {
        return ['3.2.0-beta'];
    }

    /**
     * Provide a short human-readable description of this migration.
     *
     * @return string The migration description: "HTML support for contact information, rename contactInformations".
     */
    public function getDescription(): string
    {
        return 'HTML support for contact information, rename contactInformations';
    }

    /**
     * Apply migration changes for version 3.2.0-beta.2.
     *
     * Adds the configuration key `main.contactInformationHTML` with a default value of `false`
     * and renames the configuration key `main.contactInformations` to `main.contactInformation`.
     *
     * @param OperationRecorder $recorder Recorder used to record configuration changes performed by the migration.
     */
    public function up(OperationRecorder $recorder): void
    {
        $recorder->addConfig('main.contactInformationHTML', false);
        $recorder->renameConfig('main.contactInformations', 'main.contactInformation');
    }
}