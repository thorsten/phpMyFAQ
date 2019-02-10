<?php
/**
 * The main phpMyFAQ Setup.
 *
 * This script checks the complete environment, writes the database connection
 * parameters into the file config/database.php and the configuration into the database.
 *
 * PHP Version 5.5
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

define('COPYRIGHT', '&copy; 2001-2018 <a href="https://www.phpmyfaq.de/">phpMyFAQ Team</a> | Follow us on <a href="http://twitter.com/phpMyFAQ">Twitter</a> | All rights reserved.');
define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));
define('PMF_INCLUDE_DIR', PMF_ROOT_DIR.'/inc');
define('IS_VALID_PHPMYFAQ', null);

if (version_compare(PHP_VERSION, '5.5.0') < 0) {
    die('Sorry, but you need PHP 5.5.0 or later!'); // Die hard because of "use"
}

set_time_limit(0);

use Symfony\Component\ClassLoader\UniversalClassLoader;

if (!defined('DEBUG')) {
    define('DEBUG', false);
}

session_name('phpmyfaq-setup');
session_start();

require PMF_ROOT_DIR.'/config/constants.php';
require PMF_ROOT_DIR.'/config/constants_elasticsearch.php';

//
// Setting up Symfony PSR-0 autoloader
//
require PMF_INCLUDE_DIR.'/libs/Symfony/Component/ClassLoader/UniversalClassLoader.php';

$loader = new UniversalClassLoader();
$loader->registerNamespace('Symfony', PMF_INCLUDE_DIR.'/libs');
$loader->registerPrefix('PMF_', PMF_INCLUDE_DIR);
$loader->register();

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
    <script src="../assets/js/phpmyfaq.min.js"></script>
    <script src="../assets/js/setup.js"></script>

    <link rel="shortcut icon" href="../assets/template/default/favicon.ico">

</head>
<body>

<nav class="navbar navbar-default" role="navigation">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse"
            data-target="#phpmyfaq-navbar-collapse" aria-expanded="false">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
        </div>
        <div class="collapse navbar-collapse" id="phpmyfaq-navbar-collapse">
            <ul class="nav navbar-nav">
                <li><a target="_blank" href="https://www.phpmyfaq.de/documentation">Documentation</a></li>
                <li><a target="_blank" href="https://www.phpmyfaq.de/support">Support</a></li>
                <li><a target="_blank" href="http://forum.phpmyfaq.de/">Forums</a></li>
                <li><a target="_blank" href="http://faq.phpmyfaq.de/">FAQ</a></li>
            </ul>
        </div>
    </div>
</nav>

<section id="content">
    <div class="container">
        <div class="row">
            <div class="jumbotron text-center">
                <h1>phpMyFAQ <?php echo PMF_System::getVersion() ?> Setup</h1>
                <p>
                    Did you already read the
                    <a target="_blank" href="https://www.phpmyfaq.de/documentation">documentation</a>
                    carefully before starting the phpMyFAQ setup?
                </p>
            </div>
        </div>

        <div class="row">
<?php
//
// Initialize static string wrapper
//
PMF_String::init('en');

$installer = new PMF_Installer();
$system = new PMF_System();

$installer->checkBasicStuff();
$installer->checkFilesystemPermissions();

// not yet POSTed
if (!isset($_POST['sql_server']) && !isset($_POST['sql_user']) && !isset($_POST['sql_db'])) {
    $installer->checkNoncriticalSettings()
?>
        </div>

        <form class="form-horizontal" action="index.php" method="post">
        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                <fieldset>
                <legend>Database setup</legend>
                    <div class="form-group">
                        <label class="col-sm-4 control-label" for="sql_type">Server:</label>
                        <div class="col-sm-8">
                            <select name="sql_type" id="sql_type" size="1" class="form-control">
                                <?php echo implode('', $system->getSupportedSafeDatabases(true)) ?>
                            </select>
                            <p class="help-block">Please select your preferred database type.</p>
                        </div>
                    </div>

                    <div id="dbdatafull">
                        <div class="form-group">
                            <label class="col-sm-4 control-label" for="sql_server">Host/Socket:</label>
                            <div class="col-sm-8">
                                <input type="text" name="sql_server" id="sql_server" class="form-control"
                                    placeholder="e.g. 127.0.0.1">
                                <p class="help-block">Please enter the host or path to the socket of your database server.</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label" for="sql_user">User:</label>
                            <div class="col-sm-8">
                                <input type="text" name="sql_user" id="sql_user" class="form-control">
                                <p class="help-block">Please enter your database user.</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label" for="sql_password">Password:</label>
                            <div class="col-sm-8">
                                <input name="sql_password" type="password" id="sql_password" class="form-control">
                                <p class="help-block">Please enter your database password.</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label" for="sql_db">Database:</label>
                            <div class="col-sm-8">
                                <input type="text" name="sql_db" id="sql_db" class="form-control">
                                <p class="help-block">Please enter your existing database name.</p>
                            </div>
                        </div>
                    </div>

                    <div id="dbsqlite" class="hide">
                        <div class="form-group">
                            <label class="col-sm-4 control-label" for="sql_sqlitefile">SQLite database file:</label>
                            <div class="col-sm-8">
                                <input type="text" name="sql_sqlitefile" id="sql_sqlitefile" class="form-control"
                                       value="<?php echo dirname(__DIR__) ?>">
                                <p class="help-block">
                                    Please enter the full path to your SQLite datafile which should be outside your
                                    docroot.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-4 control-label" for="sqltblpre">Table prefix:</label>
                        <div class="col-sm-8">
                            <input type="text" name="sqltblpre" id="sqltblpre" class="form-control">
                            <p class="help-block">
                                Please enter a table prefix here if you want to install more phpMyFAQ installations on
                                one database.
                            </p>
                        </div>
                    </div>
                </fieldset>
            </div>

            <?php if (extension_loaded('ldap')): ?>
            <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                <fieldset>
                <legend>LDAP setup</legend>
                    <div class="form-group">
                        <label class="col-sm-4 control-label" for="ldap_enabled">&nbsp;</label>
                        <div class="col-sm-8">
                            <label class="checkbox-inline">
                                <input id="ldap_enabled" type="checkbox" name="ldap_enabled" value="yes">
                                Enable LDAP support?
                            </label>
                            <p class="help-block">You can enable LDAP later if you like.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label" for="ldap_server">Host:</label>
                        <div class="col-sm-8">
                            <input type="text" name="ldap_server" id="ldap_server" class="form-control"
                                   placeholder="127.0.0.1">
                            <p class="help-block">Please enter the host of your LDAP server.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label" for="ldap_port">Port:</label>
                        <div class="col-sm-8">
                            <input type="number" name="ldap_port" value="389" id="ldap_port" class="form-control">
                            <p class="help-block">Please enter the port of your LDAP server.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label" for="ldap_user">User DN:</label>
                        <div class="col-sm-8">
                            <input type="text" name="ldap_user" id="ldap_user" class="form-control">
                            <p class="help-block">Please enter your specified RDN username.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label" for="ldap_password">Password:</label>
                        <div class="col-sm-8">
                            <input name="ldap_password" type="password" id="ldap_password" class="form-control">
                            <p class="help-block">Please enter your LDAP password.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label" for="ldap_base">Base DN:</label>
                        <div class="col-sm-8">
                        <input type="text" name="ldap_base" id="ldap_base" class="form-control">
                            <p class="help-block">
                                Please enter your distinguished name, e.g. 'cn=John Doe,ou=Accounts,o=My Company,c=US'.
                            </p>
                        </div>
                    </div>
                    <p class="alert alert-info">
                        You can add additional LDAP configuration and more LDAP servers in the file
                        config/constants_ldap.php.
                    </p>

                </fieldset>
            </div>
            <?php endif; ?>

            <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                <fieldset>
                <legend>phpMyFAQ setup</legend>
                    <div class="form-group">
                        <label class="col-sm-4 control-label" for="language">Default language:</label>
                        <div class="col-sm-8">
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
                            <p class="help-block">Please select your default language.</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-4 control-label" for="permLevel">Permission level:</label>
                        <div class="col-sm-8">
                                <select id="permLevel" name="permLevel" size="1" class="form-control">
                                    <option value="basic">Basic (no group support)</option>
                                    <option value="medium">Medium (with group support)</option>
                            </select>
                            <p class="help-block">
                                Complexity of rights and permissions.
                            </p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label" for="realname">Your name:</label>
                        <div class="col-sm-8">
                            <input type="text" name="realname" id="realname" class="form-control">
                            <p class="help-block">Please enter your real name.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label" for="email">Your email address:</label>
                        <div class="col-sm-8">
                            <input type="email" name="email" id="email" class="form-control">
                            <p class="help-block">Please enter your email adress.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label" for="loginname">Your login name:</label>
                        <div class="col-sm-8">
                            <input type="text" name="loginname" id="loginname" class="form-control">
                            <p class="help-block">Please enter your login name.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label" for="password">Your password:</label>
                        <div class="col-sm-8">
                            <input type="password" name="password" id="password" class="form-control">
                            <p class="help-block">Please enter your password.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label" for="password_retype">Retype password:</label>
                        <div class="col-sm-8">
                            <input type="password" name="password_retyped" id="password_retype" class="form-control">
                            <p class="help-block">Please retype your password.</p>
                        </div>
                    </div>
                </fieldset>
            </div>

            <?php if (extension_loaded('curl')): ?>
            <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                <fieldset>
                    <legend>Elasticsearch setup</legend>
                    <div class="form-group">
                        <label class="col-sm-4 control-label" for="elasticsearch_enabled">&nbsp;</label>
                        <div class="col-sm-8">
                            <label class="checkbox-inline">
                                <input id="elasticsearch_enabled" type="checkbox" name="elasticsearch_enabled"
                                value="yes">
                                Enable Elasticsearch support?
                            </label>
                            <p class="help-block">You can enable Elasticsearch later if you like.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label" for="elasticsearch_server">Host:</label>
                        <div class="col-sm-8">
                            <div class="input-group">
                                <input type="text" name="elasticsearch_server[]" id="elasticsearch_server"
                                       class="form-control" placeholder="127.0.0.1:9200">
                                <span class="input-group-addon">
                                    <a title="Add more Elasticsearch hosts" style="cursor: pointer;" data-action="add">
                                        <i aria-hidden="true" class="fa fa-plus-circle"></i>
                                    </a>
                                </span>
                            </div>
                            <p class="help-block">
                                Please enter the host (domain or IP) with port number of your Elasticsearch server.
                            </p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label" for="elasticsearch_index">Index name:</label>
                        <div class="col-sm-8">
                            <input type="text" name="elasticsearch_index" id="elasticsearch_index" class="form-control">
                            <p class="help-block">Please enter your Elasticsearch index name.</p>
                        </div>
                    </div>
                </fieldset>
            </div>
            <?php endif; ?>

        </div>

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
            </p>
        </div>
        </form>
<?php
    PMF_System::renderFooter();
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
    PMF_System::renderFooter();
}
