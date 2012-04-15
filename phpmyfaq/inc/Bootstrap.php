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
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-03-07
 */

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
if (file_exists('../multisite/multisite.php')) {
    require '../multisite/multisite.php';
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

require PMF_CONFIG_DIR . '/database.php';
require PMF_CONFIG_DIR . '/constants.php';

//
// Include Autoloader and global functions
//
define('PMF_INCLUDE_DIR', __DIR__);
require PMF_INCLUDE_DIR . '/Autoloader.php';
require PMF_INCLUDE_DIR . '/functions.php';
// @todo: Linkverifier.php contains both PMF_Linkverifier class and
// helper functions => move the fns into the class.
require_once PMF_INCLUDE_DIR . '/Linkverifier.php';

//
// Set the error handler to our pmf_error_handler() function
//
set_error_handler('pmf_error_handler');

//
// Create a database connection
//
define('SQLPREFIX', $DB['prefix']);
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
// Avoid any PHP version to move sessions on URLs
ini_set('session.use_only_cookies', 1);
ini_set('session.use_trans_sid', 0);
ini_set('url_rewriter.tags', '');

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

/**
 * Build attachments path
 */
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
