<?php

/**
 * File description
 *
 * license text
 * 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @version    $Id: Decorator.php,v 1.1 2007-12-12 18:12:09 lars Exp $
 * @copyright  Copyright 2007 Lars Tiedemann
 * @since      17.08.2007
 */

/**
 * LTC_Model_Decorator
 * 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @package    LTC
 * @subpackage Model
 * @since      litecoms-0.0.1
 */
abstract class LTC_Model_Decorator
    extends LTC_Model_Abstract
    implements LTC_Model_Interface
{

    /**
     * Wrapped model instance to implement the decorator design pattern
     * 
     * @var LTC_Model subclass of LTC_Model
     */
    protected $model = null;
    

    /**
     * Creates a new LTC_Model_Decorator. 
     * 
     * An optional LTC_Model (or a subclass thereof) may be passed to implement
     * the decorator design pattern. 
     *
     * @param LTC_Model_Interface
     * @return void
     */
    public function __construct(LTC_Model_Interface $model = null)
    {
        // wrap the given $model
        if ($model != null) {
            $this->model = $model;
        } else {
            // if no model is given for wrapping, call the default constructor
            parent::__construct();
        }
    }
    
    /**
     * Call methods of the subclass if they are not defined here.
     * 
     * @param string method name
     * @param array arguments
     * @return mixed
     */
    /*public function __call($method, $arguments = array()) 
    {
        if ($this->model instanceof LTC_Model_Interface) {
            return call_user_func_array(array($this->model, $method), $arguments);
        }
    }*/

    /**
     * Returns an array of all field objects in the model. 
     *
     * @return array array properties[, array properties[, ...]]
     */
    public function getAllFieldProperties()
    {
        return $this->model->getAllFieldProperties();
    }

    /**
     * Returns the field properties of the given field name
     *
     * @param string field name
     * @return object
     */
    public function getFieldProperties($fieldName)
    {
        return $this->model->getFieldProperties($fieldName);
    }

    /**
     * Returns the name of the current model
     *
     * @return string model name
     */
    public function getName()
    {
        return $this->model->getName();
    }
    
    /**
     * Returns the table name of the table model.
     * 
     * The table name consists of the table prefix given by 
     * LTC_Db::getTablePrefix() and the model name LTC_Model::getName(). The
     * full table name is always strictly lowercase. 
     * 
     * @return string
     */
    public function getTableName()
    {
        return $this->model->getTableName();
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
        return $this->model->insert($data);
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

    /**
     * Searches for data in the model matching the given where conditions. 
     * 
     * Returns an array containing the results. 
     *
     * @access public
     * @param array
     * @param array
     * @param array
     * @return array
     */
    public function find($where, $fields = array(), $options = array())
    {
        return $this->model->find($where, $fields, $options);
    }

    /**
     * Installs the current model in the database. 
     *
     * Returns true on success, otherwise false. 
     * 
     * @return bool
     */
    public function install()
    {
        return $this->model->install();
    }

    /**
     * Uninstalls the current model from the database.
     * 
     * Returns true on success, otherwise false. 
     *
     * @return bool
     */
    public function uninstall()
    {
        return $this->model->uninstall();
    }

    /**
     * Deletes all data sets from the current model and resets the IDs. 
     * 
     * Returns true on success, otherwise false. 
     *
     * @return bool
     */
    public function clear()
    {
        return $this->model->clear();
    }
    
    /**
     * Initializes the model object.
     * 
     * Every subclass should overwrite this method. 
     * 
     * @return void
     */
    public function init()
    {
        return $this->model->init();
    }
    
    /**
     * Returns the field name of the primary key field. 
     * 
     * If the primary key consists of more than one field, an array with all
     * primary key field names is returned. If no primary key exists, false is
     * returned. 
     *
     * @return mixed
     */
    public function getPrimaryKey()
    {
        return $this->model->getPrimaryKey();
    }
    
}
