<?php

/**
 * The phpMyFAQ\Db_Sqlsrv class provides methods and functions for SQL Server Driver
 * for PHP from Microsoft for Microsoft SQL Server 2012 or later.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
    /** @var string[] Tables */
    public array $tableNames = [];

    /** @var resource|bool */
    private $conn = false;

    /** The query log string. */
    private string $sqllog = '';

    /**
     * Connection options array.
     */
    private array $connectionOptions = [];

    /**
     * Connects to the database.
     * This function connects to a MySQL database
     *
     * @param string   $host A string specifying the name of the server to which a connection is being established
     * @param string   $user Specifies the User ID to be used when connecting with SQL Server Authentication
     * @param string   $password Specifies the password associated with the User ID to be used when connecting with
     *                         SQL Server Authentication
     * @param string   $database Specifies the name of the database in use for the connection being established
     * @param int|null $port
     * @return bool|null true, if connected, otherwise false
     */
    public function connect(
        string $host,
        string $user,
        string $password,
        string $database = '',
        int $port = null
    ): ?bool {
        $this->setConnectionOptions($user, $password, $database);

        $this->conn = sqlsrv_connect($host . ', ' . $port, $this->connectionOptions);
        if (!$this->conn) {
            Database::errorPage($this->formatErrors(sqlsrv_errors()));
            die();
        }

        return true;
    }

    /**
     * Escapes a string for use in a query.
     *
     * @param string $string String
     */
    public function escape(string $string): string
    {
        return str_replace("'", "''", $string);
    }

    /**
     * Fetch a result row as an assoc array.
     *
     * @param mixed $result Resultset
     */
    public function fetchArray(mixed $result): ?array
    {
        $fetchedData = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);

        return is_array($fetchedData) ? $fetchedData : [];
    }

    /**
     * Fetch a result row.
     */
    public function fetchRow(mixed $result): mixed
    {
        return $this->fetchArray($result)[0];
    }

    /**
     * Fetches a complete result as an object.
     *
     * @param mixed $result Resultset
     * @throws Exception
     */
    public function fetchAll(mixed $result): ?array
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
     * @param mixed $result Results
     */
    public function fetchObject(mixed $result): mixed
    {
        return sqlsrv_fetch_object($result);
    }

    /**
     * Number of rows in a result.
     *
     * @param mixed $result Resultset
     */
    public function numRows(mixed $result): int
    {
        return sqlsrv_num_rows($result);
    }

    /**
     * Logs the queries.
     */
    public function log(): string
    {
        return $this->sqllog;
    }

    /**
     * This function returns the table status.
     *
     * @param string $prefix Table prefix
     */
    public function getTableStatus(string $prefix = ''): array
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
     *
     * @return mixed $result
     */
    public function query(string $query, int $offset = 0, int $rowcount = 0): mixed
    {
        $this->sqllog .= Utils::debug($query);

        $options = ['Scrollable' => SQLSRV_CURSOR_KEYSET];

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
     */
    public function nextId(string $table, string $id): int
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
     */
    public function clientVersion(): string
    {
        $client_info = sqlsrv_client_info($this->conn);

        return $client_info['DriverODBCVer'] . ' ' . $client_info['DriverVer'];
    }

    /**
     * Returns the library version string.
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
     */
    public function getTableNames(string $prefix = ''): array
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
    public function close(): void
    {
        sqlsrv_close($this->conn);
    }

    public function now(): string
    {
        return 'GETDATE()';
    }

    /**
     * Sets the connection options.
     *
     * @param string $user Specifies the User ID to be used when connecting with SQL Server Authentication
     * @param string $password Specifies the password associated with the User ID to be used when connecting with
     *                         SQL Server Authentication
     * @param string $database Specifies the name of the database in use for the connection being established
     */
    private function setConnectionOptions(string $user, string $password, string $database): void
    {
        $this->connectionOptions = [
            'UID' => $user,
            'PWD' => $password,
            'Database' => $database,
            'CharacterSet' => 'UTF-8',
            'TrustServerCertificate' => true, // even trust self-signed certificates
        ];
    }

    /**
     * Formats the error output
     */
    private function formatErrors(array $errors): string
    {
        $error = '<h3>SQL Error:</h3>' . 'MS SQL Error information: <br/>';
        foreach ($errors as $error) {
            $error .= sprintf(
                'SQLSTATE: %s<br/>Code: %s<br/>Message: %s<br/>',
                $error['SQLSTATE'],
                $error['code'],
                $error['message']
            );
        }

        return $error;
    }
}
