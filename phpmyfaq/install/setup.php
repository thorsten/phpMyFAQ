<?php
/**
 * The main phpMyFAQ Setup
 *
 * This script checks the complete environment, writes the database connection
 * parameters into the file config/database.php and the configuration into the database.
 * 
 * PHP Version 5.2.3
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 * 
 * @category  phpMyFAQ
 * @package   Setup
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Tom Rochester <tom.rochester@gmail.com>
 * @author    Johannes Schlüter <johannes@php.net>
 * @author    Uwe Pries <uwe.pries@digartis.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2002-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2002-08-20
 */

define('COPYRIGHT', '&copy; 2001-2012 <a href="http://www.phpmyfaq.de/">phpMyFAQ Team</a> | Follow us on <a href="http://twitter.com/phpMyFAQ">Twitter</a> | All rights reserved.');
define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));
define('IS_VALID_PHPMYFAQ', null);

if ((@ini_get('safe_mode') != 'On' || @ini_get('safe_mode') !== 1)) {
    set_time_limit(0);
}

require PMF_ROOT_DIR . '/config/constants.php';
require PMF_ROOT_DIR . '/inc/autoLoader.php';
require PMF_ROOT_DIR . '/inc/functions.php';
require PMF_ROOT_DIR . '/install/questionnaire.php';

//
// Initalizing static string wrapper
//
PMF_String::init('en');

$query = $uninst = array();

/**
 * Print out the HTML5 Footer
 *
 * @return void
 */
function HTMLFooter()
{
    printf('</div></div></section><footer><div class="container"><p class="pull-right">%s</p><div></footer></body></html>', COPYRIGHT);
}

