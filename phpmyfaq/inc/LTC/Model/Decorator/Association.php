<?php

/**
 * File description
 *
 * license text
 * 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @version    $Id: Association.php,v 1.1 2007-12-12 18:12:09 lars Exp $
 * @copyright  Copyright 2007 Lars Tiedemann
 * @since      29.09.2007
 */

/**
 * Associates two models.
 * 
 * Each of the two models represents one table in the database. Using the
 * Association decorator allows to join two models together. These Decorators 
 * use the Association decorator:
 * LTC_Model_Decorator_HasOne
 * LTC_Model_Decorator_HasMany
 * LTC_Model_Decorator_BelongsTo
 * LTC_Model_Decorator_HasAndBelongsTo
 * 
 * Join two models by calling one of these static methods:
 * LTC_Model::hasOne()
 * LTC_Model::hasMany()
 * LTC_Model::BelongsTo()
 * LTC_Model::HasAndBelongsTo()
 * and pass two model instances to the method. 
 * This will create one of the above mentioned Association Decorator instances. 
 * Now you can use all additional methods provided by the decorator.
 * 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @package    LTC
 * @subpackage Model
 * @since      litecoms-0.0.1
 */
class LTC_Model_Decorator_Association
    extends LTC_Model_Decorator
{
    
    /**
     * The model that is associated to the current instance
     *
     * @var LTC_Model_Interface
     */
    protected $associatedModel = null;
    
    
    /**
     * Creates a new LTC_Decorator_HasMany instance
     *
     * @param LTC_Model_Interface $model
     * @param LTC_Model_Interface $associatedModel
     */
    public function __construct(LTC_Model_Interface $model = null, LTC_Model_Interface $associatedModel = null) 
    {
        $this->model           = $model;
        $this->associatedModel = $associatedModel;
    }

    /**
     * Returns an associative array of all field properties in the model. 
     * 
     * Field properties are defined as an associative array in the init() method 
     * of the model. 
     * This method is needed by LTC_Db to process models. 
     *
     * @return array 
     *     field1 => array properties1[, field2 => array properties2[, ...]]
     */
    public function getAllFieldProperties()
    {
        return array_merge($this->model->getAllFieldProperties(), $this->associatedModel->getAllFieldProperties());
    }

    /**
     * Returns an associative array of field properties.
     *
     * This method is needed by LTC_Db to process models. 
     * 
     * @param string field name
     * @return array 
     *     property1 => value1[, property2 => value2[, ...]]
     */
    public function getFieldProperties($fieldName)
    {
        foreach ($this->getAllFieldProperties() as $field => $properties) {
            if ($field == $fieldName) {
                return $properties;
            }
        }
        return array();
    }

    /**
     * Inserts a new data set into the current model and returns the ID of the 
     * new data set.
     * 
     * The ID is the value of the primary key field.   
     *
     * @param array 
     * @return int new ID
     */
    public function insert(array $data)
    {
        // insert into model and get the ID
        $modelData = $this->extractModelData($data, $this->model);
        $id = $this->model->insert($modelData);
        if (!is_int($id) or $id <= 0) {
            return false;
        }
        // insert into associatedModel and return the ID
        $modelData = $this->extractModelData($data, $this->associatedModel);
        $modelData[$this->associatedModel->getPrimaryKey()] = $id;
        $associatedId = $this->associatedModel->insert($modelData);
        if (!is_int($associatedId) or $associatedId !== $id) {
            return false;
        }
        return $id;
    }

    /**
     * Updates the data set(s) matching $where.
     * 
     * Returns true on success, otherwise false. 
     *
     * @param array where
     * @param array data
     * @return bool
     */
    public function update($where, array $data)
    {
        // update model
        $modelData = $this->extractModelData($data, $this->model);
        $result = $this->model->update($where, $modelData);
        if ($result == false) {
            return false;
        }
        // update associatedModel and return the result 
        $modelData = $this->extractModelData($data, $this->associatedModel);
        $result = $this->associatedModel->update($where, $modelData);
        if ($result == false) {
            return false;
        }
        return true;
    }
    
    /**
     * Extracts the field value pairs from $data that belong to $model.
     *
     * @param array $data
     * @param LTC_Model_Interface $model
     * @return unknown
     */
    protected function extractModelData(array $data, LTC_Model_Interface $model) 
    {
        $modelData   = array();
        $modelFields = array_keys($model->getAllFieldProperties());
        foreach ($data as $field => $value) {
            if (in_array($field, $modelFields)) {
                $modelData[$field] = $value;
            }
        }
        return $modelData;
    }

    /**
     * Installs the model and the associated model in the database. 
     *
     * Returns true on full success, otherwise false. 
     * 
     * @return bool
     */
    public function install()
    {
        if (true == $this->model->install() and true == $this->associatedModel->install()) {
            return true; 
        }
        return false; 
    }

    /**
     * Uninstalls the model and the associated model from the database.
     * 
     * Returns true on full success, otherwise false. 
     *
     * @return bool
     */
    public function uninstall()
    {
        if (true == $this->model->uninstall() and true == $this->associatedModel->uninstall()) {
            return true; 
        }
        return false;       
    }

    /**
     * Deletes all data sets from the model and the associated model and resets 
     * the IDs. 
     * 
     * Returns true on success, otherwise false. 
     *
     * @return bool
     */
    public function clear()
    {
        if (true == $this->model->clear() and true == $this->associatedModel->clear()) {
            return true;
        }
        return false;
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
        if (true == $this->model->delete($where) and true == $this->associatedModel->delete($where)) {
            return true;
        }
        return false;
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
        return $this->find(1, $fields, $options);
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
        foreach ($where as $field => $value) {
            if (!in_array($field, $model1Fields, true) or !in_array($field, $model2Fields, true)) {
                continue;
            }
            // rename field
            $newField = sprintf('%s.%s', $this->model->getTableName(), $field);
            $where[$newField] = $where[$field];
            unset($where[$field]);
        }
        return $where;
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
        foreach ($fields as $field) {
            if (!in_array($field, $model1Fields, true) or !in_array($field, $model2Fields, true)) {
                continue;
            }
            // rename field
            $index    = array_search($field, $fields);
            $newField = sprintf('%s.%s AS %s', $this->model->getTableName(), $field, $field);
            $fields[$index] = $newField;
        }
        return $fields;
    }
    
}
