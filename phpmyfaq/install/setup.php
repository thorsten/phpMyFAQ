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
 * @copyright 2002-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2002-08-20
 */

define('COPYRIGHT', '&copy; 2001-2012 <a href="http://www.phpmyfaq.de/">phpMyFAQ Team</a> | Follow us on <a href="http://twitter.com/phpMyFAQ">Twitter</a> | All rights reserved.');
define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));
define('IS_VALID_PHPMYFAQ', null);

if (! defined('DEBUG')) {
    define('DEBUG', true);
}

if ((@ini_get('safe_mode') != 'On' || @ini_get('safe_mode') !== 1)) {
    set_time_limit(0);
}

session_name('phpmyfaq-setup');
session_start();

require PMF_ROOT_DIR . '/config/constants.php';
require PMF_ROOT_DIR . '/inc/Autoloader.php';
require PMF_ROOT_DIR . '/install/questionnaire.php';
?>
<!doctype html>
<!--[if lt IE 7 ]> <html lang="en" class="no-js ie6"> <![endif]-->
<!--[if IE 7 ]> <html lang="en" class="no-js ie7"> <![endif]-->
<!--[if IE 8 ]> <html lang="en" class="no-js ie8"> <![endif]-->
<!--[if IE 9 ]> <html lang="en" class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="en" class="no-js"> <!--<![endif]-->
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

    <title>phpMyFAQ <?php echo PMF_System::getVersion(); ?> Setup</title>

    <meta name="viewport" content="width=device-width;">
    <meta name="application-name" content="phpMyFAQ <?php echo PMF_System::getVersion(); ?>">
    <meta name="copyright" content="(c) 2001-<?php echo date('Y'); ?> phpMyFAQ Team">

    <link rel="stylesheet" href="../assets/template/default/css/style.css?v=1">

    <script src="../assets/js/libs/modernizr.min.js"></script>
    <script src="../assets/js/libs/jquery.min.js"></script>

    <link rel="shortcut icon" href="../assets/template/default/favicon.ico">
    <link rel="apple-touch-icon" href="../assets/template/default/apple-touch-icon.png">

    <script type="text/javascript">
        function selectDatabaseSetup(field) {
            switch (field.value) {
            case 'sqlite':
            case 'sqlite3':
                $('#dbsqlite').show();
                $('#dbdatafull').hide();
                break;
            default:
                $('#dbsqlite').hide();
                $('#dbdatafull').show();
                break;
            }
        }
    </script>
</head>
<body>

<!--[if lt IE 8 ]>
<div class="internet-explorer-error">
    Do you know that your Internet Explorer is out of date?<br/>
    Please use Internet Explorer 8+, Mozilla Firefox 4+, Google Chrome, Apple Safari 5+ or Opera 11+
</div>
<![endif]-->

<div class="navbar navbar-fixed-top">
    <div class="navbar-inner">
        <div class="container">
            <nav class="nav-collapse">
                <ul class="nav">
                    <li><a target="_blank" href="http://www.phpmyfaq.de/documentation.php">Documentation</a></li>
                    <li><a target="_blank" href="http://www.phpmyfaq.de/support.php">Support</a></li>
                    <li><a target="_blank" href="http://forum.phpmyfaq.de/">Forums</a></li>
                    <li><a target="_blank" href="http://faq.phpmyfaq.de/">FAQ</a></li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<section id="main">
    <div class="container">
        <div class="row" style="padding-left: 20px;">
            <div class="hero-unit hello-phpmyfaq" style="text-align: center; height: 60px;">
                <h1>phpMyFAQ <?php echo PMF_System::getVersion(); ?> Setup</h1>
                <p>
                    Did you already read the <a style="color: #ffffff; text-decoration: underline;"
                    href="http://www.phpmyfaq.de/documentation.php">documentation</a> carefully before starting the
                    phpMyFAQ setup? :-)
                </p>
            </div>
        </div>
        <div class="row" style="padding-left: 20px;">
<?php
//
// Initalizing static string wrapper
//
PMF_String::init('en');

$installer = new PMF_Installer();
$system    = new PMF_System();

$installer->checkBasicStuff();
$installer->checkFilesystemPermissions();

