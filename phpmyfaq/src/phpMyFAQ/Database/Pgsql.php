<?php

/**
 * The phpMyFAQ\Db_Pgsql class provides methods and functions for a PostgreSQL
 * database.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Tom Rochester <tom.rochester@gmail.com>
 * @copyright 2003-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 */

namespace phpMyFAQ\Database;

use Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Utils;

/**
 * Class Pgsql
 *
 * @package phpMyFAQ\Database
 */
class Pgsql implements DatabaseDriver
{
    /**
     * The query log string.
     *
     * @var string
     */
    public $sqllog = '';

    /**
     * Tables.
     *
     * @var array
     */
    public $tableNames = [];

    /**
     * The connection resource.
     *
     * @var resource
     */
    private $conn = null;

    /**
     * Connects to the database.
     *
     * @param string $host Database hostname
     * @param string $user Database username
     * @param string $password Password
     * @param string $database Database name
     * @param int|null $port
     * @return null|bool true, if connected, otherwise false
     */
    public function connect(string $host, string $user, string $password, $database = '', $port = 5432): ?bool
    {
        $connectionString = sprintf(
            'host=%s port=%d dbname=%s user=%s password=%s',
            $host,
            $port,
            $database,
            $user,
            $password
        );

        $this->conn = pg_connect($connectionString);

        if (empty($database) || $this->conn == false) {
            Database::errorPage(pg_last_error($this->conn));
            die();
        }

        return true;
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

        if (0 < $rowcount) {
            $query .= sprintf(' LIMIT %d OFFSET %d', $rowcount, $offset);
        }

        $result = pg_query($this->conn, $query);

        if (!$result) {
            $this->sqllog .= $this->error();
        }

        return $result;
    }

    /**
     * Returns the error string.
     *
     * @return string
     */
    public function error(): string
    {
        return pg_last_error($this->conn);
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
        return pg_escape_string($this->conn, $string);
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
     * Fetch a result row as an object.
     *
     * @param resource $result
     *
     * @return mixed
     */
    public function fetchObject($result)
    {
        return pg_fetch_object($result);
    }

    /**
     * Fetch a result row.
     * @param $result
     * @return false|mixed
     */
    public function fetchRow($result)
    {
        return pg_fetch_row($result);
    }

    /**
     * Number of rows in a result.
     *
     * @param mixed $result
     *
     * @return int
     */
    public function numRows($result): int
    {
        return pg_num_rows($result);
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
        $select = 'SELECT relname FROM pg_stat_user_tables ORDER BY relname;';
        $arr = [];
        $result = $this->query($select);
        while ($row = $this->fetchArray($result)) {
            $count = $this->getOne('SELECT count(1) FROM ' . $row['relname'] . ';');
            $arr[$row['relname']] = $count;
        }

        return $arr;
    }

    /**
     * Fetch a result row as an object.
     *
     * @param mixed $result
     *
     * @return array
     */
    public function fetchArray($result): ?array
    {
        $result = pg_fetch_array($result, null, PGSQL_ASSOC);
        if ($result) {
            return $result;
        }
        return [];
    }

    /**
     * Returns just one row.
     *
     * @param string
     *
     * @return string
     */
    private function getOne($query): string
    {
        $row = pg_fetch_row($this->query($query));

        return $row[0];
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
        return (int) $this->getOne("SELECT nextval('" . $table . '_' . $id . "_seq') as current_id;");
    }

    /**
     * This function returns the client version string.
     *
     * @return string
     */
    public function clientVersion(): string
    {
        $pg_version = pg_version($this->conn);
        if (isset($pg_version['client'])) {
            return $pg_version['client'];
        } else {
            return 'n/a';
        }
    }

    /**
     * Returns the server version string.
     *
     * @return string
     */
    public function serverVersion(): string
    {
        $pg_version = pg_version($this->conn);
        if (isset($pg_version['server_version'])) {
            return $pg_version['server_version'];
        } else {
            return 'n/a';
        }
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
     * Closes the connection to the database.
     *
     * @return bool
     */
    public function close(): bool
    {
        return pg_close($this->conn);
    }

    /**
     * @return string
     */
    public function now(): string
    {
        return 'CURRENT_TIMESTAMP';
    }
}
