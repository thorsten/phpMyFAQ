<?php

/**
 * The PMF_DB_Mysqli class provides methods and functions for MySQL 5.x and
 * MariaDB 5.x databases.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    David Soria Parra <dsoria@gmx.net>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_DB_Mysqli.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    David Soria Parra <dsoria@gmx.net>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 */
class PMF_DB_Mysqli implements PMF_DB_Driver
{
    /**
     * The connection object.
     *
     * @var mysqli
     */
    private $conn = false;

    /**
     * The query log string.
     *
     * @var string
     */
    private $sqllog = '';

    /**
     * Tables.
     *
     * @var array
     */
    public $tableNames = [];

    /**
     * Connects to the database.
     *
     * @param string $host     Hostname or path to socket
     * @param string $user     Username
     * @param string $password Password
     * @param string $database Database name
     *
     * @throws PMF_Exception
     *
     * @return null|boolean true, if connected, otherwise false
     */
    public function connect($host, $user, $password, $database = '')
    {
        if (substr($host, 0, 1) === '/') {
            // Connect to MySQL via socket
            $this->conn = new mysqli(null, $user, $password, null, null, $host);
        } else {
            // Connect to MySQL via network
            $this->conn = new mysqli($host, $user, $password);
        }

        if ($this->conn->connect_error) {
            PMF_Db::errorPage($this->conn->connect_errno.': '.$this->conn->connect_error);
            die();
        }

        // change character set to UTF-8
        if (!$this->conn->set_charset('utf8')) {
            PMF_Db::errorPage($this->error());
        }

        if ('' !== $database) {
            if (!$this->conn->select_db($database)) {
                throw new PMF_Exception('Cannot connect to database '.$database);
            }
        }

        return true;
    }

    /**
     * This function sends a query to the database.
     *
     * @param string $query
     * @param int    $offset
     * @param int    $rowCount
     *
     * @return mysqli_result $result
     */
    public function query($query, $offset = 0, $rowCount = 0)
    {
        if (DEBUG) {
            $this->sqllog .= PMF_Utils::debug($query);
        }

        if (0 < $rowCount) {
            $query .= sprintf(' LIMIT %d,%d', $offset, $rowCount);
        }

        $result = $this->conn->query($query);

        if (false === $result) {
            $this->sqllog .= $this->conn->errno.': '.$this->error();
        }

        return $result;
    }

    /**
     * Escapes a string for use in a query.
     *
     * @param   string
     *
     * @return string
     */
    public function escape($string)
    {
        return $this->conn->real_escape_string($string);
    }

    /**
     * Fetch a result row as an object.
     *
     * This function fetches a result row as an object.
     *
     * @param resource $result
     *
     * @return mixed
     */
    public function fetchObject($result)
    {
        return $result->fetch_object();
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
    public function fetchArray($result)
    {
        return $result->fetch_assoc();
    }

    /**
     * Fetches a complete result as an object.
     *
     * @param resource $result Resultset
     *
     * @throws Exception
     *
     * @return array
     */
    public function fetchAll($result)
    {
        $ret = [];
        if (false === $result) {
            throw new Exception('Error while fetching result: '.$this->error());
        }

        while ($row = $this->fetchObject($result)) {
            $ret[] = $row;
        }

        return $ret;
    }

    /**
     * Number of rows in a result.
     *
     * @param mixed $result
     *
     * @return int
     */
    public function numRows($result)
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
    public function log()
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
    public function getTableStatus($prefix = '')
    {
        $status = [];
        foreach ($this->getTableNames($prefix) as $table) {
            $status[$table] = $this->getOne('SELECT count(*) FROM '.$table);
        }

        return $status;
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
    public function nextId($table, $id)
    {
        $select = sprintf('
           SELECT
               MAX(%s) AS current_id
           FROM
               %s',
           $id,
           $table);

        $result = $this->query($select);

        if ($result instanceof mysqli_result) {
            $current = $result->fetch_row();
        } else {
            $current = [0];
        }

        return $current[0] + 1;
    }

    /**
     * Returns the error string.
     *
     * @return string
     */
    public function error()
    {
        return $this->conn->error;
    }

    /**
     * Returns the client version string.
     *
     * @return string
     */
    public function clientVersion()
    {
        return $this->conn->get_client_info();
    }

    /**
     * Returns the server version string.
     *
     * @return string
     */
    public function serverVersion()
    {
        return $this->conn->server_info;
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
    public function getTableNames($prefix = '')
    {
        return $this->tableNames = [
            $prefix.'faqadminlog',
            $prefix.'faqattachment',
            $prefix.'faqattachment_file',
            $prefix.'faqcaptcha',
            $prefix.'faqcategories',
            $prefix.'faqcategory_group',
            $prefix.'faqcategory_user',
            $prefix.'faqcategoryrelations',
            $prefix.'faqchanges',
            $prefix.'faqcomments',
            $prefix.'faqconfig',
            $prefix.'faqdata',
            $prefix.'faqdata_group',
            $prefix.'faqdata_revisions',
            $prefix.'faqdata_tags',
            $prefix.'faqdata_user',
            $prefix.'faqglossary',
            $prefix.'faqgroup',
            $prefix.'faqgroup_right',
            $prefix.'faqinstances',
            $prefix.'faqinstances_config',
            $prefix.'faqnews',
            $prefix.'faqquestions',
            $prefix.'faqright',
            $prefix.'faqsearches',
            $prefix.'faqsessions',
            $prefix.'faqstopwords',
            $prefix.'faqtags',
            $prefix.'faquser',
            $prefix.'faquser_group',
            $prefix.'faquser_right',
            $prefix.'faquserdata',
            $prefix.'faquserlogin',
            $prefix.'faqvisits',
            $prefix.'faqvoting',
        ];
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
    public function now()
    {
        return 'NOW()';
    }

    /**
     * Returns just one row.
     *
     * @param string
     * @param string $query
     *
     * @return string
     */
    private function getOne($query)
    {
        $row = $this->conn->query($query)->fetch_row();

        return $row[0];
    }

}
