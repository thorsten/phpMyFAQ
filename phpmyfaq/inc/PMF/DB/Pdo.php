<?php
/**
 * The PMF_DB_Pdo class is an abstract PDO wrapper
 *
 * PHP Version 5.4
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   DB
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @package   2014-01-18
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_DB_Pdo_mysql
 *
 * @category  phpMyFAQ
 * @package   DB
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @package   2014-01-18
 */
abstract class PMF_DB_Pdo implements PMF_DB_Driver
{
    /**
     * The connection object
     *
     * @var PDO
     */
    protected $conn = false;

    /**
     * The query log string
     *
     * @var string
     */
    protected $sqllog = '';

    /**
     * Tables
     *
     * @var array
     */
    public $tableNames = [];

    /**
     * Connects to the database.
     *
     * @param string $host     Hostname
     * @param string $user     Username
     * @param string $password Password
     * @param string $database Database name
     *
     * @return  boolean true, if connected, otherwise false
     */
    abstract public function connect($host, $user, $password, $database = '');

    /**
     * Sends a query to the database.
     *
     * @param string $query
     *
     * @return PDOStatement $statement
     */
    public function query($query)
    {
        if (DEBUG) {
            $this->sqllog .= PMF_Utils::debug($query);
        }

        try {
            return $this->conn->query($query);
        } catch (PDOException $e) {
            $this->sqllog .= $e->getMessage();
        }
    }

    /**
     * Escapes a string for use in a query
     *
     * @param string $string
     *
     * @return  string
     */
    public function escape($string)
    {
        // @todo quote() is not like mysql_real_escape_string()
        //return $this->conn->quote($string);
        return $string;
    }

    /**
     * Fetch a result row as an object
     *
     * @param PDOStatement $statement
     *
     * @return mixed
     */
    public function fetchObject($statement)
    {
        $statement->setFetchMode(PDO::FETCH_OBJ);
        return $statement->fetch();
    }

    /**
     * Fetch a result row as an associative array.
     *
     * @param PDOStatement $statement
     *
     * @return  array
     */
    public function fetchArray($statement)
    {
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        return $statement->fetch();
    }

    /**
     * Fetches a complete result as an object
     *
     * @param  PDOStatement $statement
     *
     * @throws Exception
     *
     * @return array
     */
    public function fetchAll($statement)
    {
        if (false === $statement) {
            throw new Exception('Error while fetching result: ' . $this->error());
        }

        return $statement->fetchAll(PDO::FETCH_OBJ  );
    }

    /**
     * Number of rows in a result
     *
     * @param PDOStatement $statement
     *
     * @return integer
     */
    public function numRows($statement)
    {
        return $statement->rowCount();
    }

    /**
     * Logs the queries
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
     * @return array
     */
    public function getTableStatus()
    {
        $arr = [];
        $result = $this->query("SHOW TABLE STATUS");
        while ($row = $this->fetchArray($result)) {
            $arr[$row["Name"]] = $row["Rows"];
        }
        return $arr;
    }

    /**
     * This function is a replacement for MySQL's auto-increment so that
     * we don't need it anymore.
     *
     * @param   string      the name of the table
     * @param   string      the name of the ID column
     * @return  int
     */
    public function nextId($table, $id)
    {
        $select = sprintf("
           SELECT
               MAX(%s) AS current_id
           FROM
               %s",
            $id,
            $table);

        $result  = $this->query($select);
        $current = $result->fetch();
        return $current[0] + 1;
    }

    /**
     * Returns the error string.
     *
     * @return string
     */
    public function error()
    {
        $error = $this->conn->errorInfo();

        return $error[0] . ': ' . $error[2];
    }

    /**
     * Returns the client version string.
     *
     * @return string
     */
    public function clientVersion()
    {
        return $this->conn->getAttribute(PDO::ATTR_CLIENT_VERSION);
    }

    /**
     * Returns the server version string.
     *
     * @return string
     */
    public function serverVersion()
    {
        return $this->conn->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    /**
     * Returns an array with all table names
     *
     * @param  string $prefix Table prefix
     *
     * @return array
     */
    function getTableNames($prefix = '')
    {
        // First, declare those tables that are referenced by others
        $this->tableNames[] = $prefix.'faquser';

        $result = $this->query('SHOW TABLES'.(('' == $prefix) ? '' : ' LIKE \''.$prefix.'%\''));
        while ($row = $this->fetchObject($result)) {
            foreach ($row as $tableName) {
                if (!in_array($tableName, $this->tableNames)) {
                    $this->tableNames[] = $tableName;
                }
            }
        }

        return $this->tableNames;
    }

    /**
     * Closes the connection to the database.
     *
     * @return void
     */
    public function close()
    {
        if (is_resource($this->conn)) {
            $this->conn = null;
        }
    }

    /**
     * Destructor
     *
     * @return void
     */
    public function __destruct()
    {
        if (is_resource($this->conn)) {
            $this->conn = null;
        }
    }
}
