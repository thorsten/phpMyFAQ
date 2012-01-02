<?php
/**
 * phpMyFAQ database based search classes
 *
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 * 
 * @category  phpMyFAQ
 * @package   PMF_Search
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2010-06-06
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Search_Database
 *
 * @category  phpMyFAQ
 * @package   PMF_Search
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2010-06-06
 */
class PMF_Search_Database extends PMF_Search_Abstract implements PMF_Search_Interface
{
    /**
     * Database connection handle
     * 
     * @var PMF_DB_Driver
     */
    protected $dbHandle = null;

    /**
     * Searching database table
     * 
     * @var string
     */
    protected $table = '';
    
    /**
     * Joined searching database table
     * 
     * @var string
     */
    protected $joinedTable = '';
    
    /**
     * Columns for the resultset
     * 
     * @var array
     */
    protected $resultColumns = array();
    
    /**
     * Columns for the joined table
     * 
     * @var array
     */
    protected $joinedColumns = array();
    
    /**
     * Matching columns for the search
     * 
     * @var array
     */
    protected $matchingColumns = array();
    
    /**
     * Conditions columns with their values
     * 
     * @var array
     */
    protected $conditions = array();
    
    /**
     * Constructor
     * 
     * @param PMF_Language $language Language
     * 
     * @return PMF_Search_Abstract
     */
    public function __construct(PMF_Language $language)
    {
        parent::__construct($language);
    }
    
    /**
     * Prepares the search and executes it
     * 
     * @param string $searchTerm Search term
     * 
     * @return resource
     * 
     * @throws PMF_Search_Exception
     */
    public function search($searchTerm)
    {
        $query = sprintf("
            SELECT
                %s
            FROM 
                %s %s %s
            WHERE
                %s = %d",
            $this->getResultColumns(),
            $this->getTable(),
            $this->getJoinedTable(),
            $this->getJoinedColumns(),
            $this->getMatchingColumns(),
            $searchTerm);

        $this->resultSet = $this->dbHandle->query($query);
    }
    
    /**
     * Setter for the database handle
     * 
     * @param PMF_DB_Driver $dbHandle Database Handle
     * 
     * @return PMF_Search_Database
     */
    public function setDatabaseHandle(PMF_DB_Driver $dbHandle)
    {
        $this->dbHandle = $dbHandle;
        
        return $this;
    }
    /**
     * Getter for the database handle
     * 
     * @return PMF_DB_Driver
     */
    public function getDatabaseHandle()
    {
        return $this->dbHandle;
    }
    
    /**
     * Sets search table
     * 
     * @param string $table Table where search should be performed
     * 
     * @return PMF_Search_Database
     */
    public function setTable($table)
    {
        $this->table = $table;
        
        return $this;
    }
    
    /**
     * Returns the search table
     * 
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }
    
    /**
     * Sets joined search table
     * 
     * @param string $joinedTable Joined table where search should be performed
     * 
     * @return PMF_Search_Database
     */
    public function setJoinedTable($joinedTable = '')
    {
        $this->joinedTable = $joinedTable;
        
        return $this;
    }
    
    /**
     * Returns the joined table
     * 
     * @return string
     */
    public function getJoinedTable()
    {
        if (empty($this->joinedTable)) {
            return '';
        } else {
            return ' LEFT JOIN ' . $this->joinedTable . ' ON ';
        }
    }
    
    /**
     * Sets the part of the SQL query with the columns for the result set
     * 
     * @param array $columns Array of columns
     * 
     * @return PMF_Search_Database
     */
    public function setResultColumns(Array $columns)
    {
        $this->resultColumns = $columns;
        
        return $this;
    }
    
    /**
     * Returns the part of the SQL query with the columns for the result set
     * 
     * @return string
     */
    public function getResultColumns()
    {
        $resultColumns = '';
        
        foreach ($this->resultColumns as $column) {
            if (empty($resultColumns)) {
                $resultColumns = $column;
            } else {
                $resultColumns .= ', ' . $column;
            }
        }
        
        return $resultColumns;
    }
    
    /**
     * Sets the part of the SQL query with the columns for the join
     * 
     * @param array $joinedColumns Array of columns
     * 
     * @return PMF_Search_Database
     */
    public function setJoinedColumns(Array $joinedColumns)
    {
        $this->joinedColumns = $joinedColumns;
        
        return $this;
    }
    
    /**
     * Returns the part of the SQL query with the columns for the join
     * 
     * @return string
     */
    public function getJoinedColumns()
    {
        $joinedColumns = '';
        
        foreach ($this->joinedColumns as $column) {
            $joinedColumns .= $column . ' AND ';
        }
        
        return PMF_String::substr($joinedColumns, 0, -4);
    }
    
    /**
     * Sets the part of the SQL query with the matching columns
     * 
     * @param array $matchingColumns Array of columns
     * 
     * @return PMF_Search_Database
     */
    public function setMatchingColumns(Array $matchingColumns)
    {
        $this->matchingColumns = $matchingColumns;
        
        return $this;
    }
    
    /**
     * Returns the part of the SQL query with the matching columns
     * 
     * @return string
     */
    public function getMatchingColumns()
    {
        return implode(', ', $this->matchingColumns);
    }
    
    /**
     * Sets the part of the SQL query with the conditions
     * 
     * @param array $conditions Array of columns
     * 
     * @return PMF_Search_Database
     */
    public function setConditions(Array $conditions)
    {
        $this->conditions = $conditions;
        
        return $this;
    }
    
    /**
     * Returns the part of the SQL query with the conditions
     * 
     * @return string
     */
    public function getConditions()
    {
        $conditions = '';
        
        if (count($this->conditions)) {
            foreach ($this->conditions as $column => $value) {
                if (is_array($value)) {
                    $conditions .= ' AND ' . $column . ' IN (' . implode(', ', $value) . ')';
                } else {
                    $conditions .= ' AND ' . $column . ' = ' . $value;
                }
            }
        }
        
        return $conditions;
    }
    
    /**
     * Creates the part for the WHERE clause
     * 
     * @param string $searchTerm Search term
     * 
     * @return string
     */
    public function getMatchClause($searchTerm = '')
    {
        $keys     = PMF_String::preg_split("/\s+/", $searchTerm);
        $numKeys  = count($keys);
        $numMatch = count($this->matchingColumns);
        $where    = '';
        
        for ($i = 0; $i < $numKeys; $i++) {
            if (strlen($where) != 0 ) {
                $where = $where . " OR";
            }
            $where = $where . " (";
            for ($j = 0; $j < $numMatch; $j++) {
                if ($j != 0) {
                    $where = $where." OR ";
                }
                $where = sprintf("%s%s LIKE '%%%s%%'", 
                    $where, 
                    $this->matchingColumns[$j],
                    $this->dbHandle->escape($keys[$i]));
            }
            $where .= ")";
        }
        
        return $where;
    }
}