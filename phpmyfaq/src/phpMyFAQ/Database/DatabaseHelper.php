<?php

/**
 * Helper class for database drivers.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2012-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-04-12
 */

namespace phpMyFAQ\Database;

use phpMyFAQ\Configuration;
use phpMyFAQ\Strings;

/**
 * Class Helper
 *
 * @package phpMyFAQ\Database
 */
class DatabaseHelper
{
    /**
     * Constructor.
     */
    public function __construct(private readonly Configuration $config)
    {
    }

    /**
     * Align the prefix of the table name used in the PMF backup file,
     * from the (old) value of the system upon which the backup was performed
     * to the (new) prefix of the system upon which the backup will be restored
     * This alignment will be performed upon all the SQL query "patterns"
     * provided within the PMF backup file.
     *
     *
     */
    public static function alignTablePrefix(string $query, string $oldValue, string $newValue): string
    {
        // Align DELETE FROM <prefix.tablename>
        $query = self::alignTablePrefixByPattern($query, 'DELETE FROM', $oldValue, $newValue);
        // Align INSERT INTO <prefix.tablename>
        return self::alignTablePrefixByPattern($query, 'INSERT INTO', $oldValue, $newValue);
    }

    /**
     * Align the prefix of the table name used in the PMF backup file,
     * from the (old) value of the system upon which the backup was performed
     * to the (new) prefix of the system upon which the backup will be restored.
     * This alignment will be performed ONLY upon those given SQL queries starting
     * with the given pattern.
     *
     *
     */
    private static function alignTablePrefixByPattern(
        string $query,
        string $startPattern,
        string $oldValue,
        string $newValue
    ): string {
        $return = $query;
        $matches = [];

        Strings::preg_match_all('/^' . $startPattern . "\s+(\w+)(\s+|$)/i", $query, $matches);

        if (isset($matches[1][0])) {
            $oldTableFullName = $matches[1][0];
            $newTableFullName = $newValue . Strings::substr($oldTableFullName, Strings::strlen($oldValue));
            $return = str_replace($oldTableFullName, $newTableFullName, $query);
        }

        return $return;
    }

    /**
     * This function builds the queries for the backup.
     *
     * @return string[]
     */
    public function buildInsertQueries(string $query, string $table): array
    {
        if (!$result = $this->config->getDb()->query($query)) {
            return [];
        }
        $ret = [];

        $ret[] = "\r\n-- Table: " . $table;

        while ($row = $this->config->getDb()->fetchArray($result)) {
            $p1 = [];
            $p2 = [];
            foreach ($row as $key => $val) {
                if (is_int($key)) {
                    continue; // Fix for SQLite3
                }
                $p1[] = $key;
                if ('rights' != $key && is_numeric($val)) {
                    $p2[] = $val;
                } else {
                    if (is_null($val)) {
                        $p2[] = 'NULL';
                    } else {
                        $p2[] = sprintf("'%s'", $this->config->getDb()->escape($val));
                    }
                }
            }
            $ret[] = 'INSERT INTO ' . $table . ' (' . implode(',', $p1) . ') VALUES (' . implode(',', $p2) . ');';
        }

        return $ret;
    }
}
