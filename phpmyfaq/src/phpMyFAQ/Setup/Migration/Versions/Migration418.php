<?php

/**
 * Migration for phpMyFAQ 4.1.8.
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

use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Setup\Migration\AbstractMigration;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;

readonly class Migration418 extends AbstractMigration
{
    public function getVersion(): string
    {
        return '4.1.8';
    }

    public function getDependencies(): array
    {
        return ['4.1.3'];
    }

    public function getDescription(): string
    {
        return 'Restore the add_faq permission for upgraded installations';
    }

    /**
     * PermissionType::FAQ_ADD was renamed from 'addfaq' to 'add_faq' in v4.0.15 without a
     * matching migration, so upgraded installations lost the "Add new FAQ" permission.
     */
    public function up(OperationRecorder $recorder): void
    {
        $recorder->renamePermission('addfaq', PermissionType::FAQ_ADD->value);
    }
}
