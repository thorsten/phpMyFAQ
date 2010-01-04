<?php
/**
 * Database result set Iterator implementation
 *
 * PHP Version 5.2.0
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
 * @package   PMF_DB
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-01-04
 */

/**
 * PMF_DB_Resultset
 * 
 * @category  phpMyFAQ
 * @package   PMF_DB
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-01-04
 */
class PMF_DB_Resultset implements Iterator 
{
    /**
     * DB handle
     *
     * @var PMF_DB_Driver
     */
    private $db;
    
    /**
     * Iterator from an ArrayObject instance
     *
     * @var ArrayObject
     */
    private $iterator;
    
    /**
     * Constructor
     *
     * @param resource$result Resultset
     */
    public function __construct($result)
    {
        $this->db    = PMF_Db::getInstance();
        $arrayObject = new ArrayObject();
        while ($row = $this->db->fetch_assoc($result)) {
            $arrayObject[]  = $row;
        }
        $this->iterator = $arrayObject->getIterator();
    }
    
    /**
     * Resets pointer of database resultset
     *
     * @return void
     */
    public function rewind()
    {
        $this->iterator->rewind();
    }
    
    /**
     * Returns current row
     * 
     * @return array
     */
    public function current()
    {
        if ($this->iterator->valid()) {
            return $this->iterator->current();
        }
    }
    
    /**
     * Returns the key of the current element
     *
     * @return integer
     */
    public function key()
    {
        return key($this->current());
    }
    
    /**
     * Returns next row
     * 
     * @return void
     */
    public function next()
    {
        if ($this->iterator->valid()) {
            $this->iterator->next();
        }
    }
    
    /**
     * Checks if current element is valid
     *
     * @return boolean
     */
    public function valid()
    {
        return is_array($this->current());
    }
    
    /**
     * Count rows
     * 
     * @return integer
     */
    public function count()
    {
        return $this->iterator->count();
    }
}