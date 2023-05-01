<?php

/**
 * The Sqlite3 class provides methods and functions for a SQLite v3 database.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-03-02
 */

namespace phpMyFAQ\Database;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Utils;

/**
 * Class Sqlite3
 *
 * @package phpMyFAQ\Database
 */
class Sqlite3 implements DatabaseDriver
{
    /**
     * @var string[] Tables.
     */
    public array $tableNames = [];

    /**
     * The connection object.
     *
     * @var \SQLite3|bool
     */
    private \Sqlite3|bool $conn = false;

    /**
     * The query log string.
     *
     *
     * @see query()
     */
    private string $sqllog = '';

    /** @var string */
    private const ERROR_MESSAGE =
        'Do not call numRows() after you\'ve fetched one or more result records, because ' .
        'phpMyFAQ\Database\Sqlite3::numRows() has to reset the results at its end.';

    /**
     * Connects to the database.
     *
     * @param int|null $port
     */
    public function connect(
        string $host,
        string $user,
        string $password,
        string $database = '',
        int $port = null
    ): ?bool {
        $this->conn = new \Sqlite3($host);

        return true;
    }

    /**
     * Escapes a string for use in a query.
     */
    public function escape(string $string): string
    {
        return \SQLite3::escapeString($string);
    }

    /**
     * Fetch a result row as an object.
     *
     * @return object|null or NULL if there are no more results
     */
    public function fetchObject(mixed $result): ?object
    {
        $return = $result->fetchArray(SQLITE3_ASSOC);

        return $return
            ? (object)$return
            : null;
    }

    /**
     * Fetch a result row as an array.
     */
    public function fetchArray(mixed $result): ?array
    {
        $fetchedData = $result->fetchArray();

        return is_array($fetchedData) ? $fetchedData : [];
    }

    /**
     * Fetch a result row.
     */
    public function fetchRow(mixed $result): mixed
    {
        return $result->fetchSingle();
    }

    /**
     * Fetches a complete result as an object.
     *
     * @param mixed $result Resultset
     * @return array|null of stdClass
     * @throws Exception
     */
    public function fetchAll(mixed $result): ?array
    {
        $ret = [];
        if (false === $result) {
            throw new Exception('Error while fetching result: ' . $this->error());
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $ret[] = (object)$row;
        }

        return $ret;
    }

    /**
     * Returns the error string.
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
     * @return \SQLite3Result|bool $result
     */
    public function query(string $query, int $offset = 0, int $rowcount = 0): \SQLite3Result|bool
    {
        $this->sqllog .= Utils::debug($query);

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
     */
    public function fetchAssoc(mixed $result): array
    {
        $fetchedData = $result->fetchArray(SQLITE3_ASSOC);

        return is_array($fetchedData) ? $fetchedData : [];
    }

    /**
     * Number of rows in a result.
     */
    public function numRows(mixed $result): int
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
     * Returns the next ID of a table.
     *
     * @param string $table the name of the table
     * @param string $id the name of the ID column
     */
    public function nextId(string $table, string $id): int
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
     * Returns the library version string.
     */
    public function serverVersion(): string
    {
        return $this->clientVersion();
    }

    /**
     * Returns the library version string.
     */
    public function clientVersion(): string
    {
        $version = \Sqlite3::version();

        return $version['versionString'];
    }

    /**
     * Closes the connection to the database.
     */
    public function close(): bool
    {
        return $this->conn->close();
    }

    public function now(): string
    {
        return "DATETIME('now', 'localtime')";
    }
}
