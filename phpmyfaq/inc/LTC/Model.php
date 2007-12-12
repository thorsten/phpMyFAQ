<?php

/**
 * File description
 *
 * license text
 * 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @version    $Id: Model.php,v 1.1 2007-12-12 18:12:09 lars Exp $
 * @copyright  Copyright 2007 Lars Tiedemann
 * @since      litecoms-0.0.1
 */


/**
 * The basic class for the model layer in the LTC MVC design. 
 * 
 * To use a model object, simply call LTC_Model::getModel('MyModel'). This will
 * return an instance of the class LTC_Model_MyModel. Models created with 
 * LTC_Model::getModel() are stored in a registry, so you always get the same 
 * instance unless using LTC_Model::getModel() with the second optional 
 * parameter set to true.    
 * 
 * If you are not familiar with the terms MVC or model layer, you should 
 * definitely read about it before laying your hands on here :-)
 * 
 * A Model, in its simplest form, represents a single table in the database. 
 * The Model provides means to manipulate, add and delete data in the table. 
 * 
 * You won't find any database queries here in the model as all database 
 * operations are being performed by the database layer LTC_Db. 
 * 
 * Of course, each model (aka database table) consists of fields (columns in the
 * database table). Field definitions and properties are stored in the  
 * $fields variable. The definitions are set in the init() function. 
 * 
 * Each Model object must extend LTC_Model to provide a clean API. A Model class 
 * must be named LTC_Model_MyModelName where MyModelName is the Model name. The
 * Model name is also equal to the file name followed by .php, of course (in 
 * this case: MyModelName.php). The file must be stored in the Model directory
 * (/LTC/Model/) to comply with LTC naming conventions. 
 *
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @package    LTC
 * @subpackage Model
 * @since      litecoms-0.0.1
 */
