<?php

/**
 * The phpMyFAQ\Database\Mysqli class provides methods and functions for MySQL and
 * MariaDB databases.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    David Soria Parra <dsoria@gmx.net>
 * @copyright 2005-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 */

namespace phpMyFAQ\Database;

use mysqli_result;
use phpMyFAQ\Database;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Utils;

/**
 * Class Mysqli
 *
 * @package phpMyFAQ\Database
 */
class Mysqli implements DatabaseDriver
{
    /**
     * Tables.
     *
     * @var array
     */
    public $tableNames = [];

    /**
     * The connection object.
     *
     * @var \mysqli
     */
    private $conn = false;

    /**
     * The query log string.
     *
     * @var string
     */
    private $sqllog = '';

    /**
     * Connects to the database.
     *
     * @param string $host Hostname or path to socket
     * @param string $user Username
     * @param string $password Password
     * @param string $database Database name
     * @param int|null $port
     * @return null|bool true, if connected, otherwise false
     * @throws Exception
     */
    public function connect(string $host, string $user, string $password, $database = '', $port = 3306): ?bool
    {
        if (substr($host, 0, 1) === '/') {
            // Connect to MySQL via socket
            $this->conn = new \mysqli(null, $user, $password, null, $port, $host);
        } else {
            // Connect to MySQL via network
            $this->conn = new \mysqli($host, $user, $password, null, $port);
        }

        if ($this->conn->connect_error) {
            Database::errorPage($this->conn->connect_errno . ': ' . $this->conn->connect_error);
            die();
        }

        // change character set to UTF-8
        if (!$this->conn->set_charset('utf8')) {
            Database::errorPage($this->error());
        }

        if ('' !== $database) {
            if (!$this->conn->select_db($database)) {
                throw new Exception('Cannot connect to database ' . $database);
            }
        }

        return true;
    }

    /**
     * Returns the error string.
     *
     * @return string
     */
    public function error(): string
    {
        return $this->conn->error;
    }

    /**
     * Escapes a string for use in a query.
     *
     * @param string
     *
     * @return string
     */
    public function escape($string): string
    {
        return $this->conn->real_escape_string($string);
    }

    /**
     * Fetch a result row as an object.
     *
     * This function fetches a result as an associative array.
     *
     * @param mixed $result
     *
     * @return array
     */
    public function fetchArray($result): ?array
    {
        return $result->fetch_assoc();
    }

    /**
     * Fetch a result row.
     * @param $result
     * @return false|mixed
     */
    public function fetchRow($result)
    {
        return $result->fetch_row()[0] ?? false;
    }

    /**
     * Fetches a complete result as an object.
     *
     * @param mysqli_result $result Result set
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
     * Fetch a result row as an object.
     *
     * This function fetches a result row as an object.
     *
     * @param mysqli_result $result
     *
     * @return mixed
     * @throws Exception
     */
    public function fetchObject($result)
    {
        if ($result instanceof mysqli_result) {
            return $result->fetch_object();
        }

        throw new Exception($this->error());
    }

    /**
     * Number of rows in a result.
     *
     * @param mysqli_result $result
     *
     * @return int
     */
    public function numRows($result): int
    {
        if ($result instanceof mysqli_result) {
            return $result->num_rows;
        } else {
            return 0;
        }
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
     * Returns just one row.
     *
     * @param string $query
     *
     * @return string
     */
    private function getOne($query): string
    {
        $row = $this->conn->query($query)->fetch_row();

        return $row[0];
    }

    /**
     * This function is a replacement for MySQL's auto-increment so that
     * we don't need it anymore.
     *
     * @param string $table The name of the table
     * @param string $id    The name of the ID column
     *
     * @return int
     */
    public function nextId($table, $id): int
    {
        $select = sprintf(
            '
           SELECT
               MAX(%s) AS current_id
           FROM
               %s',
            $id,
            $table
        );

        $result = $this->query($select);

        if ($result instanceof mysqli_result) {
            $current = $result->fetch_row();
        } else {
            $current = [0];
        }

        return $current[0] + 1;
    }

    /**
     * This function sends a query to the database.
     *
     * @param string $query
     * @param int $offset
     * @param int $rowcount
     *
     * @return mysqli_result $result
     */
    public function query(string $query, $offset = 0, $rowcount = 0)
    {
        if (DEBUG) {
            $this->sqllog .= Utils::debug($query);
        }

        if (0 < $rowcount) {
            $query .= sprintf(' LIMIT %d,%d', $offset, $rowcount);
        }

        $result = $this->conn->query($query);

        if (false === $result) {
            $this->sqllog .= $this->conn->errno . ': ' . $this->error();
        }

        return $result;
    }

    /**
     * Returns the client version string.
     *
     * @return string
     */
    public function clientVersion(): string
    {
        return $this->conn->get_client_info();
    }

    /**
     * Returns the server version string.
     *
     * @return string
     */
    public function serverVersion(): string
    {
        return $this->conn->server_info;
    }

    /**
     * Closes the connection to the database.
     */
    public function close()
    {
        if (is_resource($this->conn)) {
            $this->conn->close();
        }
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        if (is_resource($this->conn)) {
            $this->conn->close();
        }
    }

    /**
     * @return string
     */
    public function now(): string
    {
        return 'NOW()';
    }
}
