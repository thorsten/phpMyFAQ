<?php
/**
 * The main phpMyFAQ Setup
 *
 * This script checks the complete environment, writes the database connection
 * parameters into the file config/database.php and the configuration into the database.
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Setup
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Tom Rochester <tom.rochester@gmail.com>
 * @author    Johannes Schlüter <johannes@php.net>
 * @author    Uwe Pries <uwe.pries@digartis.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @author    Florian Anderiasch <florian@phpmyfaq.de>
 * @copyright 2002-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2002-08-20
 */

define('COPYRIGHT', '&copy; 2001-2014 <a href="http://www.phpmyfaq.de/">phpMyFAQ Team</a> | Follow us on <a href="http://twitter.com/phpMyFAQ">Twitter</a> | All rights reserved.');
define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));
define('PMF_INCLUDE_DIR', PMF_ROOT_DIR . '/inc');
define('IS_VALID_PHPMYFAQ', null);

if (version_compare(PHP_VERSION, '5.4.4') < 0) {
    die("Sorry, but you need PHP 5.4.4 or later!"); // Die hard because of "use"
}

use Symfony\Component\ClassLoader\UniversalClassLoader;

if (! defined('DEBUG')) {
    define('DEBUG', true);
}

if ((@ini_get('safe_mode') != 'On' || @ini_get('safe_mode') !== 1)) {
    set_time_limit(0);
}

session_name('phpmyfaq-setup');
session_start();

require PMF_ROOT_DIR . '/config/constants.php';

//
// Setting up PSR-0 autoloader for Symfony Components
//
require PMF_INCLUDE_DIR . '/libs/Symfony/Component/ClassLoader/UniversalClassLoader.php';

$loader = new UniversalClassLoader();
$loader->registerNamespace('Symfony', PMF_INCLUDE_DIR . '/libs');
$loader->registerPrefix('PMF_', PMF_INCLUDE_DIR);
$loader->registerPrefix('Twig_', PMF_INCLUDE_DIR . '/libs');
$loader->register();

//
// Initialize static string wrapper
//
PMF_String::init('en');

$installer = new PMF_Installer();
$system    = new PMF_System();

//
// Initializing Twig
//
$twig = new Twig_Environment(
    new Twig_Loader_Filesystem(PMF_ROOT_DIR . '/setup/assets/twig')
);

$tplLayoutVars = array(
    'version'           => PMF_System::getVersion(),
    'currentYear'       => date('Y'),
    'criticalErrors'    => $installer->checkBasicStuff(),
    'filePermErrors'    => $installer->checkFilesystemPermissions(),
    'nonCriticalErrors' => $installer->checkNoncriticalSettings()
);

$twig->loadTemplate('layout.twig')->display($tplLayoutVars);

// not yet POSTed
if (!isset($_POST['sql_server']) && !isset($_POST['sql_user']) && !isset($_POST['sql_db'])) {

    $tplDatabaseVars = array(
        'databases' => $system->getSupportedSafeDatabases(true),
        'dirname'   => dirname(__DIR__)
    );
    ?>

    <form class="form-horizontal" action="setup.php" method="post">
        <div class="row">
            <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                <?php
                $twig->loadTemplate('database.twig')->display($tplDatabaseVars);
                ?>
            </div>

            <?php if (extension_loaded('ldap')): ?>
            <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                <?php
                $twig->loadTemplate('ldap.twig')->display($tplDatabaseVars);
                ?>
            </div>
        </div>
        <div class="row">
            <?php endif; ?>

            <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                <?php
                $tplConfigVars = array(
                    'languageOptions' => $installer->renderLanguageOptions($languageCodes)
                );
                $twig->loadTemplate('mainconfig.twig')->display($tplConfigVars);
                ?>
            </div>
        </div>

        <div class="row text-center">
            <button class="btn btn-primary btn-lg" type="submit">
                Click to install phpMyFAQ <?php echo PMF_System::getVersion(); ?>
            </button>
        </div>
        <div class="row" style="padding-left: 20px;">
            <p class="alert alert-info" style="margin-top: 20px;">
                Your password will be saved with a <strong><?php echo PMF_ENCRYPTION_TYPE; ?></strong>
                encryption. You can change the encryption type for passwords in <em>config/constants.php</em>.
            </p>
        </div>
    </form>
    <?php
    PMF_System::renderFooter();
} else {
    $installer->startInstall();

    $tplSuccessVars = array(
        'dataList' => $installer->renderDataList()
    );
    $twig->loadTemplate('success.twig')->display($tplSuccessVars);

    $installer->cleanUpFiles();
    PMF_System::renderFooter();
}