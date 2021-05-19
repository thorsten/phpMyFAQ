<?php

/**
 * The Sqlite3 class provides methods and functions for a SQLite v3 database.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-03-02
 */

namespace phpMyFAQ\Database;

use phpMyFAQ\Database;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Utils;
use SQLite3Result;

/**
 * Class Sqlite3
 *
 * @package phpMyFAQ\Database
 */
class Sqlite3 implements DatabaseDriver
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
     * @var SQLite3
     */
    private $conn = false;

    /**
     * The query log string.
     *
     * @var string
     *
     * @see query()
     */
    private $sqllog = '';

    /** @var string */
    private const ERROR_MESSAGE =
        'Do not call numRows() after you\'ve fetched one or more result records, because ' .
        'phpMyFAQ\Database\Sqlite3::numRows() has to reset the resultset at its end.';

    /**
     * Connects to the database.
     *
     * @param string $host
     * @param string $user
     * @param string $password
     * @param string $database
     * @param int|null $port
     * @return null|bool
     */
    public function connect(string $host, string $user, string $password, $database = '', $port = null): ?bool
    {
        $this->conn = new \Sqlite3($host);

        if (!$this->conn) {
            Database::errorPage($this->conn->lastErrorMsg());
            die();
        }

        return true;
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
        return \SQLite3::escapeString($string);
    }

    /**
     * Fetch a result row as an object.
     *
     * @param mixed $result
     *
     * @return object or NULL if there are no more results
     */
    public function fetchObject($result): ?object
    {
        $result->fetchedByPMF = true;
        $return = $result->fetchArray(SQLITE3_ASSOC);

        return $return
            ? (object)$return
            : null;
    }

    /**
     * Fetch a result row as an array.
     *
     * @param SQLite3Result $result
     *
     * @return array
     */
    public function fetchArray($result): ?array
    {
        $result->fetchedByPMF = true;

        return $result->fetchArray();
    }

    /**
     * Fetch a result row.
     * @param $result
     * @return false|mixed
     */
    public function fetchRow($result)
    {
        return $result->fetchSingle();
    }

    /**
     * Fetches a complete result as an object.
     *
     * @param resource $result Resultset
     *
     * @return array of stdClass
     * @throws Exception
     */
    public function fetchAll($result): ?array
    {
        $ret = [];
        if (false === $result) {
            throw new Exception('Error while fetching result: ' . $this->error());
        }

        $result->fetchedByPMF = true;
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $ret[] = (object)$row;
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
        if (0 === $this->conn->lastErrorCode()) {
            return '';
        }

        return $this->conn->lastErrorMsg();
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
        $arr = [];

        $result = $this->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
        while ($row = $this->fetchAssoc($result)) {
            $num_result = $this->query('SELECT * FROM ' . $row['name']);
            $arr[$row['name']] = $this->numRows($num_result);
        }

        return $arr;
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
            $query .= sprintf(' LIMIT %d,%d', $offset, $rowcount);
        }

        $result = $this->conn->query($query);

        if (!$result) {
            $this->sqllog .= $this->error();
        }

        return $result;
    }

    /**
     * Fetch a result row as an associate array.
     *
     * @param SQLite3Result $result
     *
     * @return array
     */
    public function fetchAssoc($result): array
    {
        $result->fetchedByPMF = true;

        return $result->fetchArray(SQLITE3_ASSOC);
    }

    /**
     * Number of rows in a result.
     *
     * @param SQLite3Result $result
     *
     * @return int
     */
    public function numRows($result): int
    {
        !isset($result->fetchedByPMF) || !$result->fetchedByPMF || die(self::ERROR_MESSAGE);
        $numberOfRows = 0;
        while ($result->fetchArray(SQLITE3_NUM)) {
            ++$numberOfRows;
        }
        $result->reset();

        return $numberOfRows;
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
     * Returns the next ID of a table.
     *
     * @param string      the name of the table
     * @param string      the name of the ID column
     *
     * @return int
     */
    public function nextId($table, $id): int
    {
        $result = (int)$this->conn->querySingle(
            sprintf(
                'SELECT max(%s) AS current_id FROM %s',
                $id,
                $table
            )
        );

        return ($result + 1);
    }

    /**
     * Returns the libary version string.
     *
     * @return string
     */
    public function serverVersion(): string
    {
        return $this->clientVersion();
    }

    /**
     * Returns the libary version string.
     *
     * @return string
     */
    public function clientVersion(): string
    {
        $version = \Sqlite3::version();

        return $version['versionString'];
    }

    /**
     * Closes the connection to the database.
     *
     * @return bool
     */
    public function close(): bool
    {
        return $this->conn->close();
    }

    /**
     * @return string
     */
    public function now(): string
    {
        return "DATETIME('now', 'localtime')";
    }
}
