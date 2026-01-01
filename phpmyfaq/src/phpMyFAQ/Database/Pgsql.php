<?php

/**
 * The phpMyFAQ\Database\Pgsql class provides methods and functions for a PostgreSQL
 * database.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Tom Rochester <tom.rochester@gmail.com>
 * @copyright 2005-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-12-13
 */

declare(strict_types=1);

namespace phpMyFAQ\Database;

use Exception;
use PgSql\Connection;
use PgSql\Result;
use phpMyFAQ\Database;

/**
 * Class Pgsql
 *
 * @package phpMyFAQ\Database
 * @deprecated Use PDO instead. Will be removed in the v5.0 release.
 */
class Pgsql implements DatabaseDriver
{
    /**
     * The query log string.
     */
    private string $sqlLog = '';

    /**
     * Tables.
     *
     * @var string[]
     */
    public array $tableNames = [];

    /**
     * The connection resource.
     */
    private Connection|bool $conn = false;

    /**
     * Connects to the database.
     *
     * @param string $host Database hostname
     * @param string $user Database username
     * @param string $password Password
     * @param string $database Database name
     * @return null|bool true, if connected, otherwise false
     */
    public function connect(
        string $host,
        #[\SensitiveParameter] string $user,
        #[\SensitiveParameter] string $password,
        string $database = '',
        ?int $port = null,
    ): ?bool {
        $connectionString = sprintf(
            'host=%s port=%d dbname=%s user=%s password=%s',
            $host,
            $port,
            $database,
            $user,
            $password,
        );

        try {
            $this->conn = pg_connect($connectionString);

            if ($this->conn === false) {
                throw new Exception('No PostgreSQL connection opened yet');
            }

            if ($database === '') {
                throw new Exception('Database name is empty');
            }
        } catch (Exception $exception) {
            Database::errorPage($exception->getMessage());
            die();
        }

        return true;
    }

    /**
     * This function sends a query to the database.
     *
     * @return bool|Result $result
     */
    public function query(string $query, int $offset = 0, int $rowcount = 0): bool|Result
    {
        $this->sqlLog .= $query;

        if (0 < $rowcount) {
            $query .= sprintf(' LIMIT %d OFFSET %d', $rowcount, $offset);
        }

        $result = pg_query($this->conn, $query);

        if (!$result) {
            $this->sqlLog .= $this->error();
        }

        if (pg_result_status($result) === PGSQL_COMMAND_OK) {
            return true;
        }

        return $result;
    }

    /**
     * Returns the error string.
     */
    public function error(): string
    {
        return pg_last_error($this->conn);
    }

    /**
     * Escapes a string for use in a query.
     */
    public function escape(string $string): string
    {
        return pg_escape_string($this->conn, $string);
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
     * Fetch a result row as an object.
     *
     * @return false|object
     */
    public function fetchObject(mixed $result): mixed
    {
        return pg_fetch_object($result);
    }

    /**
     * Fetch a result row.
     */
    public function fetchRow(mixed $result): array|false
    {
        return pg_fetch_row($result);
    }

    /**
     * Number of rows in a result.
     */
    public function numRows(mixed $result): int
    {
        return pg_num_rows($result);
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
     */
    public function getTableStatus(string $prefix = ''): array
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
     */
    public function fetchArray(mixed $result): ?array
    {
        $result = pg_fetch_array($result, null, PGSQL_ASSOC);
        if ($result) {
            return $result;
        }

        return [];
    }

    /**
     * Returns just one row.
     */
    private function getOne(string $query): string
    {
        $row = pg_fetch_row($this->query($query));

        return $row[0];
    }

    /**
     * Returns the next ID of a table.
     *
     * @param string $table the name of the table
     * @param string $column    the name of the ID column
     */
    public function nextId(string $table, string $column): int
    {
        return (int) $this->getOne("SELECT nextval('" . $table . '_' . $column . "_seq') as current_id;");
    }

    /**
     * This function returns the client version string.
     */
    public function clientVersion(): string
    {
        $pgVersion = pg_version($this->conn);
        return $pgVersion['client'] ?? 'n/a';
    }

    /**
     * Returns the server version string.
     */
    public function serverVersion(): string
    {
        $pgVersion = pg_version($this->conn);
        return $pgVersion['server'] ?? 'n/a';
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
     * Closes the connection to the database.
     */
    public function close(): bool
    {
        return pg_close($this->conn);
    }

    public function now(): string
    {
        return 'CURRENT_TIMESTAMP';
    }
}