$system = new PMF_System();

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

    <title>phpMyFAQ <?php print PMF_System::getVersion(); ?> Setup</title>

    <meta name="viewport" content="width=device-width;">
    <meta name="application-name" content="phpMyFAQ <?php print PMF_System::getVersion(); ?>">
    <meta name="copyright" content="(c) 2001-<?php print date('Y'); ?> phpMyFAQ Team">

    <link rel="stylesheet" href="../template/default/css/style.css?v=1">

    <script src="../js/libs/modernizr.min.js"></script>
    <script src="../js/libs/jquery.min.js"></script>

    <link rel="shortcut icon" href="../template/default/favicon.ico">
    <link rel="apple-touch-icon" href="../template/default/apple-touch-icon.png">

    <script type="text/javascript">
        function selectDatabaseSetup(field) {
            switch (field.value) {
            case 'sqlite':
                $('#dbsqlite').show()
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
            <div class="hero-unit hello-phpmyfaq" style="text-align: center; height: 70px;">
                <h1>phpMyFAQ <?php print PMF_System::getVersion(); ?> Setup</h1>
                <p>
                    Did you already read the <a style="color: #ffffff; text-decoration: underline;"
                    href="http://www.phpmyfaq.de/documentation.php">documentation</a> carefully before starting the phpMyFAQ
                    setup? :-)
                </p>
            </div>
        </div>
        <div class="row" style="padding-left: 20px;">
<?php

if (version_compare(PHP_VERSION, PMF_System::VERSION_MINIMUM_PHP, '<')) {
    printf('<p class="alert alert-error">Sorry, but you need PHP %s or later!</p>', PMF_System::VERSION_MINIMUM_PHP);
    HTMLFooter();
    die();
}

if (! function_exists('date_default_timezone_set')) {
    print '<p class="alert alert-error">Sorry, but setting a default timezone doesn\'t work in your environment!</p>';
    HTMLFooter();
    die();
}

if (! $system->checkDatabase()) {
    print '<p class="alert alert-error">No supported database detected! Please install one of the following' .
          ' database systems and enable the corresponding PHP extension in php.ini:</p>';
    print '<ul>';
    foreach ($system->getSupportedDatabases() as $database) {
        printf('    <li>%s</li>', $database[1]);
    }
    print '</ul>';
    HTMLFooter();
    die();
}

if (! $system->checkRequiredExtensions()) {
    print '<p class="alert alert-error">The following extensions are missing! Please enable the PHP extension(s) in ' .
          'php.ini.</p>';
    print '<ul>';
    foreach ($system->getMissingExtensions() as $extension) {
        printf('    <li>ext/%s</li>', $extension);
    }
    print '</ul>';
    HTMLFooter();
    die();
}

if (! $system->checkphpMyFAQInstallation()) {
    print '<p class="alert alert-error">It seems you\'re already running a version of phpMyFAQ. Please use the ' .
          '<a href="update.php">update script</a>.</p>';
    HTMLFooter();
    die();
}

$dirs       = array('/attachments', '/config', '/data');
$faileddirs = array();

foreach ($dirs as $dir) {
    if (!@is_dir(PMF_ROOT_DIR . $dir)) {
        if (!@mkdir (PMF_ROOT_DIR . $dir, 0755)) {
            $faileddirs[] = $dir;
        }
    } else if (!@is_writable(PMF_ROOT_DIR . $dir)) {
        $faileddirs[] = $dir;
    } else {
        @copy('index.html', PMF_ROOT_DIR . $dir . '/index.html');
    }
}

if (sizeof($faileddirs)) {
    print '<p class="alert alert-error">The following directory/-ies could not be created or are not writable:</p><ul>';
    foreach ($faileddirs as $dir) {
        print "<li>$dir</li>\n";
    }
    print '</ul><p class="alert alert-error">Please create it manually and/or change access to chmod 755 (or greater if ' .
          'necessary).</p>';
    HTMLFooter();
    die();
}

if (!isset($_POST["sql_server"]) && !isset($_POST["sql_user"]) && !isset($_POST["sql_db"])) {

    if ((@ini_get('safe_mode') == 'On' || @ini_get('safe_mode') === 1)) {
        print '<p class="alert alert-error">The PHP safe mode is enabled. You may have problems when phpMyFAQ tries to write ' .
              ' in some directories.</p>';
    }
    if (! extension_loaded('gd')) {
        print '<p class="alert alert-error">You don\'t have GD support enabled in your PHP installation. Please enable GD ' .
              'support in your php.ini file otherwise you can\'t use Captchas for spam protection.</p>';
    }
    if (! function_exists('imagettftext')) {
        print '<p class="alert alert-error">You don\'t have Freetype support enabled in the GD extension of your PHP ' .
              'installation. Please enable Freetype support in GD extension otherwise the Captchas for spam ' .
              'protection will be quite easy to break.</p>';
    }
    if (! extension_loaded('curl') || ! extension_loaded('openssl')) {
        print '<p class="alert alert-error">You don\'t have cURL and/or OpenSSL support enabled in your PHP installation. ' .
              'Please enable cURL and/or OpenSSL support in your php.ini file otherwise you can\'t use the Twitter ' .
              ' support.</p>';
    }
?>
        </div>

        <form class="form-horizontal" action="setup.php" method="post">
        <div class="row">
            <div class="span6">
                <fieldset>
                <legend>Add your database connection setup</legend>
                    <div class="control-group">
                        <label for="sql_type">Database server:</label>
                        <div class="controls">
                            <select name="sql_type" id="sql_type" size="1" onchange="selectDatabaseSetup(this);">
<?php
    // check what extensions are loaded in PHP
    foreach ($system->getSupportedDatabases() as $extension => $database) {
        if (extension_loaded($extension) && version_compare(PHP_VERSION, $database[0]) >= 0) {
            // prevent MySQLi with zend.ze1_compatibility_mode enabled due to a few cloning isssues
            if (($extension == 'mysqli') && ini_get('zend.ze1_compatibility_mode')) {
                continue;
            }
            printf('<option value="%s">%s</option>', $extension, $database[1]);
        }
    }
?>
                            </select>
                            <p class="help-block">Please select your preferred database type.</p>
                        </div>
                    </div>

                    <div id="dbdatafull">
                        <div class="control-group">
                            <label for="sql_server">Database hostname:</label>
                            <div class="controls">
                                <input type="text" name="sql_server" id="sql_server" />
                                <p class="help-block">Please enter the host of your database server.</p>
                            </div>
                        </div>
                        <div class="control-group">
                            <label for="sql_user">Database user:</label>
                            <div class="controls">
                                <input type="text" name="sql_user" id="sql_user" />
                                <p class="help-block">Please enter your database user.</p>
                            </div>
                        </div>
                        <div class="control-group">
                            <label for="sql_password">Database password:</label>
                            <div class="controls">
                                <input name="sql_passwort" type="password" id="sql_password" />
                                <p class="help-block">Please enter your database password.</p>
                            </div>
                        </div>
                        <div class="control-group">
                            <label for="sql_db">Database name:</label>
                            <div class="controls">
                                <input type="text" name="sql_db" id="sql_db" />
                                <p class="help-block">Please enter your database name.</p>
                            </div>
                        </div>
                    </div>

                    <div id="dbsqlite" class="hide">
                        <div class="control-group">
                            <label for="sql_sqlitefile">SQLite database file:</label>
                            <div class="controls">
                                <input type="text" name="sql_sqlitefile" id="sql_sqlitefile"
                                       value="<?php print dirname(dirname(__FILE__)); ?>" />
                                <p class="help-block">
                                    Please enter the full path to your SQLite datafile which should be outside your
                                    docroot.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="control-group">
                        <label for="sqltblpre">Table prefix:</label>
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
                        <label for="ldap_enabled">&nbsp;</label>
                        <div class="controls">
                            <label class="checkbox">
                                <input id="ldap_enabled" type="checkbox" name="ldap_enabled" value="yes" />
                                Enable LDAP support?
                            </label>
                            <p class="help-block">You can enable LDAP later if you like.</p>
                        </div>
                    </div>
                    <div class="control-group">
                        <label for="ldap_server">LDAP server host:</label>
                        <div class="controls">
                            <input type="text" name="ldap_server" id="ldap_server" />
                            <p class="help-block">Please enter the host of your LDAP server.</p>
                        </div>
                    </div>
                    <div class="control-group">
                        <label for="ldap_port">LDAP server port:</label>
                        <div class="controls">
                            <input type="number" name="ldap_port" value="389" id="ldap_port" />
                            <p class="help-block">Please enter the port of your LDAP server.</p>
                        </div>
                    </div>
                    <div class="control-group">
                        <label for="ldap_user">LDAP user DN:</label>
                        <div class="controls">
                            <input type="text" name="ldap_user" id="ldap_user" />
                            <p class="help-block">Please enter your specified RDN username.</p>
                        </div>
                    </div>
                    <div class="control-group">
                        <label for="ldap_password">LDAP password:</label>
                        <div class="controls">
                            <input name="ldap_password" type="password" id="ldap_password" />
                            <p class="help-block">Please enter your LDAP password.</p>
                        </div>
                    </div>
                    <div class="control-group">
                        <label for="ldap_base">LDAP base DN:</label>
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
                        <label for="language">Default language:</label>
                        <div class="controls">
                            <select name="language" size="1" id="language">
<?php
    if ($dir = @opendir(PMF_ROOT_DIR . '/lang')) {
        while ($dat = @readdir($dir)) {
            if (substr($dat, -4) == '.php') {
                printf('<option value="%s"', $dat);
                if ($dat == "language_en.php") {
                    print ' selected="selected"';
                }
                print '>' . $languageCodes[substr(strtoupper($dat), 9, 2)] . '</option>';
            }
        }
    } else {
        print '<option>english</option>';
    }
?>
                            </select>
                            <p class="help-block">Please select your default language.</p>
                        </div>
                    </div>

                    <div class="control-group">
                        <label for="permLevel">Permission level:</label>
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
                        <label for="realname">Your name:</label>
                        <div class="controls">
                            <input type="text" name="realname" id="realname" required="required" />
                            <p class="help-block">Please enter your real name.</p>
                        </div>
                    </div>
                    <div class="control-group">
                        <label for="email">Your email address:</label>
                        <div class="controls">
                            <input type="email" name="email" id="email" required="required" />
                            <p class="help-block">Please enter your email adress.</p>
                        </div>

                    </div>
                    <div class="control-group">
                        <label for="loginname">Your login name:</label>
                        <div class="controls">
                            <input type="text" name="loginname" id="loginname" required="required" />
                            <p class="help-block">Please enter your login name.</p>
                        </div>
                    </div>
                    <div class="control-group">
                        <label for="password">Your password:</label>
                        <div class="controls">
                            <input type="password" name="password" id="password" required="required" />
                            <p class="help-block">Please enter your password.</p>
                        </div>
                    </div>
                    <div class="control-group">
                        <label for="password_retype">Retype password:</label>
                        <div class="controls">
                            <input type="password" name="password_retyped" id="password_retype" required="required" />
                            <p class="help-block">Please retype your password.</p>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>

        <div class="row" style="padding-left: 20px; text-align: center;">
            <input class="btn-primary btn-large" type="submit"
                   value="Click to install phpMyFAQ <?php print PMF_System::getVersion(); ?>" />
        </div>
        <div class="row" style="padding-left: 20px;">
            <p class="alert alert-info" style="margin-top: 20px;">
                Your password will be saved with a <strong><?php print PMF_ENCRYPTION_TYPE; ?></strong>
                encryption without any salt. You can change the encryption type for passwords in
                <em>config/constants.php</em>.
            </p>
        </div>
        </form>
<?php
    HTMLFooter();
} else {
    
    // Check table prefix
    $sqltblpre = PMF_Filter::filterInput(INPUT_POST, 'sqltblpre', FILTER_SANITIZE_STRING, '');
    if (!defined('SQLPREFIX')) {
        define('SQLPREFIX', $sqltblpre);
    }

    // Check database entries
    $dbType = PMF_Filter::filterInput(INPUT_POST, 'sql_type', FILTER_SANITIZE_STRING);
    if (!is_null($dbType)) {
        $dbType = trim($dbType);
        if (file_exists(PMF_ROOT_DIR . '/install/' . $dbType . '.sql.php')) {
            require PMF_ROOT_DIR . '/install/' . $dbType . '.sql.php';
        } else {
            printf('<p class="alert alert-error"><strong>Error:</strong> Invalid server type: %s</p>', $dbType);
            HTMLFooter();
            die();
        }
    } else {
        print "<p class=\"alert alert-error\"><strong>Error:</strong> Please select a database type.</p>\n";
        HTMLFooter();
        die();
    }

    $dbServer = PMF_Filter::filterInput(INPUT_POST, 'sql_server', FILTER_SANITIZE_STRING);
    if (is_null($dbServer) && $dbType != 'sqlite') {
        print "<p class=\"alert alert-error\"><strong>Error:</strong> Please add a database server.</p>\n";
        HTMLFooter();
        die();
    }

    $dbUser = PMF_Filter::filterInput(INPUT_POST, 'sql_user', FILTER_SANITIZE_STRING);
    if (is_null($dbUser) && $dbType != 'sqlite') {
        print "<p class=\"alert alert-error\"><strong>Error:</strong> Please add a database username.</p>\n";
        HTMLFooter();
        die();
    }

    $dbPassword = PMF_Filter::filterInput(INPUT_POST, 'sql_passwort', FILTER_SANITIZE_STRING);
    if (is_null($dbPassword) && $dbType == 'sqlite') {
        // Password can be empty...
        $dbPassword = '';
    }

    $dbDatabaseName = PMF_Filter::filterInput(INPUT_POST, 'sql_db', FILTER_SANITIZE_STRING);
    if (is_null($dbDatabaseName) && $dbType != 'sqlite') {
        print "<p class=\"alert alert-error\"><strong>Error:</strong> Please add a database name.</p>\n";
        HTMLFooter();
        die();
    }

    if ($dbType == 'sqlite') {
        $sqliteFile = PMF_Filter::filterInput(INPUT_POST, 'sql_sqlitefile', FILTER_SANITIZE_STRING);
        if (!is_null($sqliteFile)) {
            $dbServer = $sqliteFile; // We're using $dbServer, too!
        } else {
            print "<p class=\"alert alert-error\"><strong>Error:</strong> Please add a SQLite database filename.</p>\n";
            HTMLFooter();
            die();
        }
    }

    // check database connection
    require PMF_ROOT_DIR . "/inc/Db.php";
    require PMF_ROOT_DIR . "/inc/PMF_DB/Driver.php";
    $db = PMF_Db::factory($dbType);
    $db->connect($dbServer, $dbUser, $dbPassword, $dbDatabaseName);
    if (!$db) {
        printf("<p class=\"alert alert-error\"><strong>DB Error:</strong> %s</p>\n", $db->error());
        HTMLFooter();
        die();
    }

    // check LDAP if available
    $ldapEnabled = PMF_Filter::filterInput(INPUT_POST, 'ldap_enabled', FILTER_SANITIZE_STRING);
    if (extension_loaded('ldap') && !is_null($ldapEnabled)) {

        // check LDAP entries
        $ldapServer = PMF_Filter::filterInput(INPUT_POST, 'ldap_server', FILTER_SANITIZE_STRING);
        if (is_null($ldapServer)) {
            print "<p class=\"alert alert-error\"><strong>Error:</strong> Please add a LDAP server.</p>\n";
            HTMLFooter();
            die();
        }
        
        $ldapPort = PMF_Filter::filterInput(INPUT_POST, 'ldap_port', FILTER_VALIDATE_INT);
        if (is_null($ldapPort)) {
            print "<p class=\"alert alert-error\"><strong>Error:</strong> Please add a LDAP port.</p>\n";
            HTMLFooter();
            die();
        }

        $ldapBase = PMF_Filter::filterInput(INPUT_POST, 'ldap_base', FILTER_SANITIZE_STRING);
        if (is_null($ldapBase)) {
            print "<p class=\"alert alert-error\"><strong>Error:</strong> Please add a LDAP base search DN.</p>\n";
            HTMLFooter();
            die();
        }

        // LDAP User and LDAP password are optional
        $ldapUser     = PMF_Filter::filterInput(INPUT_POST, 'ldap_user', FILTER_SANITIZE_STRING, '');
        $ldapPassword = PMF_Filter::filterInput(INPUT_POST, 'ldap_password', FILTER_SANITIZE_STRING, '');

        // check LDAP connection
        require PMF_ROOT_DIR . "/inc/Ldap.php";
        $ldap = new PMF_Ldap($ldapServer, $ldapPort, $ldapBase, $ldapUser, $ldapPassword);
        if (!$ldap) {
            print "<p class=\"alert alert-error\"><strong>LDAP Error:</strong> " . $ldap->error() . "</p>\n";
            HTMLFooter();
            die();
        }
    }

    // check loginname
    $loginname = PMF_Filter::filterInput(INPUT_POST, 'loginname', FILTER_SANITIZE_STRING);
    if (is_null($loginname)) {
        print '<p class="alert alert-error"><strong>Error:</strong> Please add a loginname for your account.</p>';
        HTMLFooter();
        die();
    }

    // check user entries
    $password = PMF_Filter::filterInput(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    if (is_null($password)) {
        print '<p class="alert alert-error"><strong>Error:</strong> Please add a password for the your account.</p>';
        HTMLFooter();
        die();
    }
    
    $password_retyped = PMF_Filter::filterInput(INPUT_POST, 'password_retyped', FILTER_SANITIZE_STRING);
    if (is_null($password_retyped)) {
        print '<p class="alert alert-error"><strong>Error:</strong> Please add a retyped password.</p>';
        HTMLFooter();
        die();
    }
    
    if (strlen($password) <= 5 || strlen($password_retyped) <= 5) {
        print '<p class="alert alert-error"><strong>Error:</strong> Your password and retyped password are too short.' .
              ' Please set your password and your retyped password with a minimum of 6 characters.</p>';
        HTMLFooter();
        die();
    }
    if ($password != $password_retyped) {
        print '<p class="alert alert-error"><strong>Error:</strong> Your password and retyped password are not equal.' .
              ' Please check your password and your retyped password.</p>';
        HTMLFooter();
        die();
    }

    $language  = PMF_Filter::filterInput(INPUT_POST, 'language', FILTER_SANITIZE_STRING, 'en');
    $realname  = PMF_Filter::filterInput(INPUT_POST, 'realname', FILTER_SANITIZE_STRING, '');
    $email     = PMF_Filter::filterInput(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL, '');
    $permLevel = PMF_Filter::filterInput(INPUT_POST, 'permLevel', FILTER_SANITIZE_STRING, 'basic');
    
    // Write the DB variables in database.php
    $datafile = PMF_ROOT_DIR . '/config/database.php';
    $ret = file_put_contents(
        $datafile,
        "<?php\n\$DB[\"server\"] = '" . $dbServer . "';\n" .
        "\$DB[\"user\"] = '" . $dbUser . "';\n" .
        "\$DB[\"password\"] = '" . $dbPassword . "';\n" .
        "\$DB[\"db\"] = '" . $dbDatabaseName . "';\n" .
        "\$DB[\"prefix\"] = '" . $sqltblpre . "';\n" .
        "\$DB[\"type\"] = '" . $dbType . "';",
        LOCK_EX
    );
    
    if (!$ret) {
        print "<p class=\"alert alert-error\"><strong>Error:</strong> Setup cannot write to ./config/database.php.</p>";
        HTMLFooter();
        $system->cleanInstallation();
        die();
    }

    // check LDAP if available
    if (extension_loaded('ldap') && !is_null($ldapEnabled)) {
        $datafile = PMF_ROOT_DIR . '/config/ldap.php';
        $ret = file_put_contents(
            $datafile,
            "<?php\n\$PMF_LDAP[\"ldap_server\"] = '" . $ldapServer . "';\n" .
            "\$PMF_LDAP[\"ldap_port\"] = '" . $ldapPort . "';\n" .
            "\$PMF_LDAP[\"ldap_user\"] = '" . $ldapUser . "';\n" .
            "\$PMF_LDAP[\"ldap_password\"] = '" . $ldapPassword . "';\n" .
            "\$PMF_LDAP[\"ldap_base\"] = '" . $ldapBase . "';",
            LOCK_EX
        );
        if (!$ret) {
            print "<p class=\"alert alert-error\"><strong>Error:</strong> Setup cannot write to ./config/ldap.php.</p>";
            HTMLFooter();
            $system->cleanInstallation();
            die();
        }
    }

    // connect to the database using config/database.php
    require PMF_ROOT_DIR . '/config/database.php';
    $db = PMF_Db::factory($dbType);
    $db->connect($DB['server'], $DB['user'], $DB['password'], $DB['db']);
    if (!$db) {
        print "<p class=\"alert alert-error\"><strong>DB Error:</strong> ".$db->error()."</p>\n";
        HTMLFooter();
        $system->cleanInstallation();
        die();
    }

    require_once $dbType . '.sql.php'; // CREATE TABLES
    require_once 'config.sql.php';     // INSERTs for configuration
    require_once 'stopwords.sql.php';  // INSERTs for stopwords

    $system->setDatabase($db);

    print '<p>';

    // Erase any table before starting creating the required ones
    if ('sqlite' != $dbType) {
        $system->dropTables($uninst);
    }
    
    // Start creating the required tables
    $count = 0;
    foreach ($query as $executeQuery) {
        $result = @$db->query($executeQuery);
        if (!$result) {
            print '<p class="alert alert-error"><strong>Error:</strong> Please install your version of phpMyFAQ once again or send
            us a <a href=\"http://www.phpmyfaq.de\" target=\"_blank\">bug report</a>.</p>';
            printf('<p class="alert alert-error"><strong>DB error:</strong> %s</p>', $db->error());
            printf('<code>%s</code>', htmlentities($executeQuery));
            $system->dropTables($uninst);
            $system->cleanInstallation();
            HTMLFooter();
            die();
        }
        usleep(2500);
        $count++;
        if (!($count % 10)) {
            print '| ';
        }
    }

    // add main configuration
    $configuration = PMF_Configuration::getInstance();
    $configuration->getAll();

    $configs = $configuration->config;

    $configs['spam.enableCaptchaCode'] = (extension_loaded('gd') ? 'true' : 'false');
    $configs['main.referenceURL']      = PMF_Link::getSystemUri('/install/setup.php');
    $configs['main.phpMyFAQToken']     = md5(uniqid(rand()));

    $configuration->update($configs);

    // add admin account and rights
    $admin = new PMF_User();
    $admin->createUser($loginname, $password, 1);
    $admin->setStatus('protected');
    $adminData = array(
        'display_name' => $realname,
        'email'        => $email
    );
    $admin->setUserData($adminData);
    $adminId = $admin->getUserId();

    // add rights
    $rights = array(
        //1 => "adduser",
        array(
            'name' => 'adduser',
            'description' => 'Right to add user accounts',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //2 => "edituser",
        array(
            'name' => 'edituser',
            'description' => 'Right to edit user accounts',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //3 => "deluser",
        array(
            'name' => 'deluser',
            'description' => 'Right to delete user accounts',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //4 => "addbt",
        array(
            'name' => 'addbt',
            'description' => 'Right to add faq entries',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //5 => "editbt",
        array(
            'name' => 'editbt',
            'description' => 'Right to edit faq entries',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //6 => "delbt",
        array(
            'name' => 'delbt',
            'description' => 'Right to delete faq entries',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //7 => "viewlog",
        array(
            'name' => 'viewlog',
            'description' => 'Right to view logfiles',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //8 => "adminlog",
        array(
            'name' => 'adminlog',
            'description' => 'Right to view admin log',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //9 => "delcomment",
        array(
            'name' => 'delcomment',
            'description' => 'Right to delete comments',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //10 => "addnews",
        array(
            'name' => 'addnews',
            'description' => 'Right to add news',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //11 => "editnews",
        array(
            'name' => 'editnews',
            'description' => 'Right to edit news',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //12 => "delnews",
        array(
            'name' => 'delnews',
            'description' => 'Right to delete news',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //13 => "addcateg",
        array(
            'name' => 'addcateg',
            'description' => 'Right to add categories',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //14 => "editcateg",
        array(
            'name' => 'editcateg',
            'description' => 'Right to edit categories',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //15 => "delcateg",
        array(
            'name' => 'delcateg',
            'description' => 'Right to delete categories',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //16 => "passwd",
        array(
            'name' => 'passwd',
            'description' => 'Right to change passwords',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //17 => "editconfig",
        array(
            'name' => 'editconfig',
            'description' => 'Right to edit configuration',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //18 => "addatt", // Duplicate, removed with 2.7.3
        //array(
        //    'name' => 'addatt',
        //    'description' => 'Right to add attachments',
        //    'for_users' => 1,
        //    'for_groups' => 1
        //),
        //19 => "backup delatt", // Duplicate, removed with 2.7.3
        //array(
        //    'name' => 'delatt',
        //    'description' => 'Right to delete attachments',
        //    'for_users' => 1,
        //    'for_groups' => 1
        //),
        //20 => "backup",
        array(
            'name' => 'backup',
            'description' => 'Right to save backups',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //21 => "restore",
        array(
            'name' => 'restore',
            'description' => 'Right to load backups',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //22 => "delquestion",
        array(
            'name' => 'delquestion',
            'description' => 'Right to delete questions',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //23 => 'addglossary',
        array(
            'name' => 'addglossary',
            'description' => 'Right to add glossary entries',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //24 => 'editglossary',
        array(
            'name' => 'editglossary',
            'description' => 'Right to edit glossary entries',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //25 => 'delglossary'
        array(
            'name' => 'delglossary',
            'description' => 'Right to delete glossary entries',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //26 => 'changebtrevs'
        array(
            'name' => 'changebtrevs',
            'description' => 'Right to edit revisions',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //27 => "addgroup",
        array(
            'name' => 'addgroup',
            'description' => 'Right to add group accounts',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //28 => "editgroup",
        array(
            'name' => 'editgroup',
            'description' => 'Right to edit group accounts',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //29 => "delgroup",
        array(
            'name' => 'delgroup',
            'description' => 'Right to delete group accounts',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //30 => "addtranslation",
        array(
            'name' => 'addtranslation',
            'description' => 'Right to add translation',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //31 => "edittranslation",
        array(
            'name' => 'edittranslation',
            'description' => 'Right to edit translations',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //32 => "deltranslation",
        array(
            'name' => 'deltranslation',
            'description' => 'Right to delete translations',
            'for_users' => 1,
            'for_groups' => 1
        ),
        // 33 => 'approverec'
        array(
            'name' => 'approverec',
            'description' => 'Right to approve records',
            'for_users' => 1,
            'for_groups' => 1
        ),
        // 34 => 'addattachment'
        array(
            'name' => 'addattachment',
            'description' => 'Right to add attachments',
            'for_users' => 1,
            'for_groups' => 1
        ),
        // 35 => 'editattachment'
        array(
            'name' => 'editattachment',
            'description' => 'Right to edit attachments',
            'for_users' => 1,
            'for_groups' => 1
        ),
        // 36 => 'delattachment'
        array(
            'name' => 'delattachment',
            'description' => 'Right to delete attachments',
            'for_users' => 1,
            'for_groups' => 1
        ),
        // 37 => 'dlattachment'
        array(
            'name' => 'dlattachment',
            'description' => 'Right to download attachments',
            'for_users' => 1,
            'for_groups' => 1
        ),
        // 38 => 'dlattachment'
        array(
            'name' => 'reports',
            'description' => 'Right to generate reports',
            'for_users' => 1,
            'for_groups' => 1
        ),
        // 39 => 'addfaq'
        array(
            'name' => 'addfaq',
            'description' => 'Right to add FAQs in frontend',
            'for_users' => 1,
            'for_groups' => 1
        ),
        // 40 => 'addquestion'
        array(
            'name' => 'addquestion',
            'description' => 'Right to add questions in frontend',
            'for_users' => 1,
            'for_groups' => 1
        ),
        // 41 => 'addcomment'
        array(
            'name' => 'addcomment',
            'description' => 'Right to add comments in frontend',
            'for_users' => 1,
            'for_groups' => 1
        ),
    );
    
    foreach ($rights as $right) {
        $rightId = $admin->perm->addRight($right);
        $admin->perm->grantUserRight($adminId, $rightId);
    }
    
    // Add anonymous user account
    $anonymous = new PMF_User();
    $anonymous->createUser('anonymous', null, -1);
    $anonymous->setStatus('protected');
    $anonymousData = array(
        'display_name' => 'Anonymous User',
        'email'        => null
    );
    $anonymous->setUserData($anonymousData);

    print '</p>';

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
            <p>
                If you don't want to participate in the survey, you can directly visit
                <a href="../index.php">your version of phpMyFAQ</a> or login into your
                <a href="../admin/index.php">admin section</a>.
            </p>

            <fieldset>
                <legend>General questions</legend>
                <div class="control-group">
                    <label>How do you act like?</label>
                        <div class="controls">
                        <select name="q[individual]">
                            <option>as an individual</option>
                            <option>as an organisation</option>
                        </select>
                    </div>
                </div>
                <div class="control-group">
                    <label>What kind of organisation is that?</label>
                    <div class="controls">
                        <select name="q[organisation]">
                             <option>private held</option>
                             <option>public held</option>
                             <option>government organisation</option>
                             <option>foundation</option>
                             <option>other</option>
                         </select>
                    </div>
                </div>
             </fieldset>

            <fieldset>
                <legend>Technical questions</legend>
                <div class="control-group">
                    <label>Where did you installed phpMyFAQ?</label>
                    <div class="controls">
                        <select name="q[server]">
                            <option>server run by a hosting company</option>
                            <option>public server run by you/your organisation</option>
                            <option>private server run by you/your organisation</option>
                            <option>Don't know</option>
                        </select>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Beyond our own nose</legend>
                <div class="control-group">
                    <label>Which PHP software do you also use?</label>
                    <div class="controls">
                        <input name="q[other]" />
                    </div>
                </div>
                <div class="control-group">
                    <label>Are you using other web technologies?</label>
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
                Additional to your input we're going to submit some information about your system setup for statstic
                purpose.
            </p>
            <p class="alert alert-info">
                We are not storing any personal information. You can see the data by clicking
                <a href="#" onclick="show('configliste'); return false;">here</a>.
            </p>

            <div id="configliste" class="hide">
                <a href="#" onclick="hide('configliste'); return false;">hide again</a>
                <dl>
<?php
$q = new PMF_Questionnaire_Data($configs);
$options = $q->get();
array_walk($options, 'data_printer');
echo '</dl><input type="hidden" name="systemdata" value="'.PMF_String::htmlspecialchars(serialize($q->get()), ENT_QUOTES).'" />';
?>
            </div>
            <p style="text-align: center;">
                <input class="btn-primary" type="submit" value="Click here to submit the data and finish the installation process" />
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
    // Remove 'setup.php' file
    if (@unlink(basename($_SERVER['SCRIPT_NAME']))) {
        print "<p class=\"alert alert-success\">The file <em>./install/setup.php</em> was deleted automatically.</p>\n";
    } else {
        print "<p class=\"alert alert-info\">Please delete the file <em>./install/setup.php</em> manually.</p>\n";
    }
    // Remove 'update.php' file
    if (@unlink(dirname($_SERVER["PATH_TRANSLATED"])."/update.php")) {
        print "<p class=\"alert alert-success\">The file <em>./install/update.php</em> was deleted automatically.</p>\n";
    } else {
        print "<p class=\"alert alert-info\">Please delete the file <em>./install/update.php</em> manually.</p>\n";
    }
    
    HTMLFooter();
}