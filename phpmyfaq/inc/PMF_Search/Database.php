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
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2010-06-06
 */

/**
 * PMF_Search_Database
 *
 * @category  phpMyFAQ
 * @package   PMF_Search
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
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
     * @return boolean
     * 
     * @throws PMF_Search_Exception
     */
    public function search($searchTerm)
    {
        
    }
    
    /**
     * Returns the result of the search
     * 
     * @return PMF_Search_Resultset
     * 
     * @throws PMF_Search_Exception
     */
    public function getResult()
    {
        
    }
    
    /**
     * Setter for the database handle
     * 
     * @param PMF_DB_Driver $dbHandle Database Handle
     * 
     * @return void
     */
    public function setDatabaseHandle(PMF_DB_Driver $dbHandle)
    {
        $this->dbHandle = $dbHandle;
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
     * @return void
     */
    public function setTable($table)
    {
        $this->table = $table;
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
     * @return void
     */
    public function setJoinedTable($joinedTable)
    {
        $this->joinedTable = $joinedTable;
    }
    
    /**
     * Returns the joined table
     * 
     * @return string
     */
    public function getJoinedTable()
    {
        return $this->joinedTable;
    }
}