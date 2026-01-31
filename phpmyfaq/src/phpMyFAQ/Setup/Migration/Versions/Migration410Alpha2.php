<?php

/**
 * Migration for phpMyFAQ 4.1.0-alpha.2.
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

use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Setup\Migration\AbstractMigration;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;

readonly class Migration410Alpha2 extends AbstractMigration
{
    public function getVersion(): string
    {
        return '4.1.0-alpha.2';
    }

    public function getDependencies(): array
    {
        return ['4.1.0-alpha'];
    }

    public function getDescription(): string
    {
        return 'Admin session timeout counter, OpenSearch config, FAQ translate permission';
    }

    public function up(OperationRecorder $recorder): void
    {
        $recorder->addConfig('security.enableAdminSessionTimeoutCounter', true);
        $recorder->addConfig('search.enableOpenSearch', false);

        // Add new permission to translate FAQs
        $recorder->grantPermission(PermissionType::FAQ_TRANSLATE->value, 'Right to translate FAQs');
    }
}
