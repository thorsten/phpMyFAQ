<?php

/**
 * Names the index of a table in an installation that uses a table prefix.
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
 * @since     2026-11-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\QueryBuilder;

/**
 * PostgreSQL and SQLite keep the index names of a schema, or of a database file, in one
 * namespace. Two installations that share it and differ only in their table prefix would
 * both ask for "idx_hash": the second one fails, or with IF NOT EXISTS silently gets no index.
 * On these databases the index name therefore carries the table prefix, in the form the
 * PostgreSQL installer used up to 4.1 ("idx_records_" followed by the prefix).
 *
 * MySQL, MariaDB and SQL Server scope an index name to its table, and an installation
 * without a prefix has nothing to tell apart: both keep the plain name.
 */
final class IndexName
{
    private const array TYPES_WITH_SHARED_INDEX_NAMES = ['pgsql', 'pdo_pgsql', 'sqlite3', 'pdo_sqlite'];

    public static function forTablePrefix(string $indexName, string $databaseType, string $tablePrefix): string
    {
        if ($tablePrefix === '') {
            return $indexName;
        }

        if (!in_array($databaseType, self::TYPES_WITH_SHARED_INDEX_NAMES, strict: true)) {
            return $indexName;
        }

        return $indexName . '_' . $tablePrefix;
    }
}
