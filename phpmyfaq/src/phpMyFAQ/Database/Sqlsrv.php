<?php

/**
 * The phpMyFAQ\Db_Sqlsrv class provides methods and functions for SQL Server Driver
 * for PHP from Microsoft for Microsoft SQL Server 2012 or later.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-02-18
 */

namespace phpMyFAQ\Database;

use Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Utils;

/**
 * Class Sqlsrv
 *
 * @package phpMyFAQ\Database
 */
class Sqlsrv implements DatabaseDriver
{
    /**
     * Tables.
     *
     * @var array
     */
    public $tableNames = [];

    /**
     * @var resource
     */
    private $conn = false;
    /**
     * The query log string.
     *
     * @var string
     */
    private $sqllog = '';
    /**
     * Connection options array.
     *
     * @var array
     */
    private $connectionOptions = [];

    /**
     * Connects to the database.
     *
     * This function connects to a MySQL database
     *
     * @param string $host A string specifying the name of the server to which a connection is being established
     * @param string $user Specifies the User ID to be used when connecting with SQL Server Authentication
     * @param string $password Specifies the password associated with the User ID to be used when connecting with
     *                         SQL Server Authentication
     * @param string $database Specifies the name of the database in use for the connection being established
     * @param int|null $port
     * @return bool true, if connected, otherwise false
     */
    public function connect(string $host, string $user, string $password, $database = '', $port = 1433): ?bool
    {
        $this->setConnectionOptions($user, $password, $database);

        $this->conn = sqlsrv_connect($host . ', ' . $port, $this->connectionOptions);
        if (!$this->conn) {
            Database::errorPage((string)sqlsrv_errors());
            die();
        }

        return true;
    }

    /**
     * Sets the connection options.
     *
     * @param string $user Specifies the User ID to be used when connecting with SQL Server Authentication
     * @param string $password Specifies the password associated with the User ID to be used when connecting with
     *                         SQL Server Authentication
     * @param string $database Specifies the name of the database in use for the connection being established
     */
    private function setConnectionOptions(string $user, string $password, string $database)
    {
        $this->connectionOptions = [
            'UID' => $user,
            'PWD' => $password,
            'Database' => $database,
            'CharacterSet' => 'UTF-8',
        ];
    }

    /**
     * Escapes a string for use in a query.
     *
     * @param string $string String
     *
     * @return string
     */
    public function escape($string): string
    {
        return str_replace("'", "''", $string);
    }

    /**
     * Fetch a result row as an assoc array.
     *
     * @param resource $result Resultset
     *
     * @return array
     */
    public function fetchArray($result): ?array
    {
        return sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
    }

    /**
     * Fetch a result row.
     * @param $result
     * @return false|mixed
     */
    public function fetchRow($result)
    {
        return $this->fetchArray($result)[0];
    }

    /**
     * Fetches a complete result as an object.
     *
     * @param resource $result Resultset
     *
     * @return array
     * @throws Exception
     */
    public function fetchAll($result): ?array
    {
        $ret = [];
        if (false === $result) {
            throw new Exception('Error while fetching result: ' . $this->error());
        }

        while ($row = $this->fetchObject($result)) {
            $ret[] = $row;
        }

        return $ret;
    }

    /**
     * Returns the error string.
     *
     * @return string
     */
    public function error(): string
    {
        $errors = sqlsrv_errors();

        if (null !== $errors) {
            return $errors[0]['SQLSTATE'] . ': ' . $errors[0]['message'];
        }

        return '';
    }

    /**
     * Fetch a result row as an object.
     *
     * @param resource $result Results
     */
    public function fetchObject($result)
    {
        return sqlsrv_fetch_object($result);
    }

    /**
     * Number of rows in a result.
     *
     * @param resource $result Resultset
     *
     * @return int
     */
    public function numRows($result): int
    {
        return sqlsrv_num_rows($result);
    }

