<?php
/**
 * The main phpMyFAQ Setup.
 *
 * This script checks the complete environment, writes the database connection
 * parameters into the file config/database.php and the configuration into the database.
 *
<<<<<<< HEAD
 * PHP Version 5.3
=======
 * PHP Version 5.6
>>>>>>> 2.10
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Tom Rochester <tom.rochester@gmail.com>
 * @author    Johannes Schlüter <johannes@php.net>
 * @author    Uwe Pries <uwe.pries@digartis.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @author    Florian Anderiasch <florian@phpmyfaq.de>
 * @copyright 2002-2017 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2002-08-20
 */

define('COPYRIGHT', '&copy; 2001-2017 <a href="http://www.phpmyfaq.de/">phpMyFAQ Team</a> | Follow us on <a href="http://twitter.com/phpMyFAQ">Twitter</a> | All rights reserved.');
define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));
define('PMF_INCLUDE_DIR', PMF_ROOT_DIR.'/src');
define('IS_VALID_PHPMYFAQ', null);

if (version_compare(PHP_VERSION, '5.6.0') < 0) {
    die('Sorry, but you need PHP 5.6.0 or later!');
}

set_time_limit(0);

use Symfony\Component\ClassLoader\UniversalClassLoader;
use Symfony\Component\ClassLoader\Psr4ClassLoader;

if (!defined('DEBUG')) {
    define('DEBUG', false);
}

session_name('phpmyfaq-setup');
session_start();

require PMF_ROOT_DIR.'/config/constants.php';
require PMF_ROOT_DIR.'/config/constants_elasticsearch.php';

//
// Setting up Symfony PSR-0 and PSR-4 autoloader
//
require PMF_INCLUDE_DIR.'/libs/symfony/class-loader/UniversalClassLoader.php';
require PMF_INCLUDE_DIR.'/libs/symfony/class-loader/Psr4ClassLoader.php';

$loader = new UniversalClassLoader();
$loader->registerPrefix('PMF_', PMF_INCLUDE_DIR);
$loader->registerPrefix('Twig_', PMF_INCLUDE_DIR . '/libs');
$loader->register();

<<<<<<< HEAD
=======
$psr4Loader = new Psr4ClassLoader();
$psr4Loader->addPrefix('Symfony', PMF_INCLUDE_DIR.'/libs/symfony/');
$psr4Loader->register();

?>
<!doctype html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

    <title>phpMyFAQ <?php echo PMF_System::getVersion() ?> Setup</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="application-name" content="phpMyFAQ <?php echo PMF_System::getVersion() ?>">
    <meta name="copyright" content="(c) 2001-<?php echo date('Y') ?> phpMyFAQ Team">

    <link rel="stylesheet" href="../admin/assets/css/style.min.css?v=1">

    <script src="../assets/js/modernizr.min.js"></script>
    <script src="../assets/js/vendors.js"></script>
    <script src="../assets/js/phpmyfaq.min.js"></script>

    <link rel="shortcut icon" href="../assets/themes/default/img/favicon.ico">

</head>
<body>

<nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="navbarsExampleDefault">
    <ul class="navbar-nav mr-auto">
      <li class="nav-item">
        <a class="nav-link" target="_blank" href="http://www.phpmyfaq.de/documentation">Documentation</a>
      </li>
      <li class="nav-item">
      <a class="nav-link" target="_blank" href="http://www.phpmyfaq.de/support">Support</a>
      </li>
      <li class="nav-item">
      <a class="nav-link" target="_blank" href="http://forum.phpmyfaq.de/">Forums</a>
      </li>
      <li class="nav-item">
      <a class="nav-link" target="_blank" href="http://faq.phpmyfaq.de/">FAQ</a>
      </li>
    </ul>
  </div>
</nav>

