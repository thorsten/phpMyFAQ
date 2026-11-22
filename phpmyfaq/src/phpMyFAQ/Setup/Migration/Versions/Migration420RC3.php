<?php

/**
 * Migration for phpMyFAQ 4.2.0-RC.3: random secret for the WebAuthn decoy credential.
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
 * @since     2026-11-18
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Setup\Installation\DatabaseSchema;
use phpMyFAQ\Setup\Installation\DefaultDataSeeder;
use phpMyFAQ\Setup\Migration\AbstractMigration;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;
use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\PostgresDialect;
use phpMyFAQ\Setup\Migration\QueryBuilder\DialectFactory;

/**
 * The WebAuthn login answers logins without a passkey with a decoy credential ID. It was keyed
 * with security.salt, which older installers derived from the public reference URL, so the decoy
 * could be computed offline and told apart from a real credential ID. It is now keyed with a
 * random secret of its own.
 *
 * The other steps repair what earlier installers and migrations left behind: indexes that were
 * created without their UNIQUE constraint or not at all, a robots.txt stored on a single line,
 * and the security.loginWithEmailAddress key that installations first set up on 3.1 never got.
 * On PostgreSQL and SQLite an installation with a table prefix gets its indexes under names
 * that carry the prefix, because these databases share index names between tables.
 * Every step checks the live state first, so the migration stays safe to re-run.
 */
