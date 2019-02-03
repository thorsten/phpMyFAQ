<?php
/**
 * The main phpMyFAQ Setup.
 *
 * This script checks the complete environment, writes the database connection
 * parameters into the file config/database.php and the configuration into the database.
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
 * @copyright 2002-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-08-20
 */

use Composer\Autoload\ClassLoader;
use phpMyFAQ\Installer;
use phpMyFAQ\Strings;
use phpMyFAQ\System;

define('COPYRIGHT', '&copy; 2001-2019 <a href="https://www.phpmyfaq.de/">phpMyFAQ Team</a> | Follow us on <a href="http://twitter.com/phpMyFAQ">Twitter</a> | All rights reserved.');
define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));
define('PMF_SRC_DIR', PMF_ROOT_DIR.'/src');
define('IS_VALID_PHPMYFAQ', null);

if (version_compare(PHP_VERSION, '5.6.6') < 0) {
    die('Sorry, but you need PHP 5.6.6 or later!');
}

set_time_limit(0);

if (!defined('DEBUG')) {
    define('DEBUG', true);
}

session_name('phpmyfaq-setup');
session_start();

require PMF_ROOT_DIR.'/src/libs/autoload.php';
require PMF_ROOT_DIR.'/config/constants.php';
require PMF_ROOT_DIR.'/config/constants_elasticsearch.php';

$loader = new ClassLoader();
$loader->add('phpMyFAQ', PMF_SRC_DIR);
$loader->register()
?>
<!doctype html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <title>phpMyFAQ <?= System::getVersion() ?> Setup</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="application-name" content="phpMyFAQ <?= System::getVersion() ?>">
  <meta name="copyright" content="(c) 2001-<?= date('Y') ?> phpMyFAQ Team">
  <link rel="stylesheet" href="../admin/assets/css/style.min.css?v=1">
  <script src="../assets/themes/default/js/vendors.min.js"></script>
  <script src="../assets/themes/default/js/phpmyfaq.min.js"></script>
  <link rel="shortcut icon" href="../assets/themes/default/img/favicon.ico">
</head>
<body>

  <header>
    <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark justify-content-between">
      <div class="container">
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarsExampleDefault">
          <ul class="navbar-nav mr-auto">
            <li class="nav-item">
              <a class="nav-link" target="_blank" href="https://www.phpmyfaq.de/documentation">Documentation</a>
            </li>
            <li class="nav-item">
            <a class="nav-link" target="_blank" href="https://www.phpmyfaq.de/support">Support</a>
            </li>
            <li class="nav-item">
            <a class="nav-link" target="_blank" href="http://forum.phpmyfaq.de/">Forums</a>
            </li>
            <li class="nav-item">
            <a class="nav-link" target="_blank" href="http://faq.phpmyfaq.de/">FAQ</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
  </header>

<main role="main">
  <section id="content">

    <div class="jumbotron">
      <div class="container">
        <h1 class="display-4 text-center">
          phpMyFAQ <?= System::getVersion() ?>
        </h1>
        <p class="text-center">
          Did you already read the
          <a target="_blank" href="https://www.phpmyfaq.de/documentation">documentation</a>
          carefully before starting the phpMyFAQ setup?</p>
      </div>
    </div>

    <div class="container">
      <div class="row">
<?php
//
// Initialize static string wrapper
//
Strings::init('en');

$installer = new Installer();
$system = new System();

$installer->checkBasicStuff();
$installer->checkFilesystemPermissions();

