<?php

/**
 * Bootstraps a phpMyFAQ instance
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-03-07
 */

use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use OpenSearch\SymfonyClientFactory;
use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\DatabaseConfiguration;
use phpMyFAQ\Configuration\ElasticsearchConfiguration;
use phpMyFAQ\Configuration\LdapConfiguration;
use phpMyFAQ\Configuration\OpenSearchConfiguration;
use phpMyFAQ\Database;
use phpMyFAQ\Core\Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage;

//
// Debug mode:
// - false debug mode disabled
// - true  debug mode enabled
const DEBUG = true;
if (DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(-1);
} else {
    error_reporting(0);
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
    ini_set('include_path', '.' . PATH_SEPARATOR . ini_get('include_path'));
}

//
// Tweak some PHP configuration values
// Warning: be sure the server has enough memory and stack for PHP
//
ini_set('pcre.backtrack_limit', '100000000');
ini_set('pcre.recursion_limit', '100000000');

//
// Include constants
//
require 'constants.php';

//
// Setting up autoloader
//
require 'autoload.php';

//
// Check if multisite/multisite.php exist for Multisite support
//
if (file_exists(PMF_ROOT_DIR . '/multisite/multisite.php') && 'cli' !== PHP_SAPI) {
    require PMF_ROOT_DIR . '/multisite/multisite.php';
}

//
// Read configuration and constants
//
if (!defined('PMF_MULTI_INSTANCE_CONFIG_DIR')) {
    define('PMF_CONFIG_DIR', PMF_ROOT_DIR . '/content/core/config'); // 4.0 single instance configuration
    define('PMF_LEGACY_CONFIG_DIR', PMF_ROOT_DIR . '/config'); // 3.x single instance configuration
} else {
    define('PMF_CONFIG_DIR', PMF_MULTI_INSTANCE_CONFIG_DIR); // Multi instance configuration
}

//
// Check if config/database.php exist -> if not, redirect to installer
//
if (!file_exists(PMF_CONFIG_DIR . '/database.php') && !file_exists(PMF_LEGACY_CONFIG_DIR . '/database.php')) {
    $response = new RedirectResponse('./setup/');
    $response->send();
} else {
    if (file_exists(PMF_CONFIG_DIR . '/database.php')) {
        $databaseFile = PMF_CONFIG_DIR . '/database.php';
    } else {
        $databaseFile = PMF_LEGACY_CONFIG_DIR . '/database.php';
    }
}

//
// Get required phpMyFAQ constants
//
if (file_exists(PMF_CONFIG_DIR . '/constants.php')) {
    require PMF_CONFIG_DIR . '/constants.php';
} else {
    require PMF_LEGACY_CONFIG_DIR . '/constants.php';
}

//
// The directory where the translations reside
//
define('PMF_TRANSLATION_DIR', dirname(__DIR__) . '/translations');

//
// Set the error handler and the exception handler
//
set_error_handler('\phpMyFAQ\Core\Error::errorHandler');
set_exception_handler('\phpMyFAQ\Core\Error::exceptionHandler');

//
// Request
//
$request = Request::createFromGlobals();

//
// Start output buffering
//
ob_start();

//
// Create a database connection
//
try {
    $dbConfig = new DatabaseConfiguration($databaseFile);
    Database::setTablePrefix($dbConfig->getPrefix());
    $db = Database::factory($dbConfig->getType());
    $db->connect(
        $dbConfig->getServer(),
        $dbConfig->getUser(),
        $dbConfig->getPassword(),
        $dbConfig->getDatabase(),
        $dbConfig->getPort()
    );
} catch (Exception $exception) {
    Database::errorPage($exception->getMessage());
    exit(-1);
}

//
// Fetch the configuration and add the database connection
//
$faqConfig = new Configuration($db);
$faqConfig->getAll();

//
// We always need a valid, secure session!
//
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_only_cookies', '1'); // Avoid any PHP version to move sessions on URLs
    ini_set('session.auto_start', '0'); // Prevent error to use session_start() if it's active in php.ini
    ini_set('session.use_trans_sid', '0');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.cookie_httponly', 'true');
    ini_set('session.cookie_secure', $request->isSecure());
    ini_set('url_rewriter.tags', '');

    //
    // Start the PHP session
    //
    if (defined('PMF_SESSION_SAVE_PATH') && !empty(PMF_SESSION_SAVE_PATH)) {
        session_save_path(PMF_SESSION_SAVE_PATH);
    }

    session_start();
    $session = new Session(new PhpBridgeSessionStorage());
    $session->start();
}

//
// Connect to LDAP server, when LDAP support is enabled
//
if ($faqConfig->isLdapActive() && file_exists(PMF_CONFIG_DIR . '/ldap.php') && extension_loaded('ldap')) {
    $ldapConfig = new LdapConfiguration(PMF_CONFIG_DIR . '/ldap.php');
    $faqConfig->setLdapConfig($ldapConfig);
} else {
    $ldap = null;
}

//
// Connect to Elasticsearch if enabled
//
if ($faqConfig->get('search.enableElasticsearch') && file_exists(PMF_CONFIG_DIR . '/elasticsearch.php')) {
    require PMF_CONFIG_DIR . '/constants_elasticsearch.php';
    $esConfig = new ElasticsearchConfiguration(PMF_CONFIG_DIR . '/elasticsearch.php');
    try {
        $esClient = ClientBuilder::create()->setHosts($esConfig->getHosts())->build();
        $faqConfig->setElasticsearch($esClient);
        $faqConfig->setElasticsearchConfig($esConfig);
    } catch (AuthenticationException $e) {
        // @handle AuthenticationException
    }
}

//
// Connect to OpenSearch if enabled
//
if ($faqConfig->get('search.enableOpenSearch') && file_exists(PMF_CONFIG_DIR . '/opensearch.php')) {
    require PMF_CONFIG_DIR . '/constants_opensearch.php';
    $openSearchConfig = new OpenSearchConfiguration(PMF_CONFIG_DIR . '/opensearch.php');
    $client = (new SymfonyClientFactory())->create([
        'base_uri' => $openSearchConfig->getHosts()[0],
        'verify_peer' => false,
    ]);
    $faqConfig->setOpenSearch($client);
    $faqConfig->setOpenSearchConfig($openSearchConfig);
}

//
// Build an attachment path
//
$confAttachmentsPath = trim($faqConfig->get('records.attachmentsPath'));
if ('/' == $confAttachmentsPath[0] || preg_match('%^[a-z]:(\\\\|/)%i', $confAttachmentsPath)) {
    // If we're here, some Windows or unix style absolute path was detected.
    define('PMF_ATTACHMENTS_DIR', $confAttachmentsPath);
} else {
    // otherwise, build the absolute path
    $tmp = dirname(__DIR__) . DIRECTORY_SEPARATOR . $confAttachmentsPath;

    // Check that nobody is traversing
    if (str_starts_with($tmp, dirname(__DIR__))) {
        define('PMF_ATTACHMENTS_DIR', $tmp);
    } else {
        define('PMF_ATTACHMENTS_DIR', false);
    }
}

//
// Fix if phpMyFAQ is running behind a proxy server
//
if (!$request->server->has('HTTP_HOST')) {
    if ($request->server->has('HTTP_X_FORWARDED_SERVER')) {
        $request->server->set('HTTP_HOST', $request->server->get('HTTP_X_FORWARDED_SERVER'));
    } else {
        $request->server->set('HTTP_HOST', $request->server->get('HTTP_X_FORWARDED_HOST'));
    }
}
