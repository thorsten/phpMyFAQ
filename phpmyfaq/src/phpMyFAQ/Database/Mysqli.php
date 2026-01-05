<?php

/**
 * The phpMyFAQ\Database\Mysqli class provides methods and functions for MySQL and
 * MariaDB databases.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    David Soria Parra <dsoria@gmx.net>
 * @copyright 2005-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-12-13
 */

declare(strict_types=1);

namespace phpMyFAQ\Database;

use mysqli_result;
use mysqli_sql_exception;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use SensitiveParameter;

/**
 * Class Mysqli
 *
 * @package phpMyFAQ\Database
 * @deprecated Use PDO instead. Will be removed in the v5.0 release.
 */
class Mysqli implements DatabaseDriver
{
    /**
     * @var string[] Tables.
     */
    public array $tableNames = [];

    /**
     * The connection object.
     */
    private \mysqli|bool $conn = false;

    /**
     * The query log string.
     */
    private string $sqlLog = '';

    /**
     * Connects to the database.
     *
     * @param string $host Hostname or path to socket
     * @param string $user Username
     * @param string $password Password
     * @param string $database Database name
     * @return null|bool true, if connected, otherwise false
     * @throws Exception
     */
    public function connect(
        string $host,
        #[SensitiveParameter] string $user,
        #[SensitiveParameter] string $password,
        string $database = '',
        ?int $port = null,
    ): ?bool {
        try {
            if (str_starts_with($host, '/')) {
                // Connect to MySQL via socket
                $this->conn = new \mysqli(null, $user, $password, null, $port, $host);
            } else {
                // Connect to MySQL via network
                $this->conn = new \mysqli($host, $user, $password, null, $port);
            }
        } catch (mysqli_sql_exception $mysqlisqlexception) {
            throw new Exception($mysqlisqlexception->getMessage());
        }

        if ($this->conn->connect_error) {
            Database::errorPage($this->conn->connect_errno . ': ' . $this->conn->connect_error);
            die();
        }

        // change character set to UTF-8
        if (!$this->conn->set_charset('utf8mb4')) {
            Database::errorPage($this->error());
        }

        if ('' !== $database) {
            try {
                $this->conn->select_db($database);
            } catch (mysqli_sql_exception) {
                throw new Exception('Cannot connect to database ' . $database);
            }
        }

        return true;
    }

    /**
     * Returns the error string.
     */
    public function error(): string
    {
        return $this->conn->error;
    }

    /**
     * Escapes a string for use in a query.
     */
    public function escape(string $string): string
    {
        return $this->conn->real_escape_string($string);
    }

    /**
     * Fetch a result row as an associative array.
     */
    public function fetchArray(mixed $result): ?array
    {
        return $result->fetch_assoc();
    }

    /**
     * Fetch a result row.
     */
    public function fetchRow(mixed $result): mixed
    {
        return $result->fetch_row()[0] ?? false;
    }

    /**
     * Fetches a complete result as an object.
     *
     * @param mixed $result Result set
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
     * Fetch a result row as an object.
     * This function fetches a result row as an object.
     *
     * @throws Exception
     */
    public function fetchObject(mixed $result): mixed
    {
        return $result->fetch_object();
    }

    /**
     * Number of rows in a result.
     */
    public function numRows(mixed $result): int
    {
        if ($result instanceof mysqli_result) {
            return $result->num_rows;
        }

        return 0;
    }

    /**
     * Logs the queries.
     */
    public function log(): string
    {
        return $this->sqlLog;
    }

    /**
     * This function returns the table status.
     *
     * @param string $prefix Table prefix
     * @return string[]
     */
    public function getTableStatus(string $prefix = ''): array
    {
        $status = [];
        foreach ($this->getTableNames($prefix) as $table) {
            $status[$table] = $this->getOne('SELECT count(*) FROM ' . $table);
        }

        return $status;
    }

    /**
     * Returns an array with all table names.
     *
     * @todo Have to be refactored because of https://github.com/thorsten/phpMyFAQ/issues/965
     *
     * @param string $prefix Table prefix
     *
     * @return string[]
     */
    public function getTableNames(string $prefix = ''): array
    {
        return $this->tableNames = [
            $prefix . 'faqadminlog',
            $prefix . 'faqattachment',
            $prefix . 'faqattachment_file',
            $prefix . 'faqbackup',
            $prefix . 'faqbookmarks',
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
            $prefix . 'faqforms',
            $prefix . 'faqglossary',
            $prefix . 'faqgroup',
            $prefix . 'faqgroup_right',
            $prefix . 'faqinstances',
            $prefix . 'faqinstances_config',
            $prefix . 'faqnews',
            $prefix . 'faqquestions',
            $prefix . 'faqright',
            $prefix . 'faqsearches',
            $prefix . 'faqseo',
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
     * Returns just one row.
     */
    private function getOne(string $query): string
    {
        $row = $this->conn->query($query)->fetch_row();

        return $row[0];
    }

    /**
     * This function is a replacement for MySQL's auto-increment so that
     * we don't need it anymore.
     *
     * @param string $table The name of the table
     * @param string $column The name of the ID column
     * @throws Exception
     */
    public function nextId(string $table, string $column): int
    {
        $select = sprintf('
           SELECT
               MAX(%s) AS current_id
           FROM
               %s', $column, $table);

        $mysqliresult = $this->query($select);

        $current = $mysqliresult instanceof mysqli_result ? $mysqliresult->fetch_row() : [0];

        return $current[0] + 1;
    }

    /**
     * This function sends a query to the database.
     *
     * @return mysqli_result $result
     * @throws Exception
     */
    public function query(string $query, int $offset = 0, int $rowcount = 0): mixed
    {
        $this->sqlLog .= $query;

        if (0 < $rowcount) {
            $query .= sprintf(' LIMIT %d,%d', $offset, $rowcount);
        }

        try {
            $result = $this->conn->query($query);
        } catch (mysqli_sql_exception $mysqlisqlexception) {
            throw new Exception($mysqlisqlexception->getMessage());
        }

        if (false === $result) {
            $this->sqlLog .= $this->conn->errno . ': ' . $this->error();
        }

        return $result;
    }

    /**
     * Returns the client version string.
     */
    public function clientVersion(): string
    {
        return mysqli_get_client_info();
    }

    /**
     * Returns the server version string.
     */
    public function serverVersion(): string
    {
        return $this->conn->server_info;
    }

    /**
     * Closes the connection to the database.
     */
    public function close(): void
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }

    public function __destruct()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }

    public function now(): string
    {
        return 'NOW()';
    }
}
