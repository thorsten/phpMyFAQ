<?php

/**
 * The PMF_DB_Mssql class provides methods and functions for Microsoft SQL
 * Server 2012 or later.
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
 * @author    Daniel Hoechst <dhoechst@petzl.com>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_DB_Mssql.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Daniel Hoechst <dhoechst@petzl.com>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 */
class PMF_DB_Mssql implements PMF_DB_Driver
{
    /**
     * The connection object.
     *
     * @var mixed
     *
     * @see   connect(), query(), close()
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
     * @param string $user
     * @param string $password
     * @param string $database
     *
     * @return bool TRUE, if connected, otherwise FALSE
     */
    public function connect($host, $user, $password, $database = '')
    {
        $this->conn = mssql_pconnect($host, $user, $password);

        if ($this->conn === false) {
            PMF_Db::errorPage(mssql_get_last_message());
            die();
        }

        if ('' !== $database) {
            return mssql_select_db($database, $this->conn);
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
            $query .= sprintf(' OFFSET %d ROWS FETCH NEXT %d ROWS ONLY', $offset, $rowcount);
        }

        $result = mssql_query($query, $this->conn);

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
        return str_replace("'", "''", $string);
    }

    /**
     * Fetch a result row as an object.
     *
     * @param mixed $result
     *
     * @return object
     */
    public function fetchObject($result)
    {
        return mssql_fetch_object($result);
    }

    /**
     * Fetch a result row as an object.
     *
     * @param mixed $result
     *
     * @return array
     */
    public function fetchArray($result)
    {
        return mssql_fetch_assoc($result);
    }

    /**
     * Fetches a complete result as an object.
     *
     * @param resource $result Resultset
     *
     * @throws Exception
     *
     * @return array
     */
    public function fetchAll($result)
    {
        $ret = [];
        if (false === $result) {
            throw new Exception('Error while fetching result: '.$this->error());
        }

        while ($row = $this->fetchObject($result)) {
            $ret[] = $row;
        }

        return $ret;
    }

    /**
     * Number of rows in a result.
     *
     * @param mixed $result
     *
     * @return int
     */
    public function numRows($result)
    {
        return mssql_num_rows($result);
    }

    /**
     * Logs the queries.
     *
     * @return string
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
        $tables = [];

        $query = "
            SELECT
                obj.name AS table_name,
                idx.rows AS table_rows
            FROM
                sysobjects obj, sysindexes idx
            WHERE
                    idx.id = OBJECT_ID(obj.name)
                AND idx.indid < 2
                AND obj.xtype = 'u'
            ORDER BY obj.name";
        $result = $this->query($query);

        while ($row = $this->fetchObject($result)) {
            if ('dtproperties' != $row->table_name) {
                $tables[$row->table_name] = $row->table_rows;
            }
        }

        return $tables;
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
        $result = $this->query('SELECT max('.$id.') as current_id FROM '.$table);
        $currentID = mssql_result($result, 0, 'current_id');

        return ($currentID + 1);
    }

    /**
     * Returns the error string.
     *
     * @return string
     */
    public function error()
    {
        $result = $this->query('SELECT @@ERROR AS ErrorCode');
        $errormsg = mssql_result($result, 0, 'ErrorCode');
        if ($errormsg != 0) {
            return $errormsg;
        }
    }

    /**
     * Returns the client version string.
     *
     * @return string
     */
    public function clientVersion()
    {
        return '';
    }

    /**
     * Returns the server version string.
     *
     * @return string
     */
    public function serverVersion()
    {
        $result = $this->query('SELECT @@version AS SERVER_VERSION');
        $version = mssql_result($result, 0, 'SERVER_VERSION');
        if (isset($version)) {
            return $version;
        }

        return 42;
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
     * Closes the connection to the database.
     *
     * @return bool
     */
    public function close()
    {
        return mssql_close($this->conn);
    }

    /**
     * @return string
     */
    public function now()
    {
        return 'GETDATE()';
    }
}
