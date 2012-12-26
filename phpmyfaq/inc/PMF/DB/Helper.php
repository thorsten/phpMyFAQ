<?php
/**
 * Helper class for database drivers
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   DB
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2012-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-04-12
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_DB_Helper
 *
 * @category  phpMyFAQ
 * @package   DB
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2012-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-04-12
 */
class PMF_DB_Helper
{
    /**
     * @var PMF_Configuration
     */
    private $_config = null;

    /**
     * Constructor
     *
     * @param PMF_Configuration $config
     *
     * @return PMF_DB_Helper
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->_config   = $config;
    }

    /**
     * This function builds the the queries for the backup
     *
     * @param string $query
     * @param string $table
     *
     * @return   array
     */
    public function buildInsertQueries($query, $table)
    {
        if (!$result = $this->_config->getDb()->query($query)) {
            array();
        }
        $ret = array();

        $ret[] = "\r\n-- Table: ".$table;

        while ($row = $this->_config->getDb()->fetchArray($result)) {
            $p1 = array();
            $p2 = array();
            foreach ($row as $key => $val) {
                $p1[] = $key;
                if ('rights' != $key && is_numeric($val)) {
                    $p2[] = $val;
                } else {
                    if (is_null($val)) {
                        $p2[] = 'NULL';
                    } else {
                        $p2[] = sprintf("'%s'", $this->_config->getDb()->escape($val));
                    }
                }
            }
            $ret[] = "INSERT INTO " . $table . " (" . implode(",", $p1) . ") VALUES (" . implode(",", $p2) . ");";
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
     * @return  string
     *
     */
    public static function alignTablePrefix($query, $oldValue, $newValue)
    {
        // Align DELETE FROM <prefix.tablename>
        $query = self::alignTablePrefixByPattern($query, "DELETE FROM", $oldValue, $newValue);
        // Align INSERT INTO <prefix.tablename>
        $query = self::alignTablePrefixByPattern($query, "INSERT INTO", $oldValue, $newValue);

        return $query;
    }

    /**
     * Align the prefix of the table name used in the PMF backup file,
     * from the (old) value of the system upon which the backup was performed
     * to the (new) prefix of the system upon which the backup will be restored.
     * This alignment will be perfomed ONLY upon those given SQL queries starting
     * with the given pattern.
     *
     * @param string $query
     * @param string $startPattern
     * @param string $oldValue
     * @param string $newValue
     * 
     * @return  string
     */
    private static function alignTablePrefixByPattern($query, $startPattern, $oldValue, $newValue)
    {
        $ret = $query;

        PMF_String::preg_match_all("/^" . $startPattern . "\s+(\w+)(\s+|$)/i", $query, $matches);
        if (isset($matches[1][0])) {
            $oldtablefullname = $matches[1][0];
            $newtablefullname = $newValue . PMF_String::substr($oldtablefullname, PMF_String::strlen($oldValue));
            $ret = PMF_String::str_replace($oldtablefullname, $newtablefullname, $query);
        }

        return $ret;
    }
}