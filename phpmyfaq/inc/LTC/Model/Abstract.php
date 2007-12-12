<?php

/**
 * File description
 *
 * license text
 * 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @version    $Id: Abstract.php,v 1.1 2007-12-12 18:12:09 lars Exp $
 * @copyright  Copyright 2007 Lars Tiedemann
 * @since      05.12.2007
 */

/**
 * A Model abstraction class with useful functions. 
 * 
 * The methods implemented herein solely use the methods defined in 
 * LTC_Model_Interface. Consider the methods in LTC_Model_Abstract as shortcut
 * methods.  
 * 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @package    LTC
 * @subpackage Model
 * @since      litecoms-0.0.1
 */
abstract class LTC_Model_Abstract
    extends LTC_Model
{

    /**
     * Deletes the data entry $id. 
     * 
     * Returns true on success, otherwise false. 
     *
     * @param int ID
     * @return bool
     */
    public function deleteById($id)
    {
        return $this->delete(array($this->getPrimaryKey() => $id));
    }

    /**
     * Searches the model for the row with the given ID. 
     * 
     * If no row can be found, false is returned. 
     *
     * @param mixed ID
     * @param array fields
     * @return array
     */
    public function findById($id, $fields = array())
    {
        if (is_array($id)) {
            return null;
        }
        $result = $this->find(array($this->getPrimaryKey() => $id));
        // return only first row
        if (is_array($result) and isset($result[0]) and count($result) == 1) {
            return $result[0];
        }
        return $result;
    }

    /**
     * Updates the data set $id with the new data $data. 
     * 
     * Returns true on success, otherwise false. 
     *
     * @param int ID
     * @param array data
     * @return bool
     */
    public function updateById($id, array $data)
    {
        return $this->update(array($this->getPrimaryKey() => $id), $data);
    }
    
    /**
     * Finds and returns all datasets.
     * 
     * @param array
     * @param array
     * @return array
     */
    public function findAll($fields = array(), $options = array())
    {
        return $this->find("1", $fields, $options);
    }
    
}
