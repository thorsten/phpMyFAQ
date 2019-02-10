<?php

/**
 * Helper class for database drivers.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2012-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-04-12
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_DB_Helper.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2012-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-04-12
 */
class PMF_DB_Helper
{
    /**
     * @var PMF_Configuration
     */
    private $config = null;

    /**
     * Constructor.
     *
     * @param PMF_Configuration $config
     *
     * @return PMF_DB_Helper
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * This function builds the the queries for the backup.
     *
     * @param string $query
     * @param string $table
     *
     * @return array
     */
    public function buildInsertQueries($query, $table)
    {
        if (!$result = $this->config->getDb()->query($query)) {
            [];
        }
        $ret = [];

        $ret[] = "\r\n-- Table: ".$table;

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
            $ret[] = 'INSERT INTO '.$table.' ('.implode(',', $p1).') VALUES ('.implode(',', $p2).');';
        }

        return $ret;
    }

    /**
     * Align the prefix of the table name used in the PMF backup file,
     * from the (old) value of the system upon which the backup was performed
     * to the (new) prefix of the system upon which the backup will be restored
     * This alignment will be performed upon all of the SQL query "patterns"
     * provided within the PMF backup file.
     *
     * @param string $query
     * @param string $oldValue
     * @param string $newValue
     *
     * @return string
     */
    public static function alignTablePrefix($query, $oldValue, $newValue)
    {
        // Align DELETE FROM <prefix.tablename>
        $query = self::alignTablePrefixByPattern($query, 'DELETE FROM', $oldValue, $newValue);
        // Align INSERT INTO <prefix.tablename>
        $query = self::alignTablePrefixByPattern($query, 'INSERT INTO', $oldValue, $newValue);

        return $query;
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
    private static function alignTablePrefixByPattern($query, $startPattern, $oldValue, $newValue)
    {
        $return = $query;
        $matches = [];

        PMF_String::preg_match_all('/^'.$startPattern."\s+(\w+)(\s+|$)/i", $query, $matches);

        if (isset($matches[1][0])) {
            $oldTableFullName = $matches[1][0];
            $newTableFullName = $newValue.PMF_String::substr($oldTableFullName, PMF_String::strlen($oldValue));
            $return = str_replace($oldTableFullName, $newTableFullName, $query);
        }

        return $return;
    }
}