    /**
     * Logs the queries.
     *
     * @return string
     */
    public function log(): string
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
    public function getTableStatus($prefix = ''): array
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
                AND obj.xtype = 'U'
            ORDER BY obj.name";
        $result = $this->query($query);

        while ($row = $this->fetchObject($result)) {
            $tables[$row->table_name] = $row->table_rows;
        }

        return $tables;
    }

    /**
     * This function sends a query to the database.
     *
     * @param string $query
     * @param int $offset
     * @param int $rowcount
     *
     * @return mixed $result
     */
    public function query(string $query, $offset = 0, $rowcount = 0)
    {
        if (DEBUG) {
            $this->sqllog .= Utils::debug($query);
        }

        $options = array('Scrollable' => SQLSRV_CURSOR_KEYSET);

        if (0 < $rowcount) {
            $query .= sprintf(' OFFSET %d ROWS FETCH NEXT %d ROWS ONLY', $offset, $rowcount);
        }

        $result = sqlsrv_query($this->conn, $query, [], $options);

        if (!$result) {
            $this->sqllog .= $this->error();
        }

        return $result;
    }

    /**
     * Returns the next ID of a table.
     *
     * @param string $table the name of the table
     * @param string $id    the name of the ID column
     *
     * @return int
     */
    public function nextId($table, $id): int
    {
        $select = sprintf(
            '
           SELECT 
               max(%s) as current_id
           FROM 
               %s',
            $id,
            $table
        );

        $result = $this->query($select);
        sqlsrv_fetch($result);

        return (sqlsrv_get_field($result, 0) + 1);
    }

    /**
     * Returns the library version string.
     *
     * @return string
     */
    public function clientVersion(): string
    {
        $client_info = sqlsrv_client_info($this->conn);

        return $client_info['DriverODBCVer'] . ' ' . $client_info['DriverVer'];
    }

    /**
     * Returns the library version string.
     *
     * @return string
     */
    public function serverVersion(): string
    {
        $server_info = sqlsrv_server_info($this->conn);

        return $server_info['SQLServerVersion'];
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
    public function getTableNames($prefix = ''): array
    {
        return $this->tableNames = [
            $prefix . 'faqadminlog',
            $prefix . 'faqattachment',
            $prefix . 'faqattachment_file',
            $prefix . 'faqcaptcha',
            $prefix . 'faqcategories',
            $prefix . 'faqcategoryrelations',
            $prefix . 'faqcategory_group',
            $prefix . 'faqcategory_news',
            $prefix . 'faqcategory_order',
            $prefix . 'faqcategory_user',
            $prefix . 'faqchanges',
            $prefix . 'faqcomments',
            $prefix . 'faqconfig',
            $prefix . 'faqdata',
            $prefix . 'faqdata_group',
            $prefix . 'faqdata_revisions',
            $prefix . 'faqdata_tags',
            $prefix . 'faqdata_user',
            $prefix . 'faqglossary',
            $prefix . 'faqgroup',
            $prefix . 'faqgroup_right',
            $prefix . 'faqinstances',
            $prefix . 'faqinstances_config',
            $prefix . 'faqmeta',
            $prefix . 'faqnews',
            $prefix . 'faqquestions',
            $prefix . 'faqright',
            $prefix . 'faqsearches',
            $prefix . 'faqsections',
            $prefix . 'faqsection_category',
            $prefix . 'faqsection_group',
            $prefix . 'faqsection_news',
            $prefix . 'faqsessions',
            $prefix . 'faqstopwords',
            $prefix . 'faqtags',
            $prefix . 'faquser',
            $prefix . 'faquserdata',
            $prefix . 'faquserlogin',
            $prefix . 'faquser_group',
            $prefix . 'faquser_right',
            $prefix . 'faqvisits',
            $prefix . 'faqvoting',
        ];
    }

    /**
     * Closes the connection to the database.
     */
    public function close()
    {
        sqlsrv_close($this->conn);
    }

    /**
     * @return string
     */
    public function now(): string
    {
        return 'GETDATE()';
    }
}
