<?php

/**
 * File description
 *
 * license text
 * 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @version    $Id: Interface.php,v 1.1 2007-12-12 18:12:09 lars Exp $
 * @copyright  Copyright 2007 Lars Tiedemann
 * @since      17.08.2007
 */

/**
 * LTC_Model_Interface
 * 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @package    LTC
 * @subpackage Model
 * @since      litecoms-0.0.1
 */
interface LTC_Model_Interface
{

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
    public function getAllFieldProperties();

    /**
     * Returns an associative array of field properties.
     *
     * If the field does not exist, false is returned. 
     * This method is needed by LTC_Db to process models. 
     * 
     * @param string field name
     * @return mixed
     */
    public function getFieldProperties($fieldName);

    /**
     * Returns the name of the current model.
     *
     * @return string model name
     */
    public function getName();
    
    /**
     * Returns the table name of the table model.
     * 
     * The table name consists of the table prefix given by 
     * LTC_Db::getTablePrefix() and the model name LTC_Model::getName(). The
     * full table name is always strictly lowercase. 
     * 
     * @return string
     */
    public function getTableName();

    /**
     * Inserts a new data set into the current model and returns the ID of the 
     * new data set.
     * 
     * The ID is the value of the primary key field.   
     *
     * @param array 
     * @return int new ID
     */
    public function insert(array $data);

    /**
     * Updates the data set(s) matching $where.
     * 
     * Returns true on success, otherwise false. 
     *
     * @param array where
     * @param array data
     * @return bool
     */
    public function update($where, array $data);

    /**
     * Deletes the data that matches the given where argument. 
     * 
     * Returns true on success, otherwise false. 
     *
     * @param array where
     * @return bool
     */
    public function delete($where);

    /**
     * Searches for data in the model matching the given where conditions. 
     * 
     * Returns an array containing the results. 
     * If no results match the where condition(s), an empty array is returned. 
     * 
     * $where is an array of one of the following forms:
     * 
     * array(
     *     field1 => value1,
     *     field2 => value2,
     *     ...
     * )
     * 
     * or 
     *    
     * array(
     *     condition1, 
     *     condition2,
     *     ...
     * ) 
     * 
     * example for condition:
     * "field1 <= 'value2'"
     * 
     * $fields is an array of fields that are returned as keys. 
     * 
     * $result = array(
     *     field1 => value1,
     *     field2 => value2,
     *     ...
     * );
     * 
     * $options is an associative array with query options that are specific 
     * to the database driver used, so be careful with this. 
     * Common SQL-based options are 'limit', 'order_by' and 'group_by':
     * $options = array(
     *     'limit' => "5, 10",
     *     'order_by' => "field1",
     *     'group_by' => "field2",
     * );
     *
     *
     * @param array
     * @param array
     * @param array
     * @return array
     */
    public function find($where, $fields = array(), $options = array());

    /**
     * Installs the current model in the database. 
     *
     * Returns true on success, otherwise false. 
     * 
     * @return bool
     */
    public function install();

    /**
     * Uninstalls the current model from the database.
     * 
     * Returns true on success, otherwise false. 
     *
     * @return bool
     */
    public function uninstall();

    /**
     * Deletes all data sets from the current model and resets the IDs. 
     * 
     * Returns true on success, otherwise false. 
     *
     * @return bool
     */
    public function clear();
    
    /**
     * Initializes the model object.
     * 
     * Every subclass should overwrite this method. 
     * 
     * @return void
     */
    public function init();
    
    /**
     * Returns the field name of the primary key field. 
     * 
     * If the primary key consists of more than one field, an array with all
     * primary key field names is returned. If no primary key exists, false is
     * returned. 
     *
     * @return mixed
     */
    public function getPrimaryKey();

} 


