<?php

/**
 * File description
 *
 * license text
 * 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @version    $Id: BelongsTo.php,v 1.1 2008-04-28 21:14:20 lars Exp $
 * @copyright  Copyright 2007 Lars Tiedemann
 * @since      29.09.2007
 */

/**
 * LTC_Model_Decorator_BelongsTo
 * 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @package    LTC
 * @subpackage Model
 * @since      litecoms-0.0.1
 */
class LTC_Model_Decorator_BelongsTo
    extends LTC_Model_Decorator_Association
{
    
    /**
     * Searches for data in the model matching the given where conditions. 
     * 
     * Returns an array containing the results.
     * If no results match the where condition(s), an empty array is returned. 
     * $options is an associative array containing optional query parameters.
     * The options need to be supported by the database layer used (see LTC_Db
     * package for details).
     * 
     * @param array
     * @param array
     * @param array 
     * @return array
     */
    public function find($where, $fields = array(), $options = array())
    {
        $db = LTC_Db::getInstance();
        // clean up vars
        $where  = $this->evaluateWhere($where);
        $fields = $this->evaluateFields($fields);
        // join foreign key
        array_unshift($where, sprintf(
            '%s.%s=%s.%s',
            $this->model->getTableName(),
            $this->associatedModel->getPrimaryKey(),
            $this->associatedModel->getTableName(),
            $this->associatedModel->getPrimaryKey()
		));
        return $db->find(array($this->model, $this->associatedModel), $where, $fields, $options);
    }
    
    /**
     * Associates the entries matching the given where term with the associated
     * model. 
     * 
     * Returns true on success, otherwise false.
     * 
     * @param array
     * @param int ID of the associated model entry
     * @return bool
     */
    public function associate($where, $parentId) 
    {
        $data = array(
            $this->associatedModel->getPrimaryKey() => $parentId
        );
        return $this->model->update($where, $data);
    }

    /**
     * Deletes the data that matches the given where argument. 
     * 
     * Returns true on success, otherwise false. 
     *
     * @param array where
     * @return bool
     */
    public function delete($where)
    {
        return $this->model->delete($where);
    }
    
}
