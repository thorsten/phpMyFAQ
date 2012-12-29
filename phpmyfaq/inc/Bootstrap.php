<?php
/**
 * Bootstrap phpMyFAQ
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Configuration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-03-07
 */

use Symfony\Component\ClassLoader\UniversalClassLoader;

//
// Debug mode:
// - false      debug mode disabled
// - true       debug mode enabled
//
define('DEBUG', true);
if (DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL | E_STRICT);
} else {
    error_reporting(0);
}

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

//
// Fix the PHP include path if PMF is running under a "strange" PHP configuration
//
$foundCurrPath = false;
$includePaths  = explode(PATH_SEPARATOR, ini_get('include_path'));
$i             = 0;
while ((!$foundCurrPath) && ($i < count($includePaths))) {
    if ('.' == $includePaths[$i]) {
        $foundCurrPath = true;
    }
    $i++;
}
if (!$foundCurrPath) {
    ini_set('include_path', '.' . PATH_SEPARATOR . ini_get('include_path'));
}

//
// Tweak some PHP configuration values
// Warning: be sure the server has enough memory and stack for PHP
//
ini_set('pcre.backtrack_limit', 100000000);
ini_set('pcre.recursion_limit', 100000000);

//
// Check if multisite/multisite.php exist for Multisite support
//
if (file_exists(__DIR__ . '/../multisite/multisite.php') && 'cli' !== PHP_SAPI) {
    require __DIR__ . '/../multisite/multisite.php';
}

//
// Read configuration and constants
//
if (! defined('PMF_MULTI_INSTANCE_CONFIG_DIR')) {
    // Single instance configuration
    define('PMF_CONFIG_DIR', dirname(__DIR__) . '/config');
} else {
    // Multi instance configuration
    define('PMF_CONFIG_DIR', PMF_MULTI_INSTANCE_CONFIG_DIR);
}

//
// Check if config/database.php exist -> if not, redirect to installer
//
if (!file_exists(PMF_CONFIG_DIR . '/database.php')) {
    header("Location: install/setup.php");
    exit();
}

require PMF_CONFIG_DIR . '/database.php';
require PMF_CONFIG_DIR . '/constants.php';

if (!defined('PMF_ROOT_DIR')) {
    /**
     * The root directory
     */
    define('PMF_ROOT_DIR', dirname(__DIR__));
}

/**
 * The include directory
 */
define('PMF_INCLUDE_DIR', __DIR__);

/**
 * The directory where the translations reside
 */
define('PMF_LANGUAGE_DIR', dirname(__DIR__) . '/lang');

//
// Setting up PSR-0 autoloader for Symfony Components
//
require PMF_INCLUDE_DIR . '/libs/Symfony/Component/ClassLoader/UniversalClassLoader.php';

$loader = new UniversalClassLoader();
$loader->registerNamespace('Symfony', PMF_INCLUDE_DIR . '/libs');
$loader->registerPrefix('PMF_', PMF_INCLUDE_DIR);
$loader->register();

//
// Set the error handler to our pmf_error_handler() function
//
set_error_handler('pmf_error_handler');

//
// Create a database connection
//
PMF_Db::setTablePrefix($DB['prefix']);
$db = PMF_Db::factory($DB['type']);
$db->connect($DB['server'], $DB['user'], $DB['password'], $DB['db']);

//
// Fetch the configuration and add the database connection
//
$faqConfig = new PMF_Configuration($db);
$faqConfig->getAll();

//
// We always need a valid session!
//
ini_set('session.use_only_cookies', 1); // Avoid any PHP version to move sessions on URLs
ini_set('session.auto_start', 0);       // Prevent error to use session_start() if it's active in php.ini
ini_set('session.use_trans_sid', 0);
ini_set('url_rewriter.tags', '');

//
// Start the PHP session
//
PMF_Init::cleanRequest();
session_name(PMF_Session::PMF_COOKIE_NAME_AUTH);
session_start();

//
// Connect to LDAP server, when LDAP support is enabled
//
if ($faqConfig->get('security.ldapSupport') && file_exists(PMF_CONFIG_DIR . '/ldap.php')) {
    require PMF_CONFIG_DIR . '/constants_ldap.php';
    require PMF_CONFIG_DIR . '/ldap.php';
    $faqConfig->setLdapConfig($PMF_LDAP);
} else {
    $ldap = null;
}

