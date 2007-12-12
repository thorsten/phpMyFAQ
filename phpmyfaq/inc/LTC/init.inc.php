<?php

/**
 * Sets the include path environment variable, 
 * includes required files and starts the session.
 *
 * license text
 * 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @version    $Id: init.inc.php,v 1.1 2007-12-12 18:12:09 lars Exp $
 * @copyright  Copyright 2007 Lars Tiedemann
 * @since      29.09.2007
 * @package    LTC
 */


/**
 * Automatically inlucdes class files with LTC naming conventions. 
 * Classes named something like this LTC_My_Class would be 
 * expected in LTC/My/Class.php from any include_path. 
 *
 * @access public
 * @param string class name
 * @return void
 */
function ltc_autoload($className)
{    
    // prepend 'LTC_' if missing
    if (strpos($className, 'LTC_') !== 0) {
        $className = 'LTC_' . $className;
    }
    // try to include the class file from any include_path
    require_once str_replace('_', '/', $className) . '.php';
}

/**
 * Register the autoload function
 */
spl_autoload_extensions('.php');
spl_autoload_register('ltc_autoload');

/**
 * add the root directory to the include path
 */ 
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . dirname(dirname(__FILE__)));
  
/**
 * start the session
 */
session_start();

