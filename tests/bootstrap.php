<?php
/**
 * Bootstrap phpMyFAQ PHPUnit testing environment
 *
 * 
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Configuration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-02-12
 */

use Composer\Autoload\ClassLoader;
use phpMyFAQ\Installer;

date_default_timezone_set('Europe/Berlin');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL | E_STRICT);

//
// The root directory
//
define('PMF_ROOT_DIR', dirname(__DIR__) . '/phpmyfaq');
define('PMF_CONFIG_DIR', dirname(__DIR__) . '/phpmyfaq/config');
define('PMF_TEST_DIR', __DIR__);
define('IS_VALID_PHPMYFAQ', true);
define('COPYRIGHT', 'Hello, World.');
define('DEBUG', true);

$_SERVER['HTTP_HOST'] = 'https://localhost/';

require PMF_CONFIG_DIR.'/constants.php';

//
// The include directory
//
define('PMF_SRC_DIR', dirname(__DIR__) . '/phpmyfaq/src');

//
// The directory where the translations reside
//
define('LANGUAGE_DIR', dirname(__DIR__) . '/phpmyfaq/lang');

//
// Setting up PSR-0 autoloader
//
$loader = new ClassLoader();
$loader->add('phpMyFAQ', PMF_SRC_DIR);
$loader->add('PMFTest_', PMF_TEST_DIR.'/PMFTest');
$loader->register();

//
// Delete possible SQLite file first
//
@unlink(PMF_TEST_DIR.'/test.db');

//
// Create database credentials for SQLite
//
$setup = [
    'dbServer' => PMF_TEST_DIR.'/test.db',
    'dbType' => 'sqlite3',
    'loginname' => 'admin',
    'password' => 'password',
    'password_retyped' => 'password',
    'rootDir' => PMF_TEST_DIR
];

$installer = new Installer();
$installer->startInstall($setup);

require PMF_TEST_DIR.'/config/database.php';