// not yet POSTed
if (!isset($_POST["sql_server"]) && !isset($_POST["sql_user"]) && !isset($_POST["sql_db"])) {
    $installer->checkNoncriticalSettings()
?>
        </div>

        <form class="form-horizontal" action="setup.php" method="post">
        <div class="row">
            <div class="span6">
                <fieldset>
                <legend>Add your database connection setup</legend>
                    <div class="control-group">
                        <label class="control-label" for="sql_type">Database server:</label>
                        <div class="controls">
                            <select name="sql_type" id="sql_type" size="1" onchange="selectDatabaseSetup(this);">
<?php
    echo join('', $system->getSupportedSafeDatabases(true));
?>
                            </select>
                            <p class="help-block">Please select your preferred database type.</p>
                        </div>
                    </div>

                    <div id="dbdatafull">
                        <div class="control-group">
                            <label class="control-label" for="sql_server">Database hostname:</label>
                            <div class="controls">
                                <input type="text" name="sql_server" id="sql_server" required />
                                <p class="help-block">Please enter the host of your database server.</p>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="sql_user">Database user:</label>
                            <div class="controls">
                                <input type="text" name="sql_user" id="sql_user" />
                                <p class="help-block">Please enter your database user.</p>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="sql_password">Database password:</label>
                            <div class="controls">
                                <input name="sql_passwort" type="password" id="sql_password" />
                                <p class="help-block">Please enter your database password.</p>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="sql_db">Database name:</label>
                            <div class="controls">
                                <input type="text" name="sql_db" id="sql_db" required />
                                <p class="help-block">Please enter your database name.</p>
                            </div>
                        </div>
                    </div>

                    <div id="dbsqlite" class="hide">
                        <div class="control-group">
                            <label class="control-label" for="sql_sqlitefile">SQLite database file:</label>
                            <div class="controls">
                                <input type="text" name="sql_sqlitefile" id="sql_sqlitefile"
                                       value="<?php echo dirname(__DIR__); ?>" />
                                <p class="help-block">
                                    Please enter the full path to your SQLite datafile which should be outside your
                                    docroot.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="sqltblpre">Table prefix:</label>
                        <div class="controls">
                            <input type="text" name="sqltblpre" id="sqltblpre" />
                            <p class="help-block">
                                Please enter a table prefix here if you want to install more phpMyFAQ installations on
                                one database.
                            </p>
                        </div>
                    </div>
                </fieldset>
            </div>

<?php if (extension_loaded('ldap')): ?>
            <div class="span6">
                <fieldset>
                <legend>Add your LDAP setup</legend>
                    <div class="control-group">
                        <label class="control-label" for="ldap_enabled">&nbsp;</label>
                        <div class="controls">
                            <label class="checkbox">
                                <input id="ldap_enabled" type="checkbox" name="ldap_enabled" value="yes" />
                                Enable LDAP support?
                            </label>
                            <p class="help-block">You can enable LDAP later if you like.</p>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="ldap_server">LDAP server host:</label>
                        <div class="controls">
                            <input type="text" name="ldap_server" id="ldap_server" />
                            <p class="help-block">Please enter the host of your LDAP server.</p>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="ldap_port">LDAP server port:</label>
                        <div class="controls">
                            <input type="number" name="ldap_port" value="389" id="ldap_port" />
                            <p class="help-block">Please enter the port of your LDAP server.</p>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="ldap_user">LDAP user DN:</label>
                        <div class="controls">
                            <input type="text" name="ldap_user" id="ldap_user" />
                            <p class="help-block">Please enter your specified RDN username.</p>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="ldap_password">LDAP password:</label>
                        <div class="controls">
                            <input name="ldap_password" type="password" id="ldap_password" />
                            <p class="help-block">Please enter your LDAP password.</p>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="ldap_base">LDAP base DN:</label>
                        <div class="controls">
                        <input type="text" name="ldap_base" id="ldap_base" />
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
        </div>
        <div class="row">
<?php endif; ?>

            <div class="span6">
                <fieldset>
                <legend>Your phpMyFAQ setup</legend>
                    <div class="control-group">
                        <label class="control-label" for="language">Default language:</label>
                        <div class="controls">
                            <select name="language" size="1" id="language">
                            <?php
                                if ($dir = @opendir(PMF_ROOT_DIR . '/lang')) {
                                    while ($dat = @readdir($dir)) {
                                        if (substr($dat, -4) == '.php') {
                                            printf('<option value="%s"', $dat);
                                            if ($dat == "language_en.php") {
                                                echo ' selected="selected"';
                                            }
                                            printf(
                                                '>%s</option>',
                                                $languageCodes[substr(strtoupper($dat), 9, 2)]
                                            );
                                        }
                                    }
                                } else {
                                    echo '<option>english</option>';
                                }
                            ?>
                            </select>
                            <p class="help-block">Please select your default language.</p>
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="permLevel">Permission level:</label>
                        <div class="controls">
                                <select id="permLevel" name="permLevel" size="1" required="required">
                                    <option value="basic">Basic (no group support)</option>
                                    <option value="medium">Medium (with group support)</option>
                            </select>
                            <p class="help-block">
                                Complexity of rights and permissions.
                            </p>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="realname">Your name:</label>
                        <div class="controls">
                            <input type="text" name="realname" id="realname" required="required" />
                            <p class="help-block">Please enter your real name.</p>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="email">Your email address:</label>
                        <div class="controls">
                            <input type="email" name="email" id="email" required="required" />
                            <p class="help-block">Please enter your email adress.</p>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="loginname">Your login name:</label>
                        <div class="controls">
                            <input type="text" name="loginname" id="loginname" required="required" />
                            <p class="help-block">Please enter your login name.</p>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="password">Your password:</label>
                        <div class="controls">
                            <input type="password" name="password" id="password" required="required" />
                            <p class="help-block">Please enter your password.</p>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="password_retype">Retype password:</label>
                        <div class="controls">
                            <input type="password" name="password_retyped" id="password_retype" required="required" />
                            <p class="help-block">Please retype your password.</p>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>

        <div class="row" style="padding-left: 20px; text-align: center;">

            <p class="alert alert-danger">
                Dude, this is an early alpha version. Please don't install this version on production!
            </p>

            <button class="btn btn-primary btn-large" type="submit">
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
?>
        <p class="alert alert-success">
            Wow, looks like the installation worked like a charm. This is pretty cool, isn't it? :-)
        </p>

        <script type="text/javascript">
        //<![CDATA[
        var iframect = 0;

        function iframeUpdated() {
            if (iframect++ == 0) {
                return;
            }

            $('#questionnaireForm').hide();
            $('#questionnaireThanks').show();
        }

        function hide(item) {
            $('#configliste').hide();
        }

        function show(item) {
            $('#configliste').show();
        }
        //]]>
        </script>
        <iframe onload="iframeUpdated();" name="questionaireResult" style="display:none"></iframe>
        <form action="http://www.phpmyfaq.de/stats/getstatdata.php" method="post" target="questionaireResult"
              id="questionnaireForm" class="form-horizontal">

            <p>
                For further development we would like to get some feedback from our users. Therefore we'd ask you to
                take a few minutes of your time to answer a few questions.
            </p>
            <p class="alert alert-success">
                If you don't want to participate in the survey, you can directly visit
                <a href="../index.php">your version of phpMyFAQ</a> or login into your
                <a href="../admin/index.php">admin section</a>.
            </p>

            <h3>The survey</h3>

            <fieldset>
                <legend>General questions</legend>
                <div class="control-group">
                    <label class="control-label">How do you act like?</label>
                        <div class="controls">
                        <select name="q[individual]">
                            <option value="as an individual">as an individual</option>
                            <option value="as an organisation">as an organisation</option>
                        </select>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label">What kind of organisation is that?</label>
                    <div class="controls">
                        <select name="q[organisation]">
                             <option value="private held">privately held</option>
                             <option value="public held">publicly held</option>
                             <option value="government organisation">governmental organisation</option>
                             <option value="foundation">foundation</option>
                             <option value="other">other</option>
                         </select>
                    </div>
                </div>
             </fieldset>

            <fieldset>
                <legend>Technical questions</legend>
                <div class="control-group">
                    <label class="control-label">Where did you install phpMyFAQ?</label>
                    <div class="controls">
                        <select name="q[server]">
                            <option value="server run by a hosting company">server run by a hosting company</option>
                            <option value="public server run by you/your organisation">public server run by you/your organisation</option>
                            <option value="private server run by you/your organisation">private server run by you/your organisation</option>
                            <option value="Don't know">Don't know</option>
                        </select>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Beyond our own nose</legend>
                <div class="control-group">
                    <label class="control-label">Which PHP software do you also use?</label>
                    <div class="controls">
                        <input type="text" name="q[other]" />
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label">Are you using other web technologies?</label>
                    <div class="controls">
                        <label class="checkbox inline">
                            <input type="checkbox" name="q[other][]" value="ASP" />ASP
                        </label>
                        <label class="checkbox inline">
                            <input type="checkbox" name="q[other][]" value="ASP.NET" />ASP.NET
                        </label>
                        <label class="checkbox inline">
                            <input type="checkbox" name="q[other][]" value="jsp" />JAVA JSP
                        </label>
                        <label class="checkbox inline">
                            <input type="checkbox" name="q[other][]" value="perl" />Perl
                        </label>
                        <label class="checkbox inline">
                            <input type="checkbox" name="q[other][]" value="ruby" />Ruby / Ruby on Rails
                        </label>
                        <label class="checkbox inline">
                            <input type="checkbox" name="q[other][]" value="python" />Python
                        </label>
                        <label class="checkbox inline">
                            <input type="checkbox" name="q[other][]" value="clojure" />Clojure
                        </label>
                        <label class="checkbox inline">
                            <input type="checkbox" name="q[other][]" value="scala" />Scala
                        </label>
                        <label class="checkbox inline">
                            <input type="checkbox" name="q[other][]" value="go" />Go
                        </label>
                    </div>
                </div>
             </fieldset>

            <p class="alert alert-info">
                In addition to your input we're going to submit some information about your system setup for statistical
                purposes.
            </p>
            <p class="alert alert-info">
                We are not storing any personal information. You can see the data by clicking
                <a href="#" onclick="show('configliste'); return false;">here</a>.
            </p>

            <div id="configliste" class="hide">
                <a href="#" onclick="hide('configliste'); return false;">hide again</a>
<?php
$installer->printDataList();
?>
            </div>
            <p style="text-align: center;">
                <button class="btn btn-primary" type="submit">
                    Click here to submit the data and finish the installation process
                </button>
            </p>
        </form>
        <div id="questionnaireThanks" style="display:none;">
            <p class="alert alert-success"><b>Thank you for giving your feedback!</b></p>
            <p>
                You can visit <a href="../index.php">your version of phpMyFAQ</a> or login into your
                <a href="../admin/index.php">admin section</a>.
            </p>
        </div>
<?php
    $installer->cleanUpFiles();
    PMF_System::renderFooter();
}