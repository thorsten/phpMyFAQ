<?php

/**
 * The PMF_DB_Sqlite3 class provides methods and functions for a SQLite v3
 * database.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2012-03-02
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_DB_Sqlite3.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2012-03-02
 */
class PMF_DB_Sqlite3 implements PMF_DB_Driver
{
    /**
     * The connection object.
     *
     * @var SQLite3
     */
    private $conn = false;

    /**
     * The query log string.
     *
     * @var string
     *
     * @see   query()
     */
    private $sqllog = '';

    /**
     * Tables.
     *
     * @var array
     */
    public $tableNames = [];

    /**
     * Connects to the database.
     *
     * @param string $host
     * @param string
     * @param string
     * @param string
     *
     * @return bool
     */
    public function connect($host, $user, $passwd, $db = '')
    {
        $this->conn = new SQLite3($host);
        if (!$this->conn) {
            PMF_Db::errorPage($this->conn->lastErrorMsg());
            die();
        }

        return true;
    }

    /**
     * This function sends a query to the database.
     *
     * @param string $query
     * @param int    $offset
     * @param int    $rowcount
     *
     * @return mixed $result
     */
    public function query($query, $offset = 0, $rowcount = 0)
    {
        if (DEBUG) {
            $this->sqllog .= PMF_Utils::debug($query);
        }

        if (0 < $rowcount) {
            $query .= sprintf(' LIMIT %d,%d', $offset, $rowcount);
        }

        $result = $this->conn->query($query);

        if (!$result) {
            $this->sqllog .= $this->error();
        }

        return $result;
    }

    /**
     * Escapes a string for use in a query.
     *
     * @param   string
     *
     * @return string
     */
    public function escape($string)
    {
        return SQLite3::escapeString($string);
    }

    /**
     * Fetch a result row as an object.
     *
     * @param mixed $result
     *
     * @return object or NULL if there are no more results
     */
    public function fetchObject($result)
    {
        $result->fetchedByPMF = true;
        $return = $result->fetchArray(SQLITE3_ASSOC);

        return $return
            ? (object) $return
            : null;
    }

    /**
     * Fetch a result row as an array.
     *
     * @param SQLite3Result $result
     *
     * @return array
     */
    public function fetchArray($result)
    {
        $result->fetchedByPMF = true;

        return $result->fetchArray();
    }

    /**
     * Fetch a result row as an associate array.
     *
     * @param SQLite3Result $result
     *
     * @return array
     */
    public function fetchAssoc($result)
    {
        $result->fetchedByPMF = true;

        return $result->fetchArray(SQLITE3_ASSOC);
    }

    /**
     * Fetches a complete result as an object.
     *
     * @param resource $result Resultset
     *
     * @throws Exception
     *
     * @return array of stdClass
     */
    public function fetchAll($result)
    {
        $ret = [];
        if (false === $result) {
            throw new Exception('Error while fetching result: '.$this->error());
        }

        $result->fetchedByPMF = true;
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $ret[] = (object) $row;
        }

        return $ret;
    }

    /**
     * Number of rows in a result.
     *
     * @param SQLite3Result $result
     *
     * @return int
     */
    public function numRows($result)
    {
        !isset($result->fetchedByPMF) || !$result->fetchedByPMF || die("Do not call numRows() after you've fetched one or more result records, because PMF_DB_Sqlite3::numRows() has to reset the resultset at its end.");
        $numberOfRows = 0;
        while ($result->fetchArray(SQLITE3_NUM)) {
            ++$numberOfRows;
        }
        $result->reset();

        return $numberOfRows;
    }

    /**
     * Logs the queries.
     *
     * @return int
     */
    public function log()
    {
        return $this->sqllog;
    }

    /**
     * This function returns the table status.
     *
     * @param string $prefix Table prefix
     *
     * @return array
     */
    public function getTableStatus($prefix = '')
    {
        $arr = [];

        $result = $this->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
        while ($row = $this->fetchAssoc($result)) {
            $num_result = $this->query('SELECT * FROM '.$row['name']);
            $arr[$row['name']] = $this->numRows($num_result);
        }

        return $arr;
    }

    /**
     * Returns an array with all table names.
     *
     * @todo Have to be refactored because of https://github.com/thorsten/phpMyFAQ/issues/965
     *
     * @param string $prefix Table prefix
     *
     * @return array
     */
    public function getTableNames($prefix = '')
    {
        return $this->tableNames = [
            $prefix.'faqadminlog',
            $prefix.'faqattachment',
            $prefix.'faqattachment_file',
            $prefix.'faqcaptcha',
            $prefix.'faqcategories',
            $prefix.'faqcategory_group',
            $prefix.'faqcategory_user',
            $prefix.'faqcategoryrelations',
            $prefix.'faqchanges',
            $prefix.'faqcomments',
            $prefix.'faqconfig',
            $prefix.'faqdata',
            $prefix.'faqdata_group',
            $prefix.'faqdata_revisions',
            $prefix.'faqdata_tags',
            $prefix.'faqdata_user',
            $prefix.'faqglossary',
            $prefix.'faqgroup',
            $prefix.'faqgroup_right',
            $prefix.'faqinstances',
            $prefix.'faqinstances_config',
            $prefix.'faqnews',
            $prefix.'faqquestions',
            $prefix.'faqright',
            $prefix.'faqsearches',
            $prefix.'faqsessions',
            $prefix.'faqstopwords',
            $prefix.'faqtags',
            $prefix.'faquser',
            $prefix.'faquser_group',
            $prefix.'faquser_right',
            $prefix.'faquserdata',
            $prefix.'faquserlogin',
            $prefix.'faqvisits',
            $prefix.'faqvoting',
        ];
    }

    /**
     * Returns the next ID of a table.
     *
     * @param   string      the name of the table
     * @param   string      the name of the ID column
     *
     * @return int
     */
    public function nextId($table, $id)
    {
        $result = (int) $this->conn->querySingle(
            sprintf(
                'SELECT max(%s) AS current_id FROM %s',
                $id,
                $table
            )
        );

        return ($result + 1);
    }

    /**
     * Returns the error string.
     *
     * @return string
     */
    public function error()
    {
        if (0 === $this->conn->lastErrorCode()) {
            return '';
        }

        return $this->conn->lastErrorMsg();
    }

    /**
     * Returns the libary version string.
     *
     * @return string
     */
    public function clientVersion()
    {
        $version = $this->conn->version();

        return $version['versionString'];
    }

    /**
     * Returns the libary version string.
     *
     * @return string
     */
    public function serverVersion()
    {
        return $this->clientVersion();
    }

    /**
     * Closes the connection to the database.
     *
     * @return bool
     */
    public function close()
    {
        return $this->conn->close();
    }

    /**
     * @return string
     */
    public function now()
    {
        return "DATETIME('now', 'localtime')";
    }
}
