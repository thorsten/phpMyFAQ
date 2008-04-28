<?php

/**
 * File description
 *
 * license text
 * 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @version    $Id: Interface.php,v 1.2 2008-04-28 21:14:20 lars Exp $
 * @copyright  Copyright 2007 Lars Tiedemann
 * @since      litecoms-0.0.1
 */


/**
 * The database layer interface
 *
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @package    LTC
 * @subpackage Db
 * @since      litecoms-0.0.1
 */
interface LTC_Db_Interface
{
    
    /**
     * Updates one or several rows in the model and returns the
     * number of affected rows. If an error occurs, false is 
     * returned. 
     *
     * $where is an array of where clauses.   
     * $data is either an associative array containing a single
     * new data set or an array of data sets that should be 
     * updated.
     *
     * @param LTC_Model
     * @param array
     * @param array
     * @return int
     */
    public function update(LTC_Model $model, $where, $data);

    /**
     * Inserts one data set in the model.
     *        
     * $data is an associative array containing a single new data set with keys
     * as field names and their specific values. 
     *
     * @param LTC_Model
     * @param array
     * @return mixed
     */
    public function insert(LTC_Model $model, array $data);

    /**
     * Deletes one or more entries from the given model. 
     *
     * @param LTC_Model
     * @param array
     * @return bool
     */
    public function delete(LTC_Model $model, $where);

    /**
     * Searches for data in the model matching the given where conditions. 
     * 
     * Returns an array containing the results. 
     * $options is an associative array containing optional query parameters.
     * Commonly supported options are: 
     *     order_by
     *     order_reverse
     *     limit
     *     offset
     *     group_by
     *
     * @param mixed Either an LTC_Model_Interface instance or an array thereof. 
     * @param array
     * @param array
     * @param array
     * @return array
     */
    public function find($models, $where, $fields = array(), $options = array());

    /**
     * Establishes a connection to the datasource.
     *
     * Returns true on success, otherwise false. 
     * 
     * @param array Associative array with information needed to connect to the 
     * 				datasource
     * @return bool
     */
    public function connect(array $parameters);

    /**
     * Installs the model in the datasource
     *
     * Returns true on success, otherwise false. 
     *
     * @param LTC_Model
     * @return bool
     */
    public function install(LTC_Model $model);

    /**
     * Uninstalls the model in the datasource.
     *
     * Returns true on success, otherwise false. 
     *
     * @param LTC_Model
     * @return bool
     */
    public function uninstall(LTC_Model $model);

    /**
     * Deletes all data entries from the model. 
     *
     * Returns true on success, otherwise false. 
     *
     * @param LTC_Model
     * @return bool
     */
    public function clear(LTC_Model $model);

    /**
     * Disconnects from the datasource. 
     *
     * Returns true on success, otherwise false. 
     *
     * @return bool
     */
    public function disconnect();

    /**
     * Returns an error message.
     *
     * @return string
     */
    public function error();

    /**
     * Returns a new ID for the model. 
     *
     * @param LTC_Model
     * @return mixed
     */
    public function nextId(LTC_Model $model);
    
    /**
     * Escapes a string for use in a query and adds quotes if necessary.
     * 
     * @param string  
     * @return string 
     */
    public function quote($string);

} 


