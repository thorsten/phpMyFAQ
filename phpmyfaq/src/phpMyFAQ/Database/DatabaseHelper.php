<?php

/**
 * Helper class for database drivers.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2012-2022 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2012-04-12
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
     * @var Configuration
     */
    private $config;

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Align the prefix of the table name used in the PMF backup file,
     * from the (old) value of the system upon which the backup was performed
     * to the (new) prefix of the system upon which the backup will be restored
     * This alignment will be performed upon all the SQL query "patterns"
     * provided within the PMF backup file.
     *
     * @param string $query
     * @param string $oldValue
     * @param string $newValue
     *
     * @return string
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
     * @param string $query
     * @param string $startPattern
     * @param string $oldValue
     * @param string $newValue
     *
     * @return string
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
     * @param string $query
     * @param string $table
     * @return array
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
