<?php

/**
 * The phpMyFAQ\Database\PdoPgsql class provides methods and functions for PostgreSQL with PDO.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-02-09
 */

declare(strict_types=1);

namespace phpMyFAQ\Database;

use PDO;
use PDOException;
use PDOStatement;
use phpMyFAQ\Core\Exception;
use SensitiveParameter;

/**
 * Class PdoPgsql
 *
 * @package phpMyFAQ\Database
 */
class PdoPgsql implements DatabaseDriver
{
    /**
     * @var string[] Tables.
     */
    public array $tableNames = [];

    /**
     * The connection object.
     */
    private ?PDO $pdo = null;

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
        $dsn = sprintf('pgsql:host=%s;dbname=%s;port=%s', $host, $database, $port);
        try {
            $this->pdo = new PDO($dsn, $user, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $pdoException) {
            throw new Exception($pdoException->getMessage());
        }

        return true;
    }

    /**
     * Returns the error string.
     */
    public function error(): string
    {
        return $this->pdo->errorInfo()[2] ?? '';
    }

    /**
     * Escapes a string for use in a query.
     */
    public function escape(string $string): string
    {
        return $this->pdo->quote($string);
    }

    /**
     * Fetch a result row as an associative array.
     */
    public function fetchArray(mixed $result): ?array
    {
        return $result->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch a result row.
     */
    public function fetchRow(mixed $result): mixed
    {
        return $result->fetch(PDO::FETCH_NUM)[0] ?? false;
    }

    /**
     * Fetches a complete result as an object.
     *
     * @param mixed $result Result set
     * @throws Exception
     */
    public function fetchAll(mixed $result): ?array
    {
        if (false === $result) {
            throw new Exception('Error while fetching result: ' . $this->error());
        }

        return $result->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Fetch a result row as an object.
     * This function fetches a result row as an object.
     *
     * @throws Exception
     */
    public function fetchObject(mixed $result): mixed
    {
        return $result->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Number of rows in a result.
     */
    public function numRows(mixed $result): int
    {
        return $result->rowCount();
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
            $prefix . 'faqchat_messages',
            $prefix . 'faqcomments',
            $prefix . 'faqconfig',
            $prefix . 'faqcustompages',
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
        $statement = $this->pdo->prepare($query);
        $statement->execute();

        $row = $statement->fetch(PDO::FETCH_NUM);

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
        $query = sprintf('SELECT MAX(%s) AS current_id FROM  %s', $column, $table);

        $statement = $this->pdo->prepare($query);
        $statement->execute();

        $current = $statement->fetch(PDO::FETCH_NUM);

        return $current[0] + 1;
    }

    /**
     * This function sends a query to the database.
     *
     * @return PDOStatement|false $result
     * @throws Exception
     */
    public function query(string $query, int $offset = 0, int $rowcount = 0): mixed
    {
        $this->sqlLog .= $query;

        if (0 < $rowcount) {
            $query .= sprintf(' LIMIT %d,%d', $offset, $rowcount);
        }

        try {
            $result = $this->pdo->query($query);
        } catch (PDOException $pdoException) {
            throw new Exception($pdoException->getMessage());
        }

        if (false === $result) {
            $this->sqlLog .= $this->pdo->errorCode() . ': ' . $this->error();
        }

        return $result;
    }

    /**
     * Prepares a statement for execution and returns a statement object.
     *
     * @param string $query The SQL query
     * @param array $options The driver options
     */
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        return $this->pdo->prepare($query, $options);
    }

    /**
     * Executes a prepared statement.
     *
     * @param PDOStatement $pdoStatement The prepared statement
     * @param array $params The parameters
     */
    public function execute(PDOStatement $pdoStatement, array $params = []): bool
    {
        return $pdoStatement->execute($params);
    }

    /**
     * Returns the client version string.
     */
    public function clientVersion(): string
    {
        return $this->pdo->getAttribute(PDO::ATTR_CLIENT_VERSION);
    }

    /**
     * Returns the server version string.
     */
    public function serverVersion(): string
    {
        return $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    /**
     * Closes the connection to the database.
     */
    public function close(): void
    {
        $this->pdo = null;
    }

    public function __destruct()
    {
        $this->close();
    }

    public function now(): string
    {
        return 'NOW()';
    }
}