abstract class LTC_Model
    implements LTC_Model_Interface
{

    /**
     * An associative array containing field properties
     * 
     * $fields = array(
     *     'fieldName' => array(
     *         'primary_key' => false,
     * 		   'auto_increment' => false,
     *         'foreign_key' => false,
     *         'type' => 'text' | 'longtext' | 'int' | 'double'... , 
     *         'length' => 10,
     *         'null' => true,
     *         'default' => '',
     *         'description' => '',
     *         'options' => '',
     *         'attributes' => array(),
     *     ),
     * );
     *
     * @var array
     */
    protected $fields = array();

    /**
     * The name of the model.
     * 
     * The model name equals the class name of the model without the 
     * 'LTC_Model_' prefix. The model name is case sensitive and begins with 
     * an uppercase letter. Example:
     * 
     * class name: LTC_Model_MyTable
     * model name: MyTable
     *
     * The model name is set in the constructor and cannot be changed to keep 
     * the naming conventions. You may retrieve the model name using getName(). 
     * 
     * @var string The model name equals the model class name.
     */
    protected $name = '';
    
    
    /**
     * Creates a new LTC_Model instance. 
     * 
     * The constructor DOES NOT call the init() method automatically. The idea 
     * is to get a blank object without field properties and definitions when 
     * instantiating a model. You have to call init() yourself as this is the 
     * place where field definitions and options are to be set. Only 
     * LTC_Model::getModel() automatically calls init(), so this is the 
     * convenient way of using predefined models.  
     * 
     * @param LTC_Model Subclass of LTC_Model
     * @return void
     */
    public function __construct()
    {
        // automatically set the model name
        $this->name = substr(get_class($this), strlen('LTC_Model_'));
    }
    
    /**
     * The behaviour of object cloning can be influenced here. 
     * 
     * The PHP magic method __clone() may be overwritten by subclasses to 
     * influence the behaviour of LTC_Model::getModel() with the second optional
     * parameter set to true. 
     * 
     * @return void
     */
    public function __clone()
    {}

    /**
     * Returns a model object. 
     *
     * The model class name is LTC_Model_$name by convention. 
     * All models created with LTC_Model::getModel() are stored in an internal
     * registry. If the optional parameter getCopy is true, a copy of the 
     * instantiated model is returned (using the clone operator), otherwise the 
     * instance from the registry is returned. 
     * 
     * @param string model name
     * @param bool If $getCopy is true, a copy of the model stored in the 
     *             registry is returned. By default, the instance from the 
     *             registry is returned directly without making a copy of it. 
     * @return LTC_Model 
     */
    public static function getModel($name, $getCopy = false)
    {
        // static 'registry'
        static $models = array();
        // instantiate the model if it does not exist in registry 
        if (!isset($models[$name])) {
            $className = 'LTC_Model_' . ucfirst($name);
            $models[$name] = new $className();
            $models[$name]->init();
        }
        // return the stored instance or a copy
        if ($getCopy !== true) {
            return $models[$name];
        }
        return clone $models[$name];
    }

    /**
     * Returns an array of all field objects in the model. 
     *
     * @return array array properties[, array properties[, ...]]
     */
    public function getAllFieldProperties()
    {
        return $this->fields;
    }

    /**
     * Returns an associative array with the field properties.
     * 
     * If the field does not exist, false is returned. 
     *
     * @param string field name
     * @return mixed
     */
    public function getFieldProperties($fieldName)
    {
        if (!isset($this->fields[$fieldName])) {
            return false;
        }
        return $this->fields[$fieldName];
    }

    /**
     * Returns the name of the current model
     *
     * @return string model name
     */
    public function getName()
    {
        return $this->name;
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
        $db = LTC_Db::getInstance();
        return strtolower($db->getTablePrefix().$this->getName());
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
        $db = LTC_Db::getInstance();
        return $db->insert($this, $data);
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
        $db = LTC_Db::getInstance();
        return $db->update($this, $where, $data);
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
        $db = LTC_Db::getInstance();
        return $db->delete($this, $where);
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
        $db = LTC_Db::getInstance();
        return $db->find($this, $where, $fields, $options);
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
        $db = LTC_Db::getInstance();
        return $db->install($this);
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
        // remove table
        $db = LTC_Db::getInstance();
        $result = $db->uninstall($this);
        if ($result !== true) {
            return false;
        }
        // primary key
        $primaryKey   = $this->getPrimaryKey();
        $primaryField = $this->getFieldProperties($primaryKey);
        // check auto_increment option
        if (!isset($primaryField['auto_increment']) or $primaryField['auto_increment'] == false) {
            return true;
        }
        // remove sequencetable entry
        $sequence    = LTC_Model::getModel('SequenceTable');
        $sequenceKey = $sequence->getPrimaryKey();
        $sequence->delete(array($sequenceKey => $primaryKey));
        return true;
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
        $db = LTC_Db::getInstance();
        return $db->clear($this);
    }
    
    /**
     * Initializes the model object.
     * 
     * Every subclass should overwrite this method. 
     * 
     * @return void
     */
    public function init()
    {}
    
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
        $pk = array();
        foreach ($this->getAllFieldProperties() as $field => $properties) {
            if (isset($properties['primary_key']) and $properties['primary_key'] == true) {
                $pk[] = $field;
            }
        }
        if (count($pk) <= 0) {
            return false;
        }
        if (count($pk) == 1) {
            return $pk[0];
        }
        return $pk;
    }
    
    /**
     * Returns a new LTC_Model_Decorator_HasOne instance
     * 
     * @param LTC_Model_Interface
     * @param LTC_Model_Interface
     * @return LTC_Model_Decorator_HasOne
     */
    public static function hasOne(LTC_Model_Interface $model, LTC_Model_Interface $associatedModel) 
    {
        $model = new LTC_Model_Decorator_HasOne($model, $associatedModel);
        return $model;
    }
    
    /**
     * Returns a new LTC_Model_Decorator_HasMany instance
     * 
     * @param LTC_Model_Interface
     * @param LTC_Model_Interface
     * @return LTC_Model_Decorator_HasMany
     */
    public static function hasMany(LTC_Model_Interface $model, LTC_Model_Interface $associatedModel) 
    {
        $model = new LTC_Model_Decorator_HasMany($model, $associatedModel);
        return $model;
    }
    
    /**
     * Returns a new LTC_Model_Decorator_BelongsTo instance
     * 
     * @param LTC_Model_Interface
     * @param LTC_Model_Interface
     * @return LTC_Model_Decorator_BelongsTo
     */
    public static function belongsTo(LTC_Model_Interface $model, LTC_Model_Interface $associatedModel) 
    {
        $model = new LTC_Model_Decorator_BelongsTo($model, $associatedModel);
        return $model;
    }
    
    /**
     * Returns a new LTC_Model_Decorator_HasAndBelongsTo instance
     * 
     * @param LTC_Model_Interface
     * @param LTC_Model_Interface
     * @return LTC_Model_Decorator_HasAndBelongsTo
     */
    public static function hasAndBelongsTo(LTC_Model_Interface $model, LTC_Model_Interface $associatedModel) 
    {
        $model = new LTC_Model_Decorator_HasAndBelongsTo($model, $associatedModel);
        return $model;
    }

}
