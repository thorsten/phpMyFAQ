<?php
/**
 * Bootstrap phpMyFAQ PHPUnit testing environment
 *
 * PHP Version 5.5
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

use Symfony\Component\ClassLoader\UniversalClassLoader;

date_default_timezone_set('Europe/Berlin');
error_reporting(E_ALL | E_STRICT);

//
// The root directory
//
define('PMF_ROOT_DIR', dirname(__DIR__) . '/phpmyfaq');
define('PMF_CONFIG_DIR', dirname(__DIR__) . '/phpmyfaq/config');
define('PMF_TEST_DIR', __DIR__);
define('DEBUG', false);

require PMF_CONFIG_DIR . '/constants.php';
require PMF_CONFIG_DIR . '/constants_ldap.php';

/**
 * The include directory
 */
define('PMF_INCLUDE_DIR', dirname(__DIR__) . '/phpmyfaq/inc');

/**
 * The directory where the translations reside
 */
define('PMF_LANGUAGE_DIR', dirname(__DIR__) . '/phpmyfaq/lang');

//
// Setting up PSR-0 autoloader for Symfony Components
//
require PMF_INCLUDE_DIR . '/libs/Symfony/Component/ClassLoader/UniversalClassLoader.php';

$loader = new UniversalClassLoader();
$loader->registerNamespace('Symfony', PMF_INCLUDE_DIR . '/libs');
$loader->registerPrefix('PMF_', PMF_INCLUDE_DIR);
$loader->registerPrefix('PMFTest_', PMF_TEST_DIR);
$loader->register();