//
// Build attachments path
//
$confAttachmentsPath = trim($faqConfig->get('records.attachmentsPath'));
if ('/' == $confAttachmentsPath[0] || preg_match('%^[a-z]:(\\\\|/)%i', $confAttachmentsPath)) {
    // If we're here, some windows or unix style absolute path was detected.
    define('PMF_ATTACHMENTS_DIR', $confAttachmentsPath);
} else {
    // otherwise build the absolute path
    $tmp = dirname(__DIR__) . DIRECTORY_SEPARATOR . $confAttachmentsPath;

    // Check that nobody is traversing
    if (0 === strpos((string)$tmp, dirname(__DIR__))) {
        define('PMF_ATTACHMENTS_DIR', $tmp);
    } else {
        define('PMF_ATTACHMENTS_DIR', false);
    }
}

//
// Fix if phpMyFAQ is running behind a proxy server
//
if (! isset($_SERVER['HTTP_HOST'])) {
    if (isset($_SERVER['HTTP_X_FORWARDED_SERVER'])) {
        $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_FORWARDED_SERVER'];
    } else {
        $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
    };
}

//
// Fix undefined server variables in Windows IIS & CGI mode
//
if (! isset($_SERVER['SCRIPT_NAME'])) {
    if(isset($_SERVER['SCRIPT_FILENAME'])) {
        $_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_FILENAME'];
    } elseif(isset($_SERVER['PATH_TRANSLATED'])) {
        $_SERVER['SCRIPT_NAME'] = $_SERVER['PATH_TRANSLATED'];
    } elseif(isset($_SERVER['PATH_INFO'])) {
        $_SERVER['SCRIPT_NAME'] = $_SERVER['PATH_INFO'];
    } elseif(isset($_SERVER['SCRIPT_URL'])) {
        $_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_URL'];
    }
}

/**
 * phpMyFAQ custom error handler function, also to prevent the disclosure of
 * potential sensitive data.
 *
 * @access public
 * @param  int    $level    The level of the error raised.
 * @param  string $message  The error message.
 * @param  string $filename The filename that the error was raised in.
 * @param  int    $line     The line number the error was raised at.
 * @param  mixed  $context  It optionally contains an array of every variable
 *                          that existed in the scope the error was triggered in.
 *
 * @return bool
 */
function pmf_error_handler($level, $message, $filename, $line, $context)
{
    // Sanity check
    // Note: when DEBUG mode is true we want to track any error!
    if (
        // 1. the @ operator sets the PHP's error_reporting() value to 0
        (!DEBUG && (0 == error_reporting()))
        // 2. Honor the value of PHP's error_reporting() function
        || (!DEBUG && (0 == ($level & error_reporting())))
    ) {
        // Do nothing
        return true;
    }

    // Cleanup potential sensitive data
    $filename = (DEBUG ? $filename : basename($filename));

    $errorTypes = array(
        E_ERROR             => 'error',
        E_WARNING           => 'warning',
        E_PARSE             => 'parse error',
        E_NOTICE            => 'notice',
        E_CORE_ERROR        => 'code error',
        E_CORE_WARNING      => 'core warning',
        E_COMPILE_ERROR     => 'compile error',
        E_COMPILE_WARNING   => 'compile warning',
        E_USER_ERROR        => 'user error',
        E_USER_WARNING      => 'user warning',
        E_USER_NOTICE       => 'user notice',
        E_STRICT            => 'strict warning',
        E_RECOVERABLE_ERROR => 'recoverable error',
        E_DEPRECATED        => 'deprecated warning',
        E_USER_DEPRECATED   => 'user deprecated warning',
    );
    $errorType = 'unknown error';
    if (isset($errorTypes[$level])) {
        $errorType = $errorTypes[$level];
    }

    // Custom error message
    $errorMessage = sprintf(
        '<br><strong>phpMyFAQ %s</strong> [%s]: %s in <strong>%s</strong> on line <strong>%d</strong><br>',
        $errorType,
        $level,
        $message,
        $filename,
        $line
    );

    if (ini_get('display_errors')) {
        print $errorMessage;
    }
    if (ini_get('log_errors')) {
        error_log(sprintf('phpMyFAQ %s:  %s in %s on line %d',
            $errorType,
            $message,
            $filename,
            $line)
        );
    }

    switch ($level) {
        // Blocking errors
        case E_ERROR:
        case E_PARSE:
        case E_CORE_ERROR:
        case E_COMPILE_ERROR:
        case E_USER_ERROR:
            // Prevent processing any more PHP scripts
            exit();
            break;
        // Not blocking errors
        default:
            break;
    }

    return true;
}