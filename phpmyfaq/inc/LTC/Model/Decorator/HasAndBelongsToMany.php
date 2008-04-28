<?php

/**
 * File description
 *
 * license text
 * 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @version    $Id: HasAndBelongsToMany.php,v 1.1 2008-04-28 21:14:20 lars Exp $
 * @copyright  Copyright 2007 Lars Tiedemann
 * @since      29.09.2007
 */

/**
 * LTC_Model_Decorator_HasAndBelongsToMany
 * 
 * This model decorator provides methods to realize n-to-m relations. 
 * 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @package    LTC
 * @subpackage Model
 * @since      litecoms-0.0.1
 */
class LTC_Model_Decorator_HasAndBelongsToMany
    extends LTC_Model_Decorator_Association
{
    
    /**
     * Table that joins the two models
     *
     * @var LTC_Model_Interface
     */
    protected $associationTable = null;
    
    
    /**
     * Creates a new LTC_Decorator_HasMany instance
     *
     * @param LTC_Model_Interface $model
     * @param LTC_Model_Interface $associatedModel
     */
    public function __construct(LTC_Model_Interface $model = null, LTC_Model_Interface $associatedModel = null) 
    {
        parent::__construct($model, $associatedModel);
        $this->associationTable = new LTC_Model_AssociationTable($model, $associatedModel);
    }
    
    /**
     * Cleans up ambiguous field names in a $where argument. 
     * 
     * @param mixed $where argument
     * @return array A cleaned up version of the original $where input. 
     *     If a field name (a key in $where) exists in the model as well as in
     * 	   associatedModel, the field names are being joined:
     *     model.field = associatedModel.field
     *     AND
     *     model.field = fieldValue
     * 
     *     If $where is not an array, $where is returned unchanged. 
     */
    protected function evaluateWhere($where)
    {
        if (!is_array($where)) {
            $where = array($where);
        }
        // return $where if empty
        if (empty($where)) {
            return $where;
        }
        // check for ambiguous fields
        $model1Fields = array_keys($this->model->getAllFieldProperties());
        $model2Fields = array_keys($this->associatedModel->getAllFieldProperties());
        $model3Fields = array_keys($this->associationTable->getAllFieldProperties());
        foreach ($where as $field => $value) {
            if (!in_array($field, $model1Fields, true) or !in_array($field, $model3Fields, true)) {
                continue;
            }
            // rename field
            $newField = sprintf('%s.%s', $this->model->getTableName(), $field);
            $where[$newField] = $value;
            unset($where[$field]);
        }
        foreach ($where as $field => $value) {
            if (!in_array($field, $model2Fields, true) or !in_array($field, $model3Fields, true)) {
                continue;
            }
            // rename field
            $newField = sprintf('%s.%s', $this->associatedModel->getTableName(), $field);
            $where[$newField] = $value;
            unset($where[$field]);
        }
        return $where;
    }
    
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
        // join on primary key
        array_unshift($where, sprintf(
            '%s.%s=%s.%s AND %s.%s=%s.%s',
            $this->model->getTableName(),
            $this->model->getPrimaryKey(),
            $this->associationTable->getTableName(),
            $this->model->getPrimaryKey(),
            $this->associationTable->getTableName(),
            $this->associatedModel->getPrimaryKey(),
            $this->associatedModel->getTableName(),
            $this->associatedModel->getPrimaryKey()
		));
        return $db->find(array($this->model, $this->associatedModel, $this->associationTable), $where, $fields, $options);
    }
    
    /**
     * Associates the entries matching the given where term with the associated
     * model. 
     * 
     * Returns true on success, otherwise false.
     * 
     * @param int ID of the model entry
     * @param int ID of the associated model entry
     * @return bool
     */
    public function associate($id1, $id2) 
    {
        $data = array(
            $this->model->getPrimaryKey() => $id1,
            $this->associatedModel->getPrimaryKey() => $id2
        );
        $this->associationTable->insert($data);
        $find = $this->associationTable->find($data);
        if (!$find or !is_array($find)) {
            return false; 
        }
        if (count($find) === 1) {
            return true;
        }
        return false;
    }
    
    /**
     * Cleans up a $fields argument avoiding ambiguous field definitions. 
     * 
     * @param mixed $fields
     * @return array
     */
    protected function evaluateFields($fields)
    {
        // make $fields an array
        if (!is_array($fields)) {
            $fields = array($fields);
        }
        if (empty($fields)) {
            $fields = array_keys($this->getAllFieldProperties());
        }
        // check for ambiguous fields
        $model1Fields = array_keys($this->model->getAllFieldProperties());
        $model2Fields = array_keys($this->associatedModel->getAllFieldProperties());
        $model3Fields = array_keys($this->associationTable->getAllFieldProperties());
        foreach ($fields as $field) {
            if (!in_array($field, $model1Fields, true) or !in_array($field, $model3Fields, true)) {
                continue;
            }
            // rename field
            $index    = array_search($field, $fields);
            $newField = sprintf('%s.%s AS %s', $this->model->getTableName(), $field, $field);
            $fields[$index] = $newField;
        }
        foreach ($fields as $field) {
            if (!in_array($field, $model2Fields, true) or !in_array($field, $model3Fields, true)) {
                continue;
            }
            // rename field
            $index    = array_search($field, $fields);
            $newField = sprintf('%s.%s AS %s', $this->associatedModel->getTableName(), $field, $field);
            $fields[$index] = $newField;
        }
        return $fields;
    }
    
    /**
     * Installs the model, the associated model and the association table in 
     * the database. 
     *
     * Returns true on full success, otherwise false. 
     * 
     * @return bool
     */
    public function install()
    {
        if (parent::install() !== true) {
            return false;
        }
        return $this->associationTable->install();
    }

    /**
     * Uninstalls the model, the associated model and the association table 
     * from the database.
     * 
     * Returns true on full success, otherwise false. 
     *
     * @return bool
     */
    public function uninstall()
    {
        if (parent::uninstall() !== true) {
            return false;
        }
        return $this->associationTable->uninstall();
    }
    
}
