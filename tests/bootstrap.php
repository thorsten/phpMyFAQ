<?php

/**
 * Bootstrap phpMyFAQ PHPUnit testing environment
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015 phpMyFAQ Team
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2015-02-12
 */

use Composer\Autoload\ClassLoader;
use phpMyFAQ\Setup\Installer;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use Symfony\Component\HttpFoundation\Request;

date_default_timezone_set('Europe/Berlin');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED);

//
// The root directory
//
define('PMF_ROOT_DIR', dirname(__DIR__) . '/phpmyfaq');
define('PMF_CONFIG_DIR', dirname(__DIR__) . '/tests/content/core/config');
define('PMF_CONTENT_DIR', dirname(__DIR__) . '/tests/content');

const PMF_LOG_DIR = __DIR__ . '/logs/phpmyfaq.log';
const PMF_TEST_DIR = __DIR__;
const IS_VALID_PHPMYFAQ = true;

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';

// Ensure test log destination exists as a writable file for Monolog
$logDirectory = dirname(PMF_LOG_DIR);
if (!is_dir($logDirectory)) {
    mkdir($logDirectory, 0777, true);
}

if (!file_exists(PMF_LOG_DIR)) {
    touch(PMF_LOG_DIR);
}

require PMF_ROOT_DIR . '/content/core/config/constants.php';

//
// The /src directory
//
define('PMF_SRC_DIR', dirname(__DIR__) . '/phpmyfaq/src');

//
// The directory where the translations reside
//
define('PMF_TRANSLATION_DIR', dirname(__DIR__) . '/phpmyfaq/translations');
require PMF_TRANSLATION_DIR . '/language_en.php';

//
// Setting up autoloader
//
$loader = new ClassLoader();
$loader->add('phpMyFAQ', PMF_SRC_DIR);
$loader->register();

//
// Delete a possible SQLite file first
//
@unlink(PMF_TEST_DIR . '/test.db');

//
// Create database credentials for SQLite
//
$setup = [
    'dbServer' => PMF_TEST_DIR . '/test.db',
    'dbType' => 'pdo_sqlite',
    'dbPort' => null,
    'dbDatabaseName' => '',
    'loginname' => 'admin',
    'password' => 'password',
    'password_retyped' => 'password',
    'rootDir' => PMF_TEST_DIR,
    'mainUrl' => 'https://localhost/',
];

Strings::init();

Request::setTrustedHosts(['^.*$']); // Trust all hosts for testing

try {
    $installer = new Installer(new System());
    $installer->startInstall($setup);
} catch (Exception $exception) {
    echo $exception->getMessage();
}

require PMF_TEST_DIR . '/content/core/config/database.php';