readonly class Migration420RC3 extends AbstractMigration
{
    /**
     * The robots.txt default as the installer stored it up to 4.2.0-RC.2: the line breaks were
     * written as a backslash followed by "n".
     */
    private const string SINGLE_LINE_ROBOTS_TEXT = 'User-agent: *\nDisallow: /admin/\nSitemap: /sitemap.xml';

    /**
     * Indexes that have to be UNIQUE, with the columns that accept NULL.
     *
     * @var array<string, array{table: string, columns: list<string>, nullable: list<string>}>
     */
    private const array UNIQUE_INDEXES = [
        'idx_api_key_unique' => [
            'table' => 'faqapi_keys',
            'columns' => ['api_key'],
            'nullable' => [],
        ],
        'idx_push_endpoint_hash' => [
            'table' => 'faqpush_subscriptions',
            'columns' => ['endpoint_hash'],
            'nullable' => [],
        ],
        'idx_faquserdata_keycloak_sub' => [
            'table' => 'faquserdata',
            'columns' => ['keycloak_sub'],
            'nullable' => ['keycloak_sub'],
        ],
        'idx_faquserdata_entra_oid' => [
            'table' => 'faquserdata',
            'columns' => ['entra_oid'],
            'nullable' => ['entra_oid'],
        ],
        'idx_faquserdata_oidc_identity' => [
            'table' => 'faquserdata',
            'columns' => ['oidc_iss', 'oidc_sub'],
            'nullable' => ['oidc_iss', 'oidc_sub'],
        ],
    ];

    public function getVersion(): string
    {
        return '4.2.0-RC.3';
    }

    public function getDependencies(): array
    {
        return ['4.2.0-RC.2'];
    }

    public function getDescription(): string
    {
        return (
            'Add the random security.webAuthnDecoySecret configuration, enforce the unique indexes '
            . 'fresh installations created as plain ones, add the admin log hash index, repair the '
            . 'single-line robots.txt default, add the missing security.loginWithEmailAddress '
            . 'configuration, and name the indexes of prefixed PostgreSQL and SQLite installations '
            . 'after their table prefix'
        );
    }

    /**
     * Configuration::add() skips keys that already exist, so a secret created on first use by
     * AuthWebAuthn before the update ran is kept.
     */
    public function up(OperationRecorder $recorder): void
    {
        $recorder->addConfig('security.webAuthnDecoySecret', bin2hex(random_bytes(32)));

        foreach (self::UNIQUE_INDEXES as $indexName => $index) {
            $this->enforceUniqueIndex($recorder, $indexName, $index['table'], $index['columns'], $index['nullable']);
        }

        $this->createPrefixedIndexes($recorder);
        $this->createAdminLogHashIndex($recorder);

        $this->repairSingleLineRobotsText($recorder);
        $this->addLoginWithEmailAddressConfig($recorder);
    }

    /**
     * Where index names carry the table prefix, createPrefixedIndexes() has created it already.
     */
    private function createAdminLogHashIndex(OperationRecorder $recorder): void
    {
        if ($this->indexNamesCarryTablePrefix()) {
            return;
        }

        if ($this->hasIndex('faqadminlog', 'idx_hash')) {
            return;
        }

        $recorder->addSql($this->createIndex('faqadminlog', 'idx_hash', 'hash'), 'Create hash index on faqadminlog');
    }

    /**
     * Installations made with 4.2 on PostgreSQL, SQLite and SQL Server got plain indexes where
     * the schema asks for unique ones, and no installer created the Keycloak and Entra ID
     * indexes at all. Rows are never deleted: when existing duplicates would violate the
     * constraint, the index is left as it is and the skipped step is logged.
     *
     * @param list<string> $columns
     * @param list<string> $nullableColumns
     */
    private function enforceUniqueIndex(
        OperationRecorder $recorder,
        string $indexName,
        string $table,
        array $columns,
        array $nullableColumns,
    ): void {
        $tableName = $this->table($table);
        $dialect = DialectFactory::create();
        $hasUnprefixedIndex = $this->hasUnprefixedIndex($table, $indexName);

        if ($this->hasUniqueIndex($table, $indexName)) {
            if ($hasUnprefixedIndex) {
                $recorder->addSql(
                    $dialect->dropIndex($indexName, $tableName),
                    sprintf('Drop the index %s on %s that lacks the table prefix', $indexName, $table),
                );
            }

            return;
        }

        if ($this->hasDuplicates($tableName, $columns)) {
            $this->configuration
                ->getLogger()
                ->warning(sprintf(
                    'Update to 4.2.0-RC.3: the unique index %s on %s (%s) was not created because the table '
                    . 'holds duplicate values. Remove the duplicates and run the update again.',
                    $indexName,
                    $tableName,
                    implode(', ', $columns),
                ));

            return;
        }

        if ($hasUnprefixedIndex) {
            $recorder->addSql(
                $dialect->dropIndex($indexName, $tableName),
                sprintf('Drop the index %s on %s that lacks the table prefix', $indexName, $table),
            );
        }

        $prefixedIndexName = $this->indexName($indexName);

        if ($this->hasIndex($table, $indexName)) {
            $recorder->addSql(
                $dialect->dropIndex($prefixedIndexName, $tableName),
                sprintf('Drop the plain index %s on %s', $prefixedIndexName, $table),
            );
        }

        $recorder->addSql(
            $dialect->createUniqueIndex($prefixedIndexName, $tableName, $columns, $nullableColumns),
            sprintf('Create unique index %s on %s', $prefixedIndexName, $table),
        );
    }

    /**
     * PostgreSQL and SQLite share index names between the tables of a schema or database file,
     * so the indexes of an installation with a table prefix carry that prefix in their name.
     */
    private function indexNamesCarryTablePrefix(): bool
    {
        return $this->indexName(indexName: 'idx') !== 'idx';
    }

    /**
     * Up to 4.2.0-RC.2 every installation asked for the same index names. A second installation
     * with another table prefix in the same PostgreSQL schema or SQLite file could not create
     * its indexes: the installer stopped at the first one, and the migrations, which use
     * IF NOT EXISTS, skipped theirs without a word. Every index of the schema is created under
     * its prefixed name here, and an index of this installation that still has the shared name
     * is dropped, which frees that name for the installation without a prefix.
     *
     * The unique indexes are left to enforceUniqueIndex(), which has to look at the rows first.
     * An index on a table or column this installation does not have yet is left to the schema
     * convergence that runs after the migrations.
     */
    private function createPrefixedIndexes(OperationRecorder $recorder): void
    {
        if (!$this->indexNamesCarryTablePrefix()) {
            return;
        }

        $dialect = DialectFactory::create();

        foreach (new DatabaseSchema($dialect)->getAllTables() as $table => $tableBuilder) {
            foreach ($tableBuilder->getIndexes() as $indexName => $index) {
                if ($index['unique']) {
                    continue;
                }

                if (!$this->hasColumns($table, $index['columns'])) {
                    continue;
                }

                if ($this->hasUnprefixedIndex($table, $indexName)) {
                    $recorder->addSql(
                        $dialect->dropIndex($indexName, $this->table($table)),
                        sprintf('Drop the index %s on %s that lacks the table prefix', $indexName, $table),
                    );
                }

                if ($this->hasIndex($table, $indexName)) {
                    continue;
                }

                $recorder->addSql(
                    $this->createIndex($table, $indexName, $index['columns']),
                    sprintf('Create index %s on %s', $this->indexName($indexName), $table),
                );
            }
        }
    }

    /**
     * @param string[] $columns
     */
    private function hasColumns(string $table, array $columns): bool
    {
        return array_all($columns, fn(string $column): bool => $this->columnExists($table, $column));
    }

    /**
     * Whether a table of this installation has an index under the name that every installation
     * in the schema or database file used to share. The catalog query is bound to the table, so
     * the index of an installation with another prefix never counts.
     */
    private function hasUnprefixedIndex(string $table, string $indexName): bool
    {
        if (!$this->indexNamesCarryTablePrefix()) {
            return false;
        }

        $db = $this->configuration->getDb();
        $tableName = $db->escape($this->table($table));
        $indexName = $db->escape($indexName);

        $catalogQuery = $this->isPostgreSql()
            ? sprintf(
                'SELECT COUNT(*) AS idx_count FROM pg_indexes '
                . "WHERE schemaname = current_schema() AND tablename = '%s' AND indexname = '%s'",
                PostgresDialect::catalogName($tableName),
                PostgresDialect::catalogName($indexName),
            )
            : sprintf(
                "SELECT COUNT(*) AS idx_count FROM sqlite_master WHERE type = 'index' AND tbl_name = '%s' AND name = '%s'",
                $tableName,
                $indexName,
            );

        $row = $db->fetchObject($db->query($catalogQuery));

        return $row instanceof \stdClass && (int) $row->idx_count > 0;
    }

    private function hasUniqueIndex(string $table, string $indexName): bool
    {
        $db = $this->configuration->getDb();

        $uniqueOnly = match (true) {
            $this->isMySql() => ' AND non_unique = 0',
            $this->isPostgreSql() => " AND indexdef LIKE 'CREATE UNIQUE INDEX%'",
            $this->isSqlite() => " AND sql LIKE 'CREATE UNIQUE INDEX%'",
            default => ' AND is_unique = 1',
        };

        $row = $db->fetchObject($db->query($this->indexExists($table, $indexName) . $uniqueOnly));

        return $row instanceof \stdClass && (int) $row->idx_count > 0;
    }

    /**
     * Rows holding NULL in one of the columns never collide, on any database.
     *
     * @param list<string> $columns
     */
    private function hasDuplicates(string $tableName, array $columns): bool
    {
        $db = $this->configuration->getDb();
        $columnList = implode(', ', $columns);
        $notNull = implode(' AND ', array_map(static fn(string $column): string => $column . ' IS NOT NULL', $columns));

        $result = $db->query(sprintf(
            'SELECT COUNT(*) AS duplicate_count FROM ('
            . 'SELECT %s FROM %s WHERE %s GROUP BY %s HAVING COUNT(*) > 1'
            . ') duplicates',
            $columnList,
            $tableName,
            $notNull,
            $columnList,
        ));
        $row = $db->fetchObject($result);

        return $row instanceof \stdClass && (int) $row->duplicate_count > 0;
    }

    /**
     * Only the untouched installer default is replaced; a robots.txt an administrator edited
     * is never the single-line literal.
     */
    private function repairSingleLineRobotsText(OperationRecorder $recorder): void
    {
        if ($this->getConfig('seo.contentRobotsText') !== self::SINGLE_LINE_ROBOTS_TEXT) {
            return;
        }

        $recorder->updateConfig('seo.contentRobotsText', DefaultDataSeeder::ROBOTS_TEXT);
    }

    /**
     * 3.1 stored the setting as main.loginWithEmailAddress and the 3.2.0-alpha migration renamed
     * the new key onto itself, so installations first set up on 3.1 have no
     * security.loginWithEmailAddress. An old key that is still there keeps its value.
     */
    private function addLoginWithEmailAddressConfig(OperationRecorder $recorder): void
    {
        $storedKeys = $this->configuration->getAll();

        if (array_key_exists('security.loginWithEmailAddress', $storedKeys)) {
            return;
        }

        if (array_key_exists('main.loginWithEmailAddress', $storedKeys)) {
            $recorder->renameConfig('main.loginWithEmailAddress', 'security.loginWithEmailAddress');
            return;
        }

        $recorder->addConfig('security.loginWithEmailAddress', 'false');
    }
}
