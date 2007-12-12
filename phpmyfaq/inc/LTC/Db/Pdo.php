<?php

/**
 * File description
 *
 * license text
 * 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @version    $Id: Pdo.php,v 1.1 2007-12-12 18:12:09 lars Exp $
 * @copyright  Copyright 2007 Lars Tiedemann
 * @since      litecoms-0.0.1
 */

/**
 * This class provides means to use the default PHP PDO database object 
 * abstraction as the prime database layer. 
 * 
 * The PDO database abstraction layer is a standard PHP object that is able to 
 * handle various database types, e.g. MySQL, PostgreSQL, MSSQL etc. 
 * 
 * Use the database config file to change the database layer or to edit the 
 * connection parameters. See the LTC_DB_CONFIGFILE constant where the config
 * file is located. To create a database object, simply call 
 * LTC_Db::getInstance() which automatically reads the config file and returns
 * a ready-to-go database layer object. 
 *  
 * Set the $db['type'] to 'pdo' in order to use the LTC_Db_Pdo database layer. 
 * Required connection parameters are:
 * 
 * $db['driver'] = 'mysql';     // Set the PDO driver to 'MySQL'
 * $db['host']   = 'localhost'; // The host (server) with the database 
 * $db['user']   = 'litecoms';  // This user used for the database connection
 * $db['pass']   = 'ltc';       // The password used for the database connection
 * $db['dbname'] = 'litecoms2'; // The name of the database
 *
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @package    LTC
 * @subpackage Db
 * @since      litecoms-0.0.1
 */
