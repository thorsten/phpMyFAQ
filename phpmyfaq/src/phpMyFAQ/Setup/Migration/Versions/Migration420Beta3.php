<?php

/**
 * Migration for phpMyFAQ 4.2.0-beta.3: generic OpenID Connect provider.
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
 * @since     2026-10-13
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Setup\Migration\AbstractMigration;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;

readonly class Migration420Beta3 extends AbstractMigration
{
    public function getVersion(): string
    {
        return '4.2.0-beta.3';
    }

    public function getDependencies(): array
    {
        return ['4.2.0-beta'];
    }

    public function getDescription(): string
    {
        return 'Add the generic OpenID Connect provider: oidc.* configuration and the issuer-bound oidc_sub/oidc_iss identity link';
    }

    /**
     * The column steps are guarded so the migration stays safe to re-run.
     */
    public function up(OperationRecorder $recorder): void
    {
        if (!$this->columnExists('faquserdata', 'oidc_sub')) {
            $recorder->addSql(
                $this->addColumn('faquserdata', 'oidc_sub', $this->varcharType(255) . ' NULL'),
                'Add oidc_sub column to faquserdata',
            );

            $recorder->addSql(
                $this->createUniqueIndex('faquserdata', 'idx_faquserdata_oidc_sub', 'oidc_sub'),
                'Create unique oidc_sub index on faquserdata',
            );
        }

        if (!$this->columnExists('faquserdata', 'oidc_iss')) {
            $recorder->addSql(
                $this->addColumn('faquserdata', 'oidc_iss', $this->varcharType(255) . ' NULL'),
                'Add oidc_iss column to faquserdata',
            );
        }

        $recorder->addConfig('oidc.enable', 'false');
        $recorder->addConfig('oidc.providerName', 'OpenID Connect');
        $recorder->addConfig('oidc.discoveryUrl', '');
        $recorder->addConfig('oidc.clientId', '');
        $recorder->addConfig('oidc.clientSecret', '');
        $recorder->addConfig('oidc.redirectUri', '');
        $recorder->addConfig('oidc.scopes', 'openid profile email');
        $recorder->addConfig('oidc.autoProvision', 'true');
        $recorder->addConfig('oidc.groupsClaim', 'groups');
        $recorder->addConfig('oidc.groupAutoAssign', 'false');
        $recorder->addConfig('oidc.groupSyncOnLogin', 'false');
        $recorder->addConfig('oidc.groupMapping', '');
        $recorder->addConfig('oidc.logoutRedirectUrl', '');
    }

    private function columnExists(string $table, string $column): bool
    {
        $tableName = $this->table($table);

        $query = match (true) {
            $this->isMySql() => sprintf("SHOW COLUMNS FROM %s LIKE '%s'", $tableName, $column),
            $this->isPostgreSql() => sprintf(
                "SELECT column_name FROM information_schema.columns WHERE table_name = '%s' AND column_name = '%s'",
                $tableName,
                $column,
            ),
            $this->isSqlite() => sprintf(
                "SELECT name FROM pragma_table_info('%s') WHERE name = '%s'",
                $tableName,
                $column,
            ),
            default => sprintf(
                "SELECT name FROM sys.columns WHERE object_id = OBJECT_ID('%s') AND name = '%s'",
                $tableName,
                $column,
            ),
        };

        $result = $this->configuration->getDb()->query($query);

        return $this->configuration->getDb()->numRows($result) > 0;
    }
}
