<?php   

/**
 * File description
 *
 * license text
 * 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @version    $Id: Db.php,v 1.1 2007-12-12 18:12:09 lars Exp $
 * @copyright  Copyright 2007 Lars Tiedemann
 * @since      litecoms-0.0.1
 */

/**
 * Path and filename to db config file 
 *
 * @var string
 */
define('LTC_DB_CONFIGFILE', 'db.config.php');


/**
 * The basic abstract database layer class should be extended by every database
 * layer. 
 * 
 * A database layer class performs all update, find, delete, install, uninstall 
 * and insert operations. No other class should use SQL queries at all, not even
 * Model subclasses. Instead, they should call the Db methods passing the Model
 * instance. 
 * 
 * LTC_Db is a Singleton class. Calling LTC_Db::getInstance() always returns the 
 * same LTC_Db instance without any loss of performance. When 
 * LTC_Db::getInstance() is called the first time, a new LTC_Db instance will be
 * created according to the settings in the Db config file (see the 
 * LTC_DB_CONFIGFILE constant where this is located).   
 *
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @package    LTC
 * @subpackage Db
 * @since      litecoms-0.0.1
 */
abstract class LTC_Db
    implements LTC_Singleton 
{

    /**
     * The table prefix
     *
     * @var string
     */
    protected $tablePrefix = '';

    /**
     * The type (subclass) of the current instance. 
     * This parameter is set when using LTC_Db::createDb() is used.
     *
     * @var string
     */
    protected $type = '';

    /**
     * The singleton instance
     *
     * @var Db
     */
    private static $instance = null;

    /**
     * The config array
     *
     * @var array
     */
    protected $config = array();  

    /**
     * The query log
     *
     * @var array
     */
    protected $log = array();


    /**
     * Returns the table prefix. 
     *
     * @return string
     */
    public function getTablePrefix()
    {
        $config = $this->getConfig();
        if (!isset($config['prefix'])) {
            return '';
        }
        return $config['prefix'];
    }
    
    /**
     * Returns the type of the current instance. 
     *
     * @return string type
     */
    public function getType() 
    {
        return $this->type;
    }

    /**
     * Creates a new Db subclass. Available subtypes are set by
     * LTC_Db::getSupportedDbTypes().  
     *
     * @param string database type
     * @return object Subclass of LTC_Db
     */
    public static function createDb($dbType)
    {
        $dbTypes = LTC_Db::getSupportedDbTypes();
        $dbType = strtolower($dbType);
        if (!isset($dbTypes[$dbType])) {
            // TODO: throw exception here
            return false;
        }
        $dbObject = new $dbTypes[$dbType]();
        if (false == ($dbObject instanceof LTC_Db)) {
            // TODO: throw exception here 
            return false;
        }
        $dbObject->type = $dbType;
        return $dbObject;
    }

    /**
     * Sets the db-config array
     *
     * @param array
     * @return void
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * Returns the current db-config array. 
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }
    
    /**
     * Returns an associative array with supported database
     * types. 
     *
     * @return array
     */
    public static function getSupportedDbTypes()
    {
        return array(
            'pdo' => 'LTC_Db_Pdo',
        );
    }
    
    /**
     * Logs a query
     *
     * @param string log string
     * @return void
     */
    protected function log($query)
    {
        $this->log[] = $query;
    }
    
    /**
     * Returns the full query log string 
     *
     * @return string
     */
    public function getLog()
    {
        return implode("\n", $this->log);
    }


    // --- The methods to implement LTC_Singleton interface ---

    /**
     * Returns the same instance of the LTC_Db implementing the Singleton design
     * pattern. 
     *
     * @return LTC_Singleton
     */
    public static function getInstance()
    {
        if (self::$instance !== null) {  
            return self::$instance;
        } 
        // load db from config file
        include LTC_DB_CONFIGFILE;
        // create the instance and check for LTC_Db_Interface
        self::$instance = LTC_Db::createDb($db['type']);
        if (false == (self::$instance instanceof LTC_Db_Interface)) {
            self::$instance = null;
            return null;
        }
        // initialize the instance
        self::$instance->setConfig($db);
        self::$instance->init();
        return self::$instance;
    }

    /**
     * Initializes the Db instance. 
     *
     * @return void
     */
    public function init()
    {
        $db = $this->getConfig();
        $this->type = strtolower($db['type']);
        $this->tablePrefix = $db['prefix'];
        $this->connect($db);
    }

}


