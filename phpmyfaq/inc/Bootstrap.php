<?php

/**
 * Bootstrap phpMyFAQ.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-03-07
 */

use Symfony\Component\ClassLoader\UniversalClassLoader;
use Symfony\Component\ClassLoader\Psr4ClassLoader;
use Elasticsearch\ClientBuilder;

//
// Debug mode:
// - false      debug mode disabled
// - true       debug mode enabled
//
define('DEBUG', false);
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
$includePaths = explode(PATH_SEPARATOR, ini_get('include_path'));
$i = 0;
while ((!$foundCurrPath) && ($i < count($includePaths))) {
    if ('.' == $includePaths[$i]) {
        $foundCurrPath = true;
    }
    ++$i;
}
if (!$foundCurrPath) {
    ini_set('include_path', '.'.PATH_SEPARATOR.ini_get('include_path'));
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
if (file_exists(__DIR__.'/../multisite/multisite.php') && 'cli' !== PHP_SAPI) {
    require __DIR__.'/../multisite/multisite.php';
}

//
// The root directory
//
if (!defined('PMF_ROOT_DIR')) {
    define('PMF_ROOT_DIR', dirname(__DIR__));
}

//
// Read configuration and constants
//
if (!defined('PMF_MULTI_INSTANCE_CONFIG_DIR')) {
    // Single instance configuration
    define('PMF_CONFIG_DIR', dirname(__DIR__).'/config');
} else {
    // Multi instance configuration
    define('PMF_CONFIG_DIR', PMF_MULTI_INSTANCE_CONFIG_DIR);
}

//
// Check if config/database.php exist -> if not, redirect to installer
//
if (!file_exists(PMF_CONFIG_DIR.'/database.php') && !file_exists(PMF_ROOT_DIR.'/inc/data.php')) {
    header('Location: setup/index.php');
    exit();
}

if (file_exists(PMF_ROOT_DIR.'/inc/data.php')) {
    require PMF_ROOT_DIR.'/inc/data.php';
} else {
    require PMF_CONFIG_DIR.'/database.php';
}
require PMF_CONFIG_DIR.'/constants.php';

/*
 * The include directory
 */
define('PMF_INCLUDE_DIR', __DIR__);

/*
 * The directory where the translations reside
 */
define('PMF_LANGUAGE_DIR', dirname(__DIR__).'/lang');

//
// Setting up PSR-0 autoloader for Symfony Components
//
require PMF_INCLUDE_DIR.'/libs/Symfony/Component/ClassLoader/UniversalClassLoader.php';

$loader = new UniversalClassLoader();
$loader->registerNamespace('Symfony', PMF_INCLUDE_DIR.'/libs');
$loader->registerPrefix('PMF_', PMF_INCLUDE_DIR);
$loader->register();

$psr4Loader = new Psr4ClassLoader();
$psr4Loader->addPrefix('Abraham\\TwitterOAuth\\', PMF_INCLUDE_DIR.'/libs/abraham/twitteroauth/src');

require PMF_INCLUDE_DIR.'/libs/parsedown/Parsedown.php';
require PMF_INCLUDE_DIR.'/libs/parsedown/ParsedownExtra.php';

//
// Set the error handler to our pmf_error_handler() function
//
set_error_handler('pmf_error_handler');

//
// Create a database connection
//
try {
    PMF_Db::setTablePrefix($DB['prefix']);
    $db = PMF_Db::factory($DB['type']);
    $db->connect($DB['server'], $DB['user'], $DB['password'], $DB['db']);
} catch (PMF_Exception $e) {
    PMF_Db::errorPage($e->getMessage());
    exit(-1);
}

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
if (defined('PMF_SESSION_SAVE_PATH') && !empty(PMF_SESSION_SAVE_PATH)) {
    session_save_path(PMF_SESSION_SAVE_PATH);
}
session_start();

//
// Connect to LDAP server, when LDAP support is enabled
//
if ($faqConfig->get('security.ldapSupport') && file_exists(PMF_CONFIG_DIR.'/ldap.php') && extension_loaded('ldap')) {
    require PMF_CONFIG_DIR.'/constants_ldap.php';
    require PMF_CONFIG_DIR.'/ldap.php';
    $faqConfig->setLdapConfig($PMF_LDAP);
} else {
    $ldap = null;
}

//
// Connect to Elasticsearch if enabled
//
if ($faqConfig->get('search.enableElasticsearch') && file_exists(PMF_CONFIG_DIR.'/elasticsearch.php')) {

    require PMF_CONFIG_DIR.'/elasticsearch.php';
    require PMF_CONFIG_DIR.'/constants_elasticsearch.php';
    require PMF_INCLUDE_DIR.'/libs/react/promise/src/functions.php';

    $psr4Loader = new Psr4ClassLoader();
    $psr4Loader->addPrefix('Elasticsearch', PMF_INCLUDE_DIR.'/libs/elasticsearch/src/Elasticsearch');
    $psr4Loader->addPrefix('GuzzleHttp\\Ring\\', PMF_INCLUDE_DIR.'/libs/guzzlehttp/ringphp/src');
    $psr4Loader->addPrefix('Monolog', PMF_INCLUDE_DIR.'/libs/monolog/src/Monolog');
    $psr4Loader->addPrefix('Psr', PMF_INCLUDE_DIR.'/libs/psr/log/Psr');
    $psr4Loader->addPrefix('React\\Promise\\', PMF_INCLUDE_DIR.'/libs/react/promise/src');
    $psr4Loader->register();

    $esClient = ClientBuilder::create()
        ->setHosts($PMF_ES['hosts'])
        ->build();

    $faqConfig->setElasticsearch($esClient);
    $faqConfig->setElasticsearchConfig($PMF_ES);
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
    $tmp = dirname(__DIR__).DIRECTORY_SEPARATOR.$confAttachmentsPath;

    // Check that nobody is traversing
    if (0 === strpos((string) $tmp, dirname(__DIR__))) {
        define('PMF_ATTACHMENTS_DIR', $tmp);
    } else {
        define('PMF_ATTACHMENTS_DIR', false);
    }
}

//
// Fix if phpMyFAQ is running behind a proxy server
//
if (!isset($_SERVER['HTTP_HOST'])) {
    if (isset($_SERVER['HTTP_X_FORWARDED_SERVER'])) {
        $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_FORWARDED_SERVER'];
    } else {
        $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
    };
}

//
// Fix undefined server variables in Windows IIS & CGI mode
//
if (!isset($_SERVER['SCRIPT_NAME'])) {
    if (isset($_SERVER['SCRIPT_FILENAME'])) {
        $_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_FILENAME'];
    } elseif (isset($_SERVER['PATH_TRANSLATED'])) {
        $_SERVER['SCRIPT_NAME'] = $_SERVER['PATH_TRANSLATED'];
    } elseif (isset($_SERVER['PATH_INFO'])) {
        $_SERVER['SCRIPT_NAME'] = $_SERVER['PATH_INFO'];
    } elseif (isset($_SERVER['SCRIPT_URL'])) {
        $_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_URL'];
    }
}

//
// phpMyFAQ exception log
//
$pmfExeptions = [];

/**
 * phpMyFAQ custom error handler function, also to prevent the disclosure of
 * potential sensitive data.
 *
 * @param int    $level    The level of the error raised.
 * @param string $message  The error message.
 * @param string $filename The filename that the error was raised in.
 * @param int    $line     The line number the error was raised at.
 * @param mixed  $context  It optionally contains an array of every variable
 *                         that existed in the scope the error was triggered in.
 *
 * @return boolean|null
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
        E_ERROR => 'error',
        E_WARNING => 'warning',
        E_PARSE => 'parse error',
        E_NOTICE => 'notice',
        E_CORE_ERROR => 'code error',
        E_CORE_WARNING => 'core warning',
        E_COMPILE_ERROR => 'compile error',
        E_COMPILE_WARNING => 'compile warning',
        E_USER_ERROR => 'user error',
        E_USER_WARNING => 'user warning',
        E_USER_NOTICE => 'user notice',
        E_STRICT => 'strict warning',
        E_RECOVERABLE_ERROR => 'recoverable error',
        E_DEPRECATED => 'deprecated warning',
        E_USER_DEPRECATED => 'user deprecated warning',
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