class LTC_Db_Pdo
    extends LTC_Db
    implements LTC_Db_Interface
{

    /**
     * Holds an instance of the standard PHP PDO class. 
     *
     * @var PDO
     */
    protected $pdo = null;


    /**
     * Establishes a connection to the datasource.
     * 
     * Creates a new PDO object using the given parameters. 
     * $parameters must contain the following keys with correct values:
     * $parameters['user'] the username used to connect to the database
     * $parameters['pass'] the password used to connect to the database
     * $parameters['driver'] e.g. 'mysql' for a MySQL database
     * $parameters['host'] the database server name or IP
     * $parameters['dbname'] the database name
     *
     * Returns true on success, otherwise false. 
     * 
     * @param array Associative array with information needed to connect to the 
     * 				datasource
     * @return bool
     */
    public function connect(array $parameters)
    {
        // TODO: check $parameters and throw exceptions
        $dsn = sprintf(
            '%s:host=%s;dbname=%s',
            strtolower($parameters['driver']),
            $parameters['host'],
            $parameters['dbname']
        );
        $this->pdo = new PDO($dsn, $parameters['user'], $parameters['pass']);
        // check and return boolean 
        if ($this->pdo instanceof PDO) {
            return true;
        }
        $this->pdo = null;
        return false;
    }
    
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
    public function update(LTC_Model $model, $where, $data)
    {
        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $model->getTableName(),
            $this->sqlSet($data),
            $this->sqlWhere($where)
        );
        $result = $this->sqlQuery($sql);
        if (($result instanceof PDOStatement) == false) {
            return false;
        }
        if (intval($result->errorCode()) == 0) {
            return true;
        }
        return false;
    }

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
    public function insert(LTC_Model $model, array $data)
    {
        // primary key
        $pk = $model->getPrimaryKey();
        $primaryField = $model->getFieldProperties($pk);
        // check auto_increment option
        if (isset($primaryField['auto_increment']) and $primaryField['auto_increment'] == true) {
            $data[$pk] = $this->nextId($model);
        }
        $sql = sprintf(
            'INSERT INTO %s SET %s',
            $model->getTableName(),
            $this->sqlSet($data)
        );
        $result = $this->sqlQuery($sql);
        if (($result instanceof PDOStatement) == false) {
            return false;
        }
        if ($result->rowCount() == 0) {
            return false;
        }
        // return inserted ID
        return $data[$pk];
    }

    /**
     * Deletes one or more entries from the given model. 
     *
     * @param LTC_Model
     * @param array
     * @return bool
     */
    public function delete(LTC_Model $model, $where)
    {
        $sql = sprintf(
            'DELETE FROM %s WHERE %s',
            $model->getTableName(),
            $this->sqlWhere($where)
        );               
        $result = $this->sqlQuery($sql);
        if (($result instanceof PDOStatement) == false) {
            return false;
        }
        if ($result->rowCount() == 0) {
            return false;
        }
        if (intval($result->errorCode()) == 0) {
            return true;
        }
        return false;
    }

    /**
     * Searches for data in the model matching the given where conditions. 
     * 
     * Returns an array containing the results. 
     *
     * @param mixed Either an LTC_Model_Interface instance or an array thereof. 
     * @param array
     * @param array
     * @param array
     * @return array
     */
    public function find($models, $where, $fields = array(), $options = array())
    {
        $tables = array();
        if (!is_array($models)) {
            $models = array($models);
        }
        foreach ($models as $model) {
            $tables[] = $model->getTableName();
        }
        if (!is_array($fields)) {
            $fields = array($fields);
        }
        if (count($fields) == 0) {
            $fields = $model->getAllFieldProperties();
            $fields = array_keys($fields);
        }
        $sql = trim(sprintf(
            'SELECT %s FROM %s WHERE %s %s',
            $this->sqlSelect($fields),
            $this->sqlFrom($tables),
            $this->sqlWhere($where),
            $this->sqlOptions($options)
        ));
        $result = $this->sqlQuery($sql);
        if ($result == false) {
            return false;
        }
        if (($result instanceof PDOStatement) == false) {
            return false;
        }
        $data = array();
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $data[] = $row;
        }
        return $data;
    }
    
    /**
     * Searches for data joining the given models that match the where condition
     * 
     * @param array
     * @param array
     * @param array 
     * @param array 
     * @return array
     */
    /*public function findJoin(array $models, array $joinFields, $where, $fields = array())
    {
        if (!is_array($fields)) {
            $fields = array($fields);
        }
        if (count($fields) == 0) {
            foreach ($models as $model) {
                $modelFields = array_keys($model->getAllFieldProperties());
                array_merge($fields, $modelFields);
            }
        }
        $tables = array();
        foreach ($models as $model) {
            $tables[] = $model->getTableName();
        }
        if (!is_array($where)) {
            $where = array($where); 
        }
        $sql = sprintf(
            'SELECT %s FROM %s WHERE %s ',
            $this->sqlSelect($fields),
            $this->sqlFrom($tables),
            $this->sqlWhere(array_merge($joinFields, $where))
        );       
        $result = $this->sqlQuery($sql);
        if ($result == false) {
            return false;
        }
        if (($result instanceof PDOStatement) == false) {
            return false;
        }
        $data = array();
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $data[] = $row;
        }
        return $data;
    }*/

    /**
     * Installs the model in the datasource
     *
     * Returns true on success, otherwise false. 
     *
     * @param LTC_Model
     * @return bool
     */
    public function install(LTC_Model $model)
    {
        // build field definitions
        $statements = array();
        $primaryKey = array();
        foreach ($model->getAllFieldProperties() as $field => $attributes) {
            // field definition statements
            $statements[] = $this->sqlFieldDefinition($field, $attributes['type'], $attributes['length'], $attributes['null'], $attributes['default']);
            // add primary key to list
            if (isset($attributes['primary_key']) and $attributes['primary_key'] === true) {
                $primaryKey[] = $field; 
            }
            // add unique index for this field
            if (isset($attributes['unique']) and $attributes['unique'] === true) {
                $statements[] = $this->sqlIndexDefinition($field, 'unique', array($field));
            }
        }
        // add primary key index statement
        if (count($primaryKey) > 0) {
            $statements[] = $this->sqlIndexDefinition('', 'primary', $primaryKey); 
        }
        // build the complete statement
        $sql = trim(sprintf(
            'CREATE TABLE %s (
                %s
            )',
            $model->getTableName(),
            implode(",\n                ", $statements)
        ));
        $result = $this->sqlQuery($sql);
        if (($result instanceof PDOStatement) == false) {
            return false;
        }
        if (intval($result->errorCode()) == 0) {
            return true;
        }
        return false;
    }

    /**
     * Uninstalls the model in the datasource.
     *
     * Returns true on success, otherwise false. 
     *
     * @param LTC_Model
     * @return bool
     */
    public function uninstall(LTC_Model $model)
    {
        $sql = sprintf(
            'DROP TABLE %s',
            $model->getTableName()
        );
        $result = $this->sqlQuery($sql);
        if (($result instanceof PDOStatement) == false) {
            return false;
        }
        if (intval($result->errorCode()) == 0) {
            return true;
        }
        return false;
        
    }

    /**
     * Deletes all data entries from the model. 
     *
     * Returns true on success, otherwise false. 
     *
     * @param LTC_Model
     * @return bool
     */
    public function clear(LTC_Model $model)
    {
        $sql = sprintf(
            'DELETE FROM %s',
            $model->getTableName()
        );
        $result = $this->sqlQuery($sql);
        // delete sequence table entry
        $key        = $model->getPrimaryKey();
        $keyOptions = $model->getFieldProperties($key);
        if (isset($keyOptions['auto_increment']) and $keyOptions['auto_increment'] == true) {
            $sequenceTable = LTC_Model::getModel('SequenceTable');
            $sequenceTable->delete(array('key_name' => $model->getPrimaryKey()));
        }
        if (($result instanceof PDOStatement) == false) {
            return false;
        }
        if ($result->rowCount() == 0) {
            return false;
        }
        if (intval($result->errorCode()) == 0) {
            return true;
        }
        return false;
    }

    /**
     * Disconnects from the database. 
     *
     * @return bool
     */
    public function disconnect()
    {
        $this->pdo = null;
        return true;
    }

    /**
     * Returns an error message of the last database operation.
     *
     * @return string
     */
    public function error()
    {
        $errorInfo = $this->pdo->errorInfo();
        if (!is_array($errorInfo) or count($errorInfo) < 2) {
            return '';
        }
        return sprintf("%s: %s", (string) $errorInfo[1], $errorInfo[2]);
    }

    /**
     * Returns a new ID for the model. 
     *
     * @param LTC_Model
     * @return mixed
     */
    public function nextId(LTC_Model $model)
    {
        $sequence    = LTC_Model::getModel('SequenceTable');
        $sequenceKey = $sequence->getPrimaryKey();
        //$sequence->install();
        $modelKey = $model->getPrimaryKey();
        $result   = $sequence->find(array($sequenceKey => $modelKey));
        // insert key name
        if ($result == false) {
            $nextId = 1;
            $sequence->insert(array(
                $sequenceKey => $modelKey,
                'key_value'  => $nextId 
            ));            
            return $nextId;
        }
        // update row
        $currentId = $result[0]['key_value'];
        $nextId    = $currentId + 1;
        $sequence->update(array($sequenceKey => $modelKey), array('key_value' => $nextId));
        return $nextId;
    }
    
    /**
     * Escapes a string for use in a query and adds quotes if necessary.
     * 
     * If $value is not a string, it will not be escaped and/or quoted, but it 
     * will be transformed into a string. Example:
     * 
     * quote("this is a string") returns "'this is a string'"
     * quote("this is a 'string'") returns "'this is a \'string\''"
     * quote(234) returns "234"   
     * 
     * @param mixed  
     * @return string 
     */
    public function quote($value)
    {
        // numeric or null needs no quotes 
        if (!is_string($value)) {
            return (string) $value;
        }
        // quote strings
        return (string) $this->pdo->quote($value);
    }

    /**
     * Returns a SET string for use in a SQL query. 
     * 
     * $data is an associative array with field => value pairs. 
     *
     * @param array
     * @return string
     */
    protected function sqlSet(array $data)
    {
        $set = array();
        foreach ($data as $field => $value) {
            $set[] = sprintf('%s=%s', $field, $this->quote($value));
        }
        return implode(', ', $set);
    }

    /**
     * Evaluates a WHERE argument and returns a valid SQL string. 
     * 
     * If the WHERE argument is a string, it is used as is.
     * If the WHERE argument is a simple array, the array elements are used 
     * glued together using 'AND'. If the WHERE argument is an associative 
     * array, the field value pairs are used as search conditions. 
     * 
     * sqlWhere( "blub='bla' OR foo='bar'" ) 
     * returns   "blub='bla' OR foo='bar'"
     * 
     * sqlWhere( array("blub='bla'", "foo<0") )
     * returns   "blub='bla' AND foo<0"
     * 
     * sqlWhere( array('blub' => 'bla', 'foo' => 0) )
     * returns   "`blub`='bla' AND `foo`=0"
     *
     * @param mixed
     * @return string
     */
    protected function sqlWhere($where)
    {
        // $where = "blub='bla' OR foo='bar'"
        if (!is_array($where)) {
            return $where;
        }
        $conditions = array();
        foreach ($where as $index => $value) {
            // $where = array("blub='bla'", "foo='bar'")
            if (is_int($index)) {
                $conditions[] = $value;
                continue;
            }
            // $where = array('blub' => 'bla', 'foo' => 'bar')
            $conditions[] = sprintf('%s=%s', (string) $index, $this->quote($value));
        }
        return implode(' AND ', $conditions);
    }

    /**
     * Returns an SQL SELECT statement for use in a query.
     *
     * @param array
     * @return string
     */
    protected function sqlSelect($fields)
    {
        return implode(', ', $fields);
    }

    /**
     * Returns an SQL FROM statement for use in a query. 
     *
     * @param array
     * @return string
     */
    protected function sqlFrom($tables)
    {
        return implode(', ', $tables);
    }
    
    /**
     * Returns an SQL field definition string that can be used in SQL CREATE or
     * UPDATE statements.
     * 
     * @param string field name
     * @param string SQL field type, e.g. 'text', 'int', 'id' 
     * @param mixed The portions in brackets within a SQL field definition. 
     *     An integer setting the SQL field length (e.g. 50 for a 
     *     VARCHAR(50) SQL field) or a string (e.g. "'yes', 'no'" for a 
     *     ENUM('yes', 'no') SQL field
     * @param bool true for NULL, false for NOT NULL
     * @param mixed Default value. If null, no default value is set. An empty
     *     string is assumed to be the default value ''.     
     * @return string SQL field definition
     */
    protected function sqlFieldDefinition($name, $type, $length = null, $null = true, $default = null)
    {
        // type
        switch (strtolower($type)) {
            case 'id':
                $type    = 'INT';
                $length  = 10;
                $null    = false;
                $default = null;
                break;
            case 'text':
                $length = (int) $length;
                if ($default !== null) {
                    $default = $this->quote((string) $default);
                }
                if ($length > 0 and $length < 255) {
                    $type = 'VARCHAR';
                    break;
                }
                $type = 'TEXT';
                $length = null;
                break;
            case 'int':
                $type = 'INT';
                if ($default !== null) {
                    $default = (int) $default;
                }
                if ($length == null or $length == 0) {
                    $length = 10;
                }
                break;
            case 'list':
                $type = 'ENUM';
                if ($default !== null) {
                    $default = $this->quote((string) $default);
                }
                break;
            case 'datetime':
                $type = 'DATETIME';
                $length = null;
                $default = null;
                break;
            default: 
                $type = strtoupper($type);
                break;
        }
        // length
        $length  = ($length == null or $length == '') ? '' : sprintf('(%s)', $length);
        // null
        $null    = ($null == true) ? 'NULL' : 'NOT NULL';
        // default
        $default = ($default === null) ? '' : sprintf('DEFAULT %s', $this->quote((string) $default));
        return trim(sprintf(
            '%s %s%s %s %s',
            $name,
            $type,
            $length,
            $null,
            $default
        ));
    }
    
    /**
     * Returns a string with SQL index definition statement. 
     * 
     * @param string index name
     * @param string index type
     * @param array index fields
     * @return string SQL index creation statement for use in a query
     */
    protected function sqlIndexDefinition($name, $type, $fields)
    {
        if (!is_array($fields)) {
            $fields = array($fields);
        }
        switch (strtolower($type)) {
            case 'primary':
                $type = 'PRIMARY KEY';
                $name = '';
                break;
            case 'primary key':
                $type = 'PRIMARY KEY';
                $name = '';
                break;
            case 'fulltext':
                $type = 'FULLTEXT';
                break;
            case 'unique':
                $type = 'UNIQUE';
                break;
            default:
                $type = 'INDEX';
                break;
        }
        return trim(sprintf(
            '%s %s (%s)',
            $type,
            $name,
            implode(', ', $fields)
        ));
    }
    
    /**
     * Performs an SQL query and returns the result as a PDO_Statement object. 
     * 
     * @param string SQL query
     * @return mixed
     */
    protected function sqlQuery($query)
    {
        $this->log($query);
        return $this->pdo->query($query);
    }
    
    /**
     * Returns an SQL string with query options.
     * 
     * $options is an associative array. Allowed keys are:
     * 'limit', 'order_by', 'order_reverse' and 'group_by'.
     * 
     * 'limit': integer
     * 'offset': integer
     * 'order_by': string "field" or array ("field1", "field2",...) 
     * 'order_reverse': bool
     * 
     * @param array
     * @return string
     */
    protected function sqlOptions(array $options)
    {
        // limit and offset
        $limit = '';
        if (isset($options['limit'])) {
            $offset = isset($options['offset']) ? $options['offset'] : 0;
            $limit  = sprintf('LIMIT %d, %d', $offset, $options['limit']);
        }
        // order_by and order_reverse
        $orderBy = '';
        if (isset($options['order_by'])) {
            $orderDir = (isset($options['order_reverse']) and $options['order_reverse'] == true) ? 'DESC' : 'ASC';
            if (!is_array($options['order_by'])) {
                $options['order_by'] = array($options['order_by']); 
            }
            $orderBy  = sprintf(
                'ORDER BY %s %s', 
                implode(', ', $options['order_by']), 
                $orderDir
            );
        }
        // group_by
        $groupBy = '';
        if (isset($options['group_by'])) {
            if (!is_array($options['group_by'])) {
                $options['group_by'] = array($options['group_by']); 
            }
            $groupBy  = sprintf(
                'GROUP BY %s', 
                implode(', ', $options['group_by'])
            );
        }
        $sqlOptions = trim(sprintf(
            '%s %s %s',
            $groupBy,
            $orderBy,
            $limit 
        ));
        return $sqlOptions;
    }

}