<section id="content">
    <div class="container">
        <div class="row">
            <div class="jumbotron">
              <div class="container">
                <h1 class="display-3">phpMyFAQ <?php echo PMF_System::getVersion() ?> Setup</h1>
                <p class="text-center">
                  Did you already read the
                  <a target="_blank" href="http://www.phpmyfaq.de/documentation">documentation</a>
                  carefully before starting the phpMyFAQ setup?</p>
              </div>
            </div>
        </div>

        <div class="row">
<?php
>>>>>>> 2.10
//
// Initialize static string wrapper
//
PMF_String::init('en');

$installer = new PMF_Installer();
$system = new PMF_System();

//
// Initializing Twig
//
$twig = new Twig_Environment(
    new Twig_Loader_Filesystem(PMF_ROOT_DIR . '/setup/assets/twig')
);

<<<<<<< HEAD
$tplLayoutVars = array(
    'version'           => PMF_System::getVersion(),
    'currentYear'       => date('Y'),
    'criticalErrors'    => $installer->checkBasicStuff(),
    'filePermErrors'    => $installer->checkFilesystemPermissions(),
    'nonCriticalErrors' => $installer->checkNoncriticalSettings()
);
=======
// not yet POSTed
if (!isset($_POST['sql_server']) && !isset($_POST['sql_user']) && !isset($_POST['sql_db'])) {
    $installer->checkNoncriticalSettings()
?>
        </div>
>>>>>>> 2.10

$twig->loadTemplate('layout.twig')->display($tplLayoutVars);

// not yet POSTed
if (!isset($_POST['sql_server']) && !isset($_POST['sql_user']) && !isset($_POST['sql_db'])) {

    $databases= $system->getSupportedSafeDatabases(true);
    // Whether the only supported RDBMS is sqlite and/or sqlite3 (in any order):
    $onlySqlite= count($databases)<=2 && strpos(strtolower($databases[0]), 'sqlite')!==FALSE
            && ( count($databases)===1 || strpos(strtolower($databases[1]), 'sqlite')!==FALSE );
    $tplDatabaseVars = array(
        'databases' => $databases,
        'dirname'   => dirname(__DIR__),
        'onlySqlite' => $onlySqlite
    );
    ?>

    <form class="form-horizontal" action="index.php" method="post">
        <div class="row">
<<<<<<< HEAD
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
=======
            <div class="col-6">
                <fieldset>
                <legend>Database setup</legend>
                    <div class="form-group">
                        <label for="sql_type">Server:</label>
                        <div>
                            <select name="sql_type" id="sql_type" size="1" class="form-control">
                                <?php echo implode('', $system->getSupportedSafeDatabases(true)) ?>
                            </select>
                            <p class="form-text text-muted">Please select your preferred database type.</p>
                        </div>
                    </div>

                    <div id="dbdatafull" style="display: block;">
                        <div class="form-group">
                            <label for="sql_server">Host/Socket:</label>
                            <div>
                                <input type="text" name="sql_server" id="sql_server" class="form-control"
                                    placeholder="e.g. 127.0.0.1">
                                <p class="form-text text-muted">Please enter the host or path to the socket of your database server.</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="sql_user">User:</label>
                            <div>
                                <input type="text" name="sql_user" id="sql_user" class="form-control">
                                <p class="form-text text-muted">Please enter your database user.</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="sql_password">Password:</label>
                            <div>
                                <input name="sql_password" type="password" id="sql_password" class="form-control">
                                <p class="form-text text-muted">Please enter your database password.</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="sql_db">Database:</label>
                            <div>
                                <input type="text" name="sql_db" id="sql_db" class="form-control">
                                <p class="form-text text-muted">Please enter your existing database name.</p>
                            </div>
                        </div>
                    </div>

                    <div id="dbsqlite" style="display: none;">
                        <div class="form-group">
                            <label for="sql_sqlitefile">SQLite database file:</label>
                            <div>
                                <input type="text" name="sql_sqlitefile" id="sql_sqlitefile" class="form-control"
                                       value="<?php echo dirname(__DIR__) ?>">
                                <p class="form-text text-muted">
                                    Please enter the full path to your SQLite datafile which should be outside your
                                    docroot.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="sqltblpre">Table prefix:</label>
                        <div>
                            <input type="text" name="sqltblpre" id="sqltblpre" class="form-control">
                            <p class="form-text text-muted">
                                Please enter a table prefix here if you want to install more phpMyFAQ installations on
                                one database.
                            </p>
                        </div>
                    </div>
                </fieldset>
            </div>

            <?php if (extension_loaded('ldap')): ?>
            <div class="col-6">
                <fieldset>
                <legend>LDAP setup</legend>
                    <div class="form-group">
                        <label for="ldap_enabled">&nbsp;</label>
                        <div>
                            <label class="checkbox-inline">
                                <input id="ldap_enabled" type="checkbox" name="ldap_enabled" value="yes">
                                Enable LDAP support?
                            </label>
                            <p class="form-text text-muted">You can enable LDAP later if you like.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="ldap_server">Host:</label>
                        <div>
                            <input type="text" name="ldap_server" id="ldap_server" class="form-control"
                                   placeholder="127.0.0.1">
                            <p class="form-text text-muted">Please enter the host of your LDAP server.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="ldap_port">Port:</label>
                        <div>
                            <input type="number" name="ldap_port" value="389" id="ldap_port" class="form-control">
                            <p class="form-text text-muted">Please enter the port of your LDAP server.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="ldap_user">User DN:</label>
                        <div>
                            <input type="text" name="ldap_user" id="ldap_user" class="form-control">
                            <p class="form-text text-muted">Please enter your specified RDN username.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="ldap_password">Password:</label>
                        <div>
                            <input name="ldap_password" type="password" id="ldap_password" class="form-control">
                            <p class="form-text text-muted">Please enter your LDAP password.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="ldap_base">Base DN:</label>
                        <div>
                        <input type="text" name="ldap_base" id="ldap_base" class="form-control">
                            <p class="form-text text-muted">
                                Please enter your distinguished name, e.g. 'cn=John Doe,ou=Accounts,o=My Company,c=US'.
                            </p>
                        </div>
                    </div>
                    <p class="alert alert-info">
                        You can add additional LDAP configuration in the admin configuration panel.
                    </p>

                </fieldset>
            </div>
            <?php endif; ?>

            <div class="col-6">
                <fieldset>
                <legend>phpMyFAQ setup</legend>
                    <div class="form-group">
                        <label for="language">Default language:</label>
                        <div>
                            <select name="language" size="1" id="language" class="form-control">
                            <?php
                                if ($dir = @opendir(PMF_ROOT_DIR.'/lang')) {
                                    $options = array();
                                    while ($dat = @readdir($dir)) {
                                        $option = '';
                                        if (substr($dat, -4) === '.php') {
                                            $option .= sprintf('<option value="%s"', $dat);
                                            if ($dat == 'language_en.php') {
                                                $option .= ' selected="selected"';
                                            }
                                            $language = $languageCodes[substr(strtoupper($dat), 9, -4)];
                                            $option  .= sprintf('>%s</option>', $language);
                                            $options[$language] = $option;
                                        }
                                    }
                                    ksort($options);
                                    echo implode("\n", $options);
                                } else {
                                    echo '<option>english</option>';
                                }
                            ?>
                            </select>
                            <p class="form-text text-muted">Please select your default language.</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="permLevel">Permission level:</label>
                        <div>
                                <select id="permLevel" name="permLevel" size="1" class="form-control">
                                    <option value="basic">Basic (no group support)</option>
                                    <option value="medium">Medium (with group support)</option>
                            </select>
                            <p class="form-text text-muted">
                                Complexity of rights and permissions.
                            </p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="realname">Your name:</label>
                        <div>
                            <input type="text" name="realname" id="realname" class="form-control">
                            <p class="form-text text-muted">Please enter your real name.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Your email address:</label>
                        <div>
                            <input type="email" name="email" id="email" class="form-control">
                            <p class="form-text text-muted">Please enter your email adress.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="loginname">Your login name:</label>
                        <div>
                            <input type="text" name="loginname" id="loginname" class="form-control">
                            <p class="form-text text-muted">Please enter your login name.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password">Your password:</label>
                        <div>
                            <input type="password" name="password" id="password" class="form-control">
                            <p class="form-text text-muted">Please enter your password.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password_retype">Retype password:</label>
                        <div>
                            <input type="password" name="password_retyped" id="password_retype" class="form-control">
                            <p class="form-text text-muted">Please retype your password.</p>
                        </div>
                    </div>
                </fieldset>
>>>>>>> 2.10
            </div>

            <?php if (extension_loaded('curl')): ?>
            <div class="col-6">
                <fieldset>
                    <legend>Elasticsearch setup</legend>
                    <div class="form-group">
                        <label for="elasticsearch_enabled">&nbsp;</label>
                        <div>
                            <label class="checkbox-inline">
                                <input id="elasticsearch_enabled" type="checkbox" name="elasticsearch_enabled"
                                value="yes">
                                Enable Elasticsearch support?
                            </label>
                            <p class="form-text text-muted">You can enable Elasticsearch later if you like.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="elasticsearch_server">Host:</label>
                        <div>
                            <div class="input-group">
                                <input type="text" name="elasticsearch_server[]" id="elasticsearch_server"
                                       class="form-control" placeholder="127.0.0.1:9200">
                                <span class="input-group-addon">
                                    <a title="Add more Elasticsearch hosts" style="cursor: pointer;" data-action="add">
                                        <i aria-hidden="true" class="fa fa-plus-circle"></i>
                                    </a>
                                </span>
                            </div>
                            <p class="form-text text-muted">
                                Please enter the host (domain or IP) with port number of your Elasticsearch server.
                            </p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="elasticsearch_index">Index name:</label>
                        <div>
                            <input type="text" name="elasticsearch_index" id="elasticsearch_index" class="form-control">
                            <p class="form-text text-muted">Please enter your Elasticsearch index name.</p>
                        </div>
                    </div>
                </fieldset>
            </div>
            <?php endif; ?>

        </div>

<<<<<<< HEAD
        <div class="row text-center">
            <button class="btn btn-primary btn-lg" type="submit">
                Click to install phpMyFAQ <?php echo PMF_System::getVersion(); ?>
            </button>
        </div>
        <div class="row" style="padding-left: 20px;">
            <p class="alert alert-info" style="margin-top: 20px;">
                Your password will be saved with a <strong><?php echo PMF_ENCRYPTION_TYPE; ?></strong>
                encryption. You can change the encryption type for passwords in <em>config/constants.php</em>.
=======
        <div class="row" style="padding-left: 20px; text-align: center;">
            <div class="form-group">
                <button class="btn btn-success btn-lg" type="submit">
                       Click to install phpMyFAQ <?php echo PMF_System::getVersion() ?>
                </button>
            </div>
        </div>

        <div class="row">
            <p class="alert alert-info text-center">
                <i aria-hidden="true" class="fa fa-info-circle fa-fw"></i>
                Your password will be saved with a <strong>salted <?php echo PMF_ENCRYPTION_TYPE ?></strong>. You can
                change the encryption type for passwords in <em>config/constants.php</em>.
>>>>>>> 2.10
            </p>
        </div>
    </form>
    <?php
    PMF_System::renderFooter();
} else {
    $installer->startInstall();
<<<<<<< HEAD
=======
    ?>
        <p class="alert alert-success">
            Wow, looks like the installation worked like a charm. This is pretty cool, isn't it? :-)
        </p>
>>>>>>> 2.10

    $twig->loadTemplate('success.twig')->display([]);

    $installer->cleanUpFiles();
    PMF_System::renderFooter();
}