// not yet POSTed
if (!isset($_POST['sql_server']) && !isset($_POST['sql_user']) && !isset($_POST['sql_db'])) {
    $installer->checkNoncriticalSettings()
?>
        </div>

        <form  action="index.php" method="post">
        <div class="row">
            <div class="col-sm">
                <fieldset>
                <legend>Database setup</legend>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label" for="sql_type">Server:</label>
                        <div class="col-sm-9">
                            <select name="sql_type" id="sql_type" class="form-control">
                                <?= implode('', $system->getSupportedSafeDatabases(true)) ?>
                            </select>
                            <p class="form-text text-muted">Please select your preferred database type.</p>
                        </div>
                    </div>

                    <div id="dbdatafull" style="display: block;">
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="sql_server">Host/Socket:</label>
                            <div class="col-sm-9">
                                <input type="text" name="sql_server" id="sql_server" class="form-control"
                                    placeholder="e.g. 127.0.0.1">
                                <p class="form-text text-muted">Please enter the host or path to the socket of your database server.</p>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label"for="sql_user">User:</label>
                            <div class="col-sm-9">
                                <input type="text" name="sql_user" id="sql_user" class="form-control">
                                <p class="form-text text-muted">Please enter your database user.</p>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label"for="sql_password">Password:</label>
                            <div class="col-sm-9">
                                <input name="sql_password" type="password" id="sql_password" class="form-control">
                                <p class="form-text text-muted">Please enter your database password.</p>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label"for="sql_db">Database:</label>
                            <div class="col-sm-9">
                                <input type="text" name="sql_db" id="sql_db" class="form-control">
                                <p class="form-text text-muted">Please enter your existing database name.</p>
                            </div>
                        </div>
                    </div>

                    <div id="dbsqlite" style="display: none;">
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label"for="sql_sqlitefile">SQLite database file:</label>
                            <div class="col-sm-9">
                                <input type="text" name="sql_sqlitefile" id="sql_sqlitefile" class="form-control"
                                       value="<?= dirname(__DIR__) ?>">
                                <p class="form-text text-muted">
                                    Please enter the full path to your SQLite datafile which should be outside your
                                    docroot.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label" for="sqltblpre">Table prefix:</label>
                        <div class="col-sm-9">
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
            <div class="col-sm">
                <fieldset>
                <legend>LDAP setup</legend>
                    <div class="form-group">
                        <div class="form-check">
                          <input id="ldap_enabled" class="form-check-input" type="checkbox" name="ldap_enabled" value="yes">
                          <label class="form-check-label" for="ldap_enabled">
                            Enable LDAP support?
                          </label>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"for="ldap_server">Host:</label>
                        <div class="col-sm-9">
                            <input type="text" name="ldap_server" id="ldap_server" class="form-control"
                                   placeholder="127.0.0.1">
                            <p class="form-text text-muted">Please enter the host of your LDAP server.</p>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"for="ldap_port">Port:</label>
                        <div class="col-sm-9">
                            <input type="number" name="ldap_port" value="389" id="ldap_port" class="form-control">
                            <p class="form-text text-muted">Please enter the port of your LDAP server.</p>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"for="ldap_user">User DN:</label>
                        <div class="col-sm-9">
                            <input type="text" name="ldap_user" id="ldap_user" class="form-control">
                            <p class="form-text text-muted">Please enter your specified RDN username.</p>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"for="ldap_password">Password:</label>
                        <div class="col-sm-9">
                            <input name="ldap_password" type="password" id="ldap_password" class="form-control">
                            <p class="form-text text-muted">Please enter your LDAP password.</p>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"for="ldap_base">Base DN:</label>
                        <div class="col-sm-9">
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
        </div>

        <div class="row">
            <?php endif; ?>

            <div class="col-sm">
                <fieldset>
                <legend>phpMyFAQ setup</legend>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"for="language">Default language:</label>
                        <div class="col-sm-9">
                            <select name="language" id="language" class="form-control">
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

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label" for="permLevel">Permission level:</label>
                        <div class="col-sm-9">
                                <select id="permLevel" name="permLevel" class="form-control">
                                    <option value="basic">Basic (no group support)</option>
                                    <option value="medium">Medium (with group support)</option>
                                    <option value="large">Large (with sections support)</option>
                            </select>
                            <p class="form-text text-muted">
                                Complexity of rights and permissions.
                            </p>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"for="realname">Your name:</label>
                        <div class="col-sm-9">
                            <input type="text" name="realname" id="realname" class="form-control">
                            <p class="form-text text-muted">Please enter your real name.</p>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"for="email">Your email address:</label>
                        <div class="col-sm-9">
                            <input type="email" name="email" id="email" class="form-control">
                            <p class="form-text text-muted">Please enter your email adress.</p>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"for="loginname">Your login name:</label>
                        <div class="col-sm-9">
                            <input type="text" name="loginname" id="loginname" class="form-control">
                            <p class="form-text text-muted">Please enter your login name.</p>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"for="password">Your password:</label>
                        <div class="col-sm-9">
                            <input type="password" name="password" id="password" class="form-control">
                            <p class="form-text text-muted">Please enter your password.</p>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"for="password_retype">Retype password:</label>
                        <div class="col-sm-9">
                            <input type="password" name="password_retyped" id="password_retype" class="form-control">
                            <p class="form-text text-muted">Please retype your password.</p>
                        </div>
                    </div>
                </fieldset>
            </div>

            <?php if (extension_loaded('curl')): ?>
            <div class="col-sm">
                <fieldset>
                    <legend>Elasticsearch setup</legend>
                    <div class="form-group">
                        <div class="form-check">
                          <input id="elasticsearch_enabled" class="form-check-input" type="checkbox" name="elasticsearch_enabled" value="yes">
                          <label class="form-check-label" for="elasticsearch_enabled">
                            Enable Elasticsearch support?
                          </label>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"for="elasticsearch_server">Host:</label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <input type="text" name="elasticsearch_server[]" id="elasticsearch_server"
                                       class="form-control" placeholder="127.0.0.1:9200">
                                <span class="input-group-addon">
                                    <a title="Add more Elasticsearch hosts" style="cursor: pointer;" data-action="add">
                                        <i aria-hidden="true" class="fas fa-plus-circle"></i>
                                    </a>
                                </span>
                            </div>
                            <p class="form-text text-muted">
                                Please enter the host (domain or IP) with port number of your Elasticsearch server.
                            </p>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"for="elasticsearch_index">Index name:</label>
                        <div class="col-sm-9">
                            <input type="text" name="elasticsearch_index" id="elasticsearch_index" class="form-control">
                            <p class="form-text text-muted">Please enter your Elasticsearch index name.</p>
                        </div>
                    </div>
                </fieldset>
            </div>
            <?php endif; ?>
        </div>

        <div class="row">
          <div class="col-sm">
              <div class="form-group row">
                  <button class="btn btn-success btn-lg btn-block" type="submit">
                         Click to install phpMyFAQ <?= System::getVersion() ?>
                  </button>
              </div>
            </div>
        </div>

        <div class="row">
          <div class="col-sm">
            <p class="alert alert-info text-center">
                <i aria-hidden="true" class="fas fa-info-circle fa-fw"></i>
                Your password will be saved with a <strong>salted <?= PMF_ENCRYPTION_TYPE ?></strong>. You can
                change the encryption type for passwords in <em>config/constants.php</em>.
            </p>
            </div>
        </div>
        </form>
<?php
    System::renderFooter();
} else {
    $installer->startInstall();
    ?>
        <p class="alert alert-success">
            Wow, looks like the installation worked like a charm. This is pretty cool, isn't it? :-)
        </p>

        <p>
            You can visit <a href="../index.php">your version of phpMyFAQ</a> or login into your
            <a href="../admin/index.php">admin section</a>.
         </p>
<?php
    $installer->cleanUpFiles();
    System::renderFooter();
}
