<?php

/**
 * File description
 *
 * license text
 * 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @version    $Id: HasOne.php,v 1.1 2007-12-12 18:12:09 lars Exp $
 * @copyright  Copyright 2007 Lars Tiedemann
 * @since      29.09.2007
 */

/**
 * LTC_Model_Decorator_HasOne
 * 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @package    LTC
 * @subpackage Model
 * @since      litecoms-0.0.1
 */
class LTC_Model_Decorator_HasOne
    extends LTC_Model_Decorator_Association
{
    
    /**
     * Searches for data in the model matching the given where conditions. 
     * 
     * Returns an array containing the results.
     * If no results match the where condition(s), an empty array is returned. 
     * 
     * @param array
     * @param array
     * @param array 
     * @return array
     */
    public function find($where, $fields = array(), $options = array())
    {
        $db = LTC_Db::getInstance();
        /*$primaryKey = $this->model->getPrimaryKey();
        // avoid ambiguous fields in $fields
        if ($fields == array()) {
            $fields = array_keys($this->getAllFieldProperties());
        }
        if (in_array($primaryKey, $fields, true)) {
            $index = array_search($primaryKey, $fields);
            $newField = sprintf('%s.%s as %s', $this->model->getTableName(), $primaryKey, $primaryKey);
            $fields[$index] = $newField;
        }
        // add join condition
        $join = sprintf(
            '%s.%s = %s.%s',
            $this->model->getTableName(),
            $this->model->getPrimaryKey(), 
            $this->associatedModel->getTableName(),
            $this->associatedModel->getPrimaryKey()
        );
        array_unshift($where, $join);*/
        // clean up vars
        $where  = $this->evaluateWhere($where);
        $fields = $this->evaluateFields($fields);
        // join primary key
        array_unshift($where, sprintf(
            '%s.%s=%s.%s',
            $this->model->getTableName(),
            $this->model->getPrimaryKey(),
            $this->associatedModel->getTableName(),
            $this->associatedModel->getPrimaryKey()
		));
        return $db->find(array($this->model, $this->associatedModel), $where, $fields, $options);
    }
    
}
