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
    /**
     * Provide the migration's version identifier.
     *
     * @return string The migration version identifier (e.g. "4.1.0-alpha.2").
     */
    public function getVersion(): string
    {
        return '4.1.0-alpha.2';
    }

    /**
     * Migration versions that must be applied before this migration.
     *
     * @return string[] Array of version identifiers this migration depends on.
     */
    public function getDependencies(): array
    {
        return ['4.1.0-alpha'];
    }

    /**
     * Provide a short human-readable description of this migration.
     *
     * @return string A concise summary of the migration's changes.
     */
    public function getDescription(): string
    {
        return 'Admin session timeout counter, OpenSearch config, FAQ translate permission';
    }

    /**
     * Apply configuration and permission changes introduced by this migration.
     *
     * @param OperationRecorder $recorder Recorder used to record configuration entries and permission grants to apply during the migration.
     */
    public function up(OperationRecorder $recorder): void
    {
        $recorder->addConfig('security.enableAdminSessionTimeoutCounter', true);
        $recorder->addConfig('search.enableOpenSearch', false);

        // Add new permission to translate FAQs
        $recorder->grantPermission(PermissionType::FAQ_TRANSLATE->value, 'Right to translate FAQs');
    }
}