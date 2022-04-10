<?php

/**
 * The main phpMyFAQ Setup.
 *
 * This script checks the complete environment, writes the database connection
 * parameters into the file config/database.php and the configuration into the database.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Tom Rochester <tom.rochester@gmail.com>
 * @author    Johannes Schlüter <johannes@php.net>
 * @author    Uwe Pries <uwe.pries@digartis.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @author    Florian Anderiasch <florian@phpmyfaq.de>
 * @copyright 2002-2022 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-08-20
 */

use Composer\Autoload\ClassLoader;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Installer;
use phpMyFAQ\Language\LanguageCodes;
use phpMyFAQ\Strings;
use phpMyFAQ\System;

define('PMF_ROOT_DIR', dirname(__FILE__, 2));
const PMF_SRC_DIR = PMF_ROOT_DIR . '/src';
const IS_VALID_PHPMYFAQ = null;

if (version_compare(PHP_VERSION, '8.0.0') < 0) {
    die('Sorry, but you need PHP 8.0.0 or later!');
}

set_time_limit(0);

if (!defined('DEBUG')) {
    define('DEBUG', true);
}

if (DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL | E_STRICT);
}

session_name('phpmyfaq-setup');
session_start();

require PMF_ROOT_DIR . '/src/libs/autoload.php';
require PMF_ROOT_DIR . '/config/constants.php';
require PMF_ROOT_DIR . '/config/constants_elasticsearch.php';

$loader = new ClassLoader();
$loader->add('phpMyFAQ', PMF_SRC_DIR);
$loader->register();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>phpMyFAQ <?= System::getVersion() ?> Setup</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="application-name" content="phpMyFAQ <?= System::getVersion() ?>">
  <meta name="copyright" content="(c) 2001-<?= date('Y') ?> phpMyFAQ Team">
  <link rel="stylesheet" href="../assets/dist/styles.css">
  <script src="../assets/dist/setup.js"></script>
  <link rel="shortcut icon" href="../assets/themes/default/img/favicon.ico">
</head>
<body>

  <header>
    <div class="px-3 py-2 bg-light">
      <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start">
          <ul class="nav col-12 col-lg-auto my-2 justify-content-center my-md-0 text-small">
            <li class="nav-item">
              <a href="https://www.phpmyfaq.de/documentation" class="nav-link" target="_blank">Documentation</a>
            </li>
            <li class="nav-item">
              <a href="https://www.phpmyfaq.de/support" class="nav-link" target="_blank">Support</a>
            </li>
            <li class="nav-item">
              <a href="https://forum.phpmyfaq.de/" class="nav-link" target="_blank">Forums</a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </header>

<main role="main">
    <section id="content">

        <div class="bg-dark text-secondary px-4 py-5 text-center">
            <div class="py-5">
                <h1 class="display-5 fw-bold text-white">phpMyFAQ <?= System::getVersion() ?></h1>
                <div class="col-lg-6 mx-auto">
                    <p class="fs-5 mb-4">
                        Did you already read our
                        <a target="_blank" href="https://www.phpmyfaq.de/docs/3.2">documentation</a>
                        carefully before starting the phpMyFAQ setup?
                    </p>
                </div>
            </div>
        </div>

    <div class="container mt-4 mb-3">

      <div class="pmf-setup-stepwizard">
        <div class="pmf-setup-stepwizard-row setup-panel">
          <div class="pmf-setup-stepwizard-step">
            <a href="#step-1" type="button" class="btn btn-primary pmf-setup-stepwizard-btn-circle">1</a>
            <p>Database setup</p>
          </div>
          <div class="pmf-setup-stepwizard-step">
            <a href="#step-2" type="button" class="btn btn-secondary pmf-setup-stepwizard-btn-circle"
            disabled="disabled">
            2
            </a>
            <p>LDAP setup</p>
          </div>
          <div class="pmf-setup-stepwizard-step">
            <a href="#step-3" type="button" class="btn btn-secondary pmf-setup-stepwizard-btn-circle"
            disabled="disabled">
            3
            </a>
            <p>Elasticsearch setup</p>
          </div>
          <div class="pmf-setup-stepwizard-step">
            <a href="#step-4" type="button" class="btn btn-secondary pmf-setup-stepwizard-btn-circle"
            disabled="disabled">
            4
            </a>
            <p>Admin user setup</p>
          </div>
        </div>
      </div>

<?php
//
// Initialize static string wrapper
//
Strings::init();
$system = new System();
$installer = new Installer();
$installer->checkBasicStuff();
$installer->checkFilesystemPermissions();

// not yet POSTed
if (!isset($_POST['sql_server']) && !isset($_POST['sql_user']) && !isset($_POST['sql_db'])) {
    $installer->checkNoncriticalSettings()
    ?>

      <form action="index.php" method="post" id="phpmyfaq-setup-form" name="phpmyfaq-setup-form"
            class="needs-validation" novalidate>

        <div class="row setup-content" id="step-1">
          <div class="col-12">
            <h3 class="mb-3"> Step 1/4: Database setup</h3>

            <div class="row">
              <label class="col-sm-3 col-form-label" for="sql_type">Server:</label>
              <div class="col-sm-9">
                  <select name="sql_type" id="sql_type" class="form-select">
                      <?= implode('', $system->getSupportedSafeDatabases(true)) ?>
                  </select>
                  <small class="form-text text-muted">Please select your preferred database type.</small>
              </div>
            </div>

            <div id="dbdatafull" style="display: block;">
              <div class="row">
                <label class="col-sm-3 col-form-label" for="sql_server">Host/Socket:</label>
                <div class="col-sm-9">
                  <input type="text" name="sql_server" id="sql_server" class="form-control"
                      placeholder="e.g. 127.0.0.1" required>
                  <small class="form-text text-muted">
                    Please enter the host or path to the socket of your database server.
                  </small>
                </div>
              </div>
              <div class="row">
                <label class="col-sm-3 col-form-label" for="sql_port">Port:</label>
                <div class="col-sm-9">
                  <input type="number" name="sql_port" id="sql_port" class="form-control"
                      value="3306" required>
                  <small class="form-text text-muted">Please enter the port your database server.</small>
                </div>
              </div>
              <div class="row">
                <label class="col-sm-3 col-form-label" for="sql_user">User:</label>
                <div class="col-sm-9">
                    <input type="text" name="sql_user" id="sql_user" class="form-control" required>
                    <small class="form-text text-muted">Please enter your database user.</small>
                </div>
              </div>
              <div class="row">
                <label class="col-sm-3 col-form-label" for="sql_password">Password:</label>
                <div class="col-sm-9">
                    <input name="sql_password" type="password" autocomplete="off" id="sql_password" class="form-control" required>
                    <small class="form-text text-muted">Please enter your database password.</small>
                </div>
              </div>
              <div class="row">
                <label class="col-sm-3 col-form-label" for="sql_db">Database:</label>
                <div class="col-sm-9">
                  <input type="text" name="sql_db" id="sql_db" class="form-control" required>
                  <small class="form-text text-muted">Please enter your existing database name.</small>
                </div>
              </div>
            </div>

            <div id="dbsqlite" style="display: none;">
              <div class="row">
                <label class="col-sm-3 col-form-label" for="sql_sqlitefile">SQLite database file:</label>
                <div class="col-sm-9">
                  <input type="text" name="sql_sqlitefile" id="sql_sqlitefile" class="form-control"
                         value="<?= dirname(__DIR__) ?>" required>
                  <small class="form-text text-muted">
                    Please enter the full path to your SQLite datafile which should be outside your document root.
                  </small>
                </div>
              </div>
            </div>

            <div class="row">
              <label class="col-sm-3 col-form-label" for="sqltblpre">Table prefix:</label>
              <div class="col-sm-9">
                <input type="text" name="sqltblpre" id="sqltblpre" class="form-control">
                <small class="form-text text-muted">
                  Please enter a table prefix here if you want to install more phpMyFAQ installations in one database.
                </small>
              </div>
            </div>
              <button class="btn btn-primary btn-next btn-lg pull-right" type="button"
                data-pmf-current-step="step-1" data-pmf-next-step="step-2">
                Next: LDAP setup
              </button>
            </div>
        </div>


        <div class="row setup-content" id="step-2">
          <div class="col-12">
            <h3 class="mb-3"> Step 2/4: LDAP setup</h3>
            <?php if (extension_loaded('ldap')) : ?>
              <div class="form-group">
                <div class="form-check">
                  <input id="ldap_enabled" class="form-check-input" type="checkbox" name="ldap_enabled"
                         value="yes">
                  <label class="form-check-label" for="ldap_enabled">
                    Enable LDAP support?
                  </label>
                </div>
              </div>
              <div class="row">
                <label class="col-sm-3 col-form-label" for="ldap_server">Host:</label>
                <div class="col-sm-9">
                    <input type="text" name="ldap_server" id="ldap_server" class="form-control"
                           placeholder="127.0.0.1">
                    <small class="form-text text-muted">Please enter the host of your LDAP server.</small>
                </div>
              </div>
              <div class="row">
                <label class="col-sm-3 col-form-label" for="ldap_port">Port:</label>
                <div class="col-sm-9">
                    <input type="number" name="ldap_port" value="389" id="ldap_port" class="form-control">
                    <small class="form-text text-muted">Please enter the port of your LDAP server.</small>
                </div>
              </div>
              <div class="row">
                <label class="col-sm-3 col-form-label" for="ldap_user">User DN:</label>
                <div class="col-sm-9">
                    <input type="text" name="ldap_user" id="ldap_user" class="form-control">
                    <small class="form-text text-muted">Please enter your specified RDN username.</small>
                </div>
              </div>
              <div class="row">
                <label class="col-sm-3 col-form-label" for="ldap_password">Password:</label>
                <div class="col-sm-9">
                    <input name="ldap_password" type="password" autocomplete="off" id="ldap_password" class="form-control">
                    <small class="form-text text-muted">Please enter your LDAP password.</small>
                </div>
              </div>
              <div class="row">
                <label class="col-sm-3 col-form-label" for="ldap_base">Base DN:</label>
                <div class="col-sm-9">
                <input type="text" name="ldap_base" id="ldap_base" class="form-control">
                  <small class="form-text text-muted">
                    Please enter your distinguished name, e.g. 'cn=John Doe,ou=Accounts,o=My Company,c=US'.
                  </small>
                </div>
              </div>
              <p class="alert alert-info">
                You can add additional LDAP configuration in the admin configuration panel.
              </p>
            <?php endif; ?>

            <button class="btn btn-primary btn-next btn-lg pull-right" type="button" data-pmf-current-step="step-2"
              data-pmf-next-step="step-3">
              Next: Elasticsearch setup
            </button>
          </div>
        </div>



        <div class="row setup-content" id="step-3">
          <div class="col-12">
            <h3 class="mb-3"> Step 3/4: Elasticsearch setup</h3>
            <?php if (extension_loaded('curl')) : ?>
              <div class="form-group">
                <div class="form-check">
                  <input id="elasticsearch_enabled" class="form-check-input" type="checkbox"
                         name="elasticsearch_enabled" value="yes">
                  <label class="form-check-label" for="elasticsearch_enabled">
                    Enable Elasticsearch support?
                  </label>
                </div>
                </div>
                <div class="row mb-2">
                  <label class="col-sm-3 col-form-label" for="elasticsearch_server">Server(s):</label>
                  <div class="col-sm-9">
                    <div class="input-group" id="elasticsearch-server-wrapper">
                      <input type="text" name="elasticsearch_server[]" id="elasticsearch_server"
                             class="form-control" placeholder="127.0.0.1:9200">
                      <span class="input-group-text" id="pmf-add-elasticsearch-host" style="cursor: pointer;">
                        Add another Elasticsearch Host
                      </span>
                    </div>
                    <small class="form-text text-muted">
                      Please enter the host (domain or IP) with port number of your Elasticsearch server.
                    </small>
                  </div>
                </div>
                <div class="row">
                  <label class="col-sm-3 col-form-label" for="elasticsearch_index">Index name:</label>
                  <div class="col-sm-9">
                    <input type="text" name="elasticsearch_index" id="elasticsearch_index" class="form-control">
                    <small class="form-text text-muted">Please enter your Elasticsearch index name.</small>
                  </div>
                </div>
            <?php endif; ?>
            <button class="btn btn-primary btn-next btn-lg pull-right" type="button" data-pmf-current-step="step-3"
              data-pmf-next-step="step-4">
              Next: Admin user setup
            </button>
          </div>
        </div>



        <div class="row setup-content" id="step-4">
          <div class="col-12">
            <h3 class="mb-3"> Step 4/4: Admin user setup</h3>

              <div class="row">
                <label class="col-sm-3 col-form-label" for="language">Default language:</label>
                <div class="col-sm-9">
                  <select name="language" id="language" class="form-control">
                  <?php
                    if ($dir = opendir(PMF_ROOT_DIR . '/lang')) {
                        $options = [];
                        while ($dat = readdir($dir)) {
                            $option = '';
                            if (str_ends_with($dat, '.php')) {
                                $option .= sprintf('<option value="%s"', $dat);
                                if ($dat == 'language_en.php') {
                                    $option .= ' selected="selected"';
                                }
                                $language = LanguageCodes::get(substr($dat, 9, -4));
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
                  <small class="form-text text-muted">Please select your default language.</small>
                </div>
            </div>

            <div class="row">
              <label class="col-sm-3 col-form-label" for="permLevel">Permission level:</label>
              <div class="col-sm-9">
                <select id="permLevel" name="permLevel" class="form-control" required>
                  <option value="basic">Basic (no group support)</option>
                  <option value="medium">Medium (with group support)</option>
                  <option value="large">Large (with sections support)</option>
                </select>
                <small class="form-text text-muted">
                  Complexity of rights and permissions.
                </small>
              </div>
            </div>
            <div class="row">
              <label class="col-sm-3 col-form-label" for="realname">Your name:</label>
              <div class="col-sm-9">
                <input type="text" name="realname" id="realname" class="form-control" required>
                <small class="form-text text-muted">Please enter your real name.</small>
              </div>
            </div>
            <div class="row">
              <label class="col-sm-3 col-form-label" for="email">Your email address:</label>
              <div class="col-sm-9">
                <input type="email" name="email" id="email" class="form-control" required>
                <small class="form-text text-muted">Please enter your email address.</small>
              </div>
            </div>
            <div class="row">
              <label class="col-sm-3 col-form-label" for="loginname">Your login name:</label>
              <div class="col-sm-9">
                <input type="text" name="loginname" id="loginname" class="form-control" required>
                <small class="form-text text-muted">Please enter your login name.</small>
              </div>
            </div>
            <div class="row">
              <label class="col-sm-3 col-form-label" for="password">Your password:</label>
              <div class="col-sm-9">
                <input type="password" autocomplete="off" name="password" id="password" class="form-control" required>
                <small class="form-text text-muted">Please enter your password.</small>
              </div>
            </div>
            <div class="row">
              <label class="col-sm-3 col-form-label" for="password_retype">Retype password:</label>
              <div class="col-sm-9">
                <input type="password" autocomplete="off" name="password_retyped" id="password_retype" class="form-control"
                       required>
                <small class="form-text text-muted">Please retype your password.</small>
              </div>
            </div>
            <div class="row">
              <div class="col-sm">
                <p class="alert alert-info text-center">
                  <i aria-hidden="true" class="fa fa-info-circle fa-fw"></i>
                  Your password will be saved with a <strong>salted <?= PMF_ENCRYPTION_TYPE ?></strong>. You can
                  change the encryption type for passwords in <em>config/constants.php</em>.
                </p>
              </div>
            </div>

            <button class="btn btn-primary btn-next btn-lg pull-right" type="button" data-pmf-current-step="step-4"
              data-pmf-next-step="submit">Click to install phpMyFAQ <?= System::getVersion() ?></button>
          </div>
        </div>

      </form>

<?php
} else {
    try {
        $installer->startInstall();
    } catch (Exception $e) {
        echo $e->getMessage();
    }
?>
  <div class="row" id="done">
    <div class="col-12">
      <h3 class="mb-3"> Step 2: Admin user setup</h3>

      <p class="alert alert-success">
        Wow, looks like the installation worked like a charm. This is pretty cool, isn't it? :-)
      </p>

      <p>
        You can visit <a href="../index.php">your version of phpMyFAQ</a> or login into your
        <a href="../admin/index.php">admin section</a>.
       </p>
     </div>
   </div>
<?php
}

System::renderFooter();
