<?php

/**
 * The config file for the database connection contains an associative array 
 * $db. 
 * 
 * license text
 * 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @version    $Id: db.config.php,v 1.1 2007-12-12 18:12:09 lars Exp $
 * @copyright  Copyright 2007 Lars Tiedemann
 * @since      litecoms-0.0.1
 */

/**
 * The array $db is an associative array containing all necessary information to
 * instantiate a database layer object and to connect to the database. 
 * 
 * Example: 
 * 
 * $db = array();
 * // common settings
 * $db['type']   = 'pdo';       // Use LTC_Db_Pdo as Db layer
 * $db['prefix'] = 'ltc_';      // The table prefix used for Litecoms tables
 * // connection settings depending on $db['type']
 * $db['driver'] = 'mysql';     // Set the PDO driver to 'MySQL'
 * $db['host']   = 'localhost'; // The host (server) with the database 
 * $db['user']   = 'litecoms';  // This user used for the database connection
 * $db['pass']   = 'ltc';       // The password used for the database connection
 * $db['dbname'] = 'litecoms2'; // The name of the database
 * 
 * @var array associative array with database settings
 */
$db = array();
// common settings
$db['type']   = 'pdo';       // Use LTC_Db_Pdo as Db layer
$db['prefix'] = 'ltc_';      // The table prefix used for Litecoms tables
// connection settings depending on $db['type']
$db['driver'] = 'mysql';     // Set the PDO driver to 'MySQL'
$db['host']   = 'localhost'; // The host (server) with the database 
$db['user']   = 'litecoms';  // This user used for the database connection
$db['pass']   = 'ltc';       // The password used for the database connection
$db['dbname'] = 'litecoms2'; // The name of the database


