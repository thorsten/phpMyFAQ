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
 * @copyright 2002-2012 phpMyFAQ Team
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

require PMF_ROOT_DIR . '/config/constants.php';
require PMF_ROOT_DIR . '/inc/Autoloader.php';
require PMF_ROOT_DIR . '/inc/functions.php';
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

if (version_compare(PHP_VERSION, PMF_System::VERSION_MINIMUM_PHP, '<')) {
    printf('<p class="alert alert-error">Sorry, but you need PHP %s or later!</p>', PMF_System::VERSION_MINIMUM_PHP);
    PMF_System::renderFooter();
}

//
// Initalizing static string wrapper
//
PMF_String::init('en');

$query = $uninst = array();

$system        = new PMF_System();
$instanceSetup = new PMF_Instance_Setup();
$instanceSetup->setRootDir(PMF_ROOT_DIR);

if (! function_exists('date_default_timezone_set')) {
    echo '<p class="alert alert-error">Sorry, but setting a default timezone doesn\'t work in your environment!</p>';
    PMF_System::renderFooter();
}

if (! $system->checkDatabase()) {
    echo '<p class="alert alert-error">No supported database detected! Please install one of the following' .
          ' database systems and enable the corresponding PHP extension in php.ini:</p>';
    echo '<ul>';
    foreach ($system->getSupportedDatabases() as $database) {
        printf('    <li>%s</li>', $database[1]);
    }
    echo '</ul>';
    PMF_System::renderFooter();
}

if (! $system->checkRequiredExtensions()) {
    echo '<p class="alert alert-error">The following extensions are missing! Please enable the PHP extension(s) in ' .
          'php.ini.</p>';
    echo '<ul>';
    foreach ($system->getMissingExtensions() as $extension) {
        printf('    <li>ext/%s</li>', $extension);
    }
    echo '</ul>';
    PMF_System::renderFooter();
}

if (! $system->checkphpMyFAQInstallation()) {
    echo '<p class="alert alert-error">It seems you\'re already running a version of phpMyFAQ. Please use the ' .
          '<a href="update.php">update script</a>.</p>';
    PMF_System::renderFooter();
    die();
}

$dirs       = array('/attachments', '/config', '/data', '/images');
$failedDirs = $instanceSetup->checkDirs($dirs);
$numDirs    = sizeof($failedDirs);

if (1 <= $numDirs) {
    printf(
        '<p class="alert alert-error">The following %s could not be created or %s not writable:</p><ul>',
        (1 < $numDirs) ? 'directories' : 'directory',
        (1 < $numDirs) ? 'are' : 'is'
    );
    foreach ($failedDirs as $dir) {
        echo "<li>$dir</li>\n";
    }
    printf(
        '</ul><p class="alert alert-error">Please create %s manually and/or change access to chmod 755 (or ' .
          'greater if necessary).</p>',
        (1 < $numDirs) ? 'them' : 'it'
    );
    PMF_System::renderFooter();
}

if (!isset($_POST["sql_server"]) && !isset($_POST["sql_user"]) && !isset($_POST["sql_db"])) {

    if ((@ini_get('safe_mode') == 'On' || @ini_get('safe_mode') === 1)) {
        echo '<p class="alert alert-error">The PHP safe mode is enabled. You may have problems when phpMyFAQ tries to write ' .
              ' in some directories.</p>';
    }
    if (! extension_loaded('gd')) {
        echo '<p class="alert alert-error">You don\'t have GD support enabled in your PHP installation. Please enable GD ' .
              'support in your php.ini file otherwise you can\'t use Captchas for spam protection.</p>';
    }
    if (! function_exists('imagettftext')) {
        echo '<p class="alert alert-error">You don\'t have Freetype support enabled in the GD extension of your PHP ' .
              'installation. Please enable Freetype support in GD extension otherwise the Captchas for spam ' .
              'protection will be quite easy to break.</p>';
    }
    if (! extension_loaded('curl') || ! extension_loaded('openssl')) {
        echo '<p class="alert alert-error">You don\'t have cURL and/or OpenSSL support enabled in your PHP installation. ' .
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
                        <label class="control-label" for="sql_type">Database server:</label>
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

            <input class="btn-primary btn-large" type="submit"
                   value="Click to install phpMyFAQ <?php echo PMF_System::getVersion(); ?>" />
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

    $dbSetup = array();

    // Check table prefix
    $dbSetup['dbPrefix'] = $sqltblpre = PMF_Filter::filterInput(INPUT_POST, 'sqltblpre', FILTER_SANITIZE_STRING, '');
    if ('' !== $dbSetup['dbPrefix']) {
        PMF_Db::setTablePrefix($dbSetup['dbPrefix']);
    }

    // Check database entries
    $dbSetup['dbType'] = PMF_Filter::filterInput(INPUT_POST, 'sql_type', FILTER_SANITIZE_STRING);
    if (!is_null($dbSetup['dbType'])) {
        $dbSetup['dbType'] = trim($dbSetup['dbType']);
        if (! file_exists(PMF_ROOT_DIR . '/install/' . $dbSetup['dbType'] . '.sql.php')) {
            printf(
                '<p class="alert alert-error"><strong>Error:</strong> Invalid server type: %s</p>',
                $dbSetup['dbType']
            );
            PMF_System::renderFooter(true);
        }
    } else {
        echo "<p class=\"alert alert-error\"><strong>Error:</strong> Please select a database type.</p>\n";
        PMF_System::renderFooter(true);
    }

    $dbSetup['dbServer'] = PMF_Filter::filterInput(INPUT_POST, 'sql_server', FILTER_SANITIZE_STRING);
    if (is_null($dbSetup['dbServer']) && ! PMF_System::isSqlite($dbSetup['dbType'])) {
        echo "<p class=\"alert alert-error\"><strong>Error:</strong> Please add a database server.</p>\n";
        PMF_System::renderFooter(true);
    }

    $dbSetup['dbUser'] = PMF_Filter::filterInput(INPUT_POST, 'sql_user', FILTER_SANITIZE_STRING);
    if (is_null($dbSetup['dbUser']) && ! PMF_System::isSqlite($dbSetup['dbType'])) {
        echo "<p class=\"alert alert-error\"><strong>Error:</strong> Please add a database username.</p>\n";
        PMF_System::renderFooter(true);
    }

    $dbSetup['dbPassword'] = PMF_Filter::filterInput(INPUT_POST, 'sql_passwort', FILTER_UNSAFE_RAW);
    if (is_null($dbSetup['dbPassword']) && ! PMF_System::isSqlite($dbSetup['dbType'])) {
        // Password can be empty...
        $dbSetup['dbPassword'] = '';
    }

    $dbSetup['dbDatabaseName'] = PMF_Filter::filterInput(INPUT_POST, 'sql_db', FILTER_SANITIZE_STRING);
    if (is_null($dbSetup['dbDatabaseName']) && ! PMF_System::isSqlite($dbSetup['dbType'])) {
        echo "<p class=\"alert alert-error\"><strong>Error:</strong> Please add a database name.</p>\n";
        PMF_System::renderFooter(true);
    }

    if (PMF_System::isSqlite($dbSetup['dbType'])) {
        $dbSetup['dbServer'] = PMF_Filter::filterInput(INPUT_POST, 'sql_sqlitefile', FILTER_SANITIZE_STRING);
        if (is_null($dbSetup['dbServer'])) {
            echo "<p class=\"alert alert-error\"><strong>Error:</strong> Please add a SQLite database filename.</p>\n";
            PMF_System::renderFooter(true);
        }
    }

    // check database connection
    require PMF_ROOT_DIR . "/inc/DB/Driver.php";
    PMF_Db::setTablePrefix($dbSetup['dbPrefix']);
    $db = PMF_Db::factory($dbSetup['dbType']);
    $db->connect($dbSetup['dbServer'], $dbSetup['dbUser'], $dbSetup['dbPassword'], $dbSetup['dbDatabaseName']);
    if (!$db) {
        printf("<p class=\"alert alert-error\"><strong>DB Error:</strong> %s</p>\n", $db->error());
        PMF_System::renderFooter(true);
    }

    $configuration = new PMF_Configuration($db);

    // check LDAP if available
    $ldapEnabled = PMF_Filter::filterInput(INPUT_POST, 'ldap_enabled', FILTER_SANITIZE_STRING);
    if (extension_loaded('ldap') && !is_null($ldapEnabled)) {

        $ldapSetup = array();

        // check LDAP entries
        $ldapSetup['ldapServer'] = PMF_Filter::filterInput(INPUT_POST, 'ldap_server', FILTER_SANITIZE_STRING);
        if (is_null($ldapSetup['ldapServer'])) {
            echo "<p class=\"alert alert-error\"><strong>Error:</strong> Please add a LDAP server.</p>\n";
            PMF_System::renderFooter(true);
        }
        
        $ldapSetup['ldapPort'] = PMF_Filter::filterInput(INPUT_POST, 'ldap_port', FILTER_VALIDATE_INT);
        if (is_null($ldapSetup['ldapPort'])) {
            echo "<p class=\"alert alert-error\"><strong>Error:</strong> Please add a LDAP port.</p>\n";
            PMF_System::renderFooter(true);
        }

        $ldapSetup['ldapBase'] = PMF_Filter::filterInput(INPUT_POST, 'ldap_base', FILTER_SANITIZE_STRING);
        if (is_null($ldapSetup['ldapBase'])) {
            echo "<p class=\"alert alert-error\"><strong>Error:</strong> Please add a LDAP base search DN.</p>\n";
            PMF_System::renderFooter(true);
        }

        // LDAP User and LDAP password are optional
        $ldapSetup['ldapUser']     = PMF_Filter::filterInput(INPUT_POST, 'ldap_user', FILTER_SANITIZE_STRING, '');
        $ldapSetup['ldapPassword'] = PMF_Filter::filterInput(INPUT_POST, 'ldap_password', FILTER_SANITIZE_STRING, '');

        // check LDAP connection
        require PMF_ROOT_DIR . "/inc/Ldap.php";
        $ldap = new PMF_Ldap($configuration);
        $ldap->connect(
            $ldapSetup['ldapServer'],
            $ldapSetup['ldapPort'],
            $ldapSetup['ldapBase'],
            $ldapSetup['ldapUser'],
            $ldapSetup['ldapPassword']
        );
        if (!$ldap) {
            echo "<p class=\"alert alert-error\"><strong>LDAP Error:</strong> " . $ldap->error() . "</p>\n";
            PMF_System::renderFooter(true);
        }
    }

    // check loginname
    $loginname = PMF_Filter::filterInput(INPUT_POST, 'loginname', FILTER_SANITIZE_STRING);
    if (is_null($loginname)) {
        echo '<p class="alert alert-error"><strong>Error:</strong> Please add a loginname for your account.</p>';
        PMF_System::renderFooter(true);
    }

    // check user entries
    $password = PMF_Filter::filterInput(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    if (is_null($password)) {
        echo '<p class="alert alert-error"><strong>Error:</strong> Please add a password for the your account.</p>';
        PMF_System::renderFooter(true);
    }
    
    $password_retyped = PMF_Filter::filterInput(INPUT_POST, 'password_retyped', FILTER_SANITIZE_STRING);
    if (is_null($password_retyped)) {
        echo '<p class="alert alert-error"><strong>Error:</strong> Please add a retyped password.</p>';
        PMF_System::renderFooter(true);
    }
    
    if (strlen($password) <= 5 || strlen($password_retyped) <= 5) {
        echo '<p class="alert alert-error"><strong>Error:</strong> Your password and retyped password are too short.' .
              ' Please set your password and your retyped password with a minimum of 6 characters.</p>';
        PMF_System::renderFooter(true);
    }
    if ($password != $password_retyped) {
        echo '<p class="alert alert-error"><strong>Error:</strong> Your password and retyped password are not equal.' .
              ' Please check your password and your retyped password.</p>';
        PMF_System::renderFooter(true);
    }

    $language  = PMF_Filter::filterInput(INPUT_POST, 'language', FILTER_SANITIZE_STRING, 'en');
    $realname  = PMF_Filter::filterInput(INPUT_POST, 'realname', FILTER_SANITIZE_STRING, '');
    $email     = PMF_Filter::filterInput(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL, '');
    $permLevel = PMF_Filter::filterInput(INPUT_POST, 'permLevel', FILTER_SANITIZE_STRING, 'basic');
    
    // Write the DB variables in database.php
    if (! $instanceSetup->createDatabaseFile($dbSetup)) {
        echo "<p class=\"alert alert-error\"><strong>Error:</strong> Setup cannot write to ./config/database.php.</p>";
        $system->cleanInstallation();
        PMF_System::renderFooter(true);
    }

    // check LDAP if available
    if (extension_loaded('ldap') && !is_null($ldapEnabled)) {
        if (! $instanceSetup->createLdapFile($ldapSetup)) {
            echo "<p class=\"alert alert-error\"><strong>Error:</strong> Setup cannot write to ./config/ldap.php.</p>";
            $system->cleanInstallation();
            PMF_System::renderFooter(true);
        }
    }

    // connect to the database using config/database.php
    require PMF_ROOT_DIR . '/config/database.php';
    $db = PMF_Db::factory($dbSetup['dbType']);
    $db->connect($DB['server'], $DB['user'], $DB['password'], $DB['db']);
    if (!$db) {
        echo "<p class=\"alert alert-error\"><strong>DB Error:</strong> ".$db->error()."</p>\n";
        $system->cleanInstallation();
        PMF_System::renderFooter(true);
    }

    require $dbSetup['dbType'] . '.sql.php'; // CREATE TABLES
    require 'stopwords.sql.php';  // INSERTs for stopwords

    $system->setDatabase($db);

    echo '<p>';

    // Erase any table before starting creating the required ones
    if (! PMF_System::isSqlite($dbSetup['dbType'])) {
        $system->dropTables($uninst);
    }
    
    // Start creating the required tables
    $count = 0;
    foreach ($query as $executeQuery) {
        $result = @$db->query($executeQuery);
        if (!$result) {
            echo '<p class="alert alert-error"><strong>Error:</strong> Please install your version of phpMyFAQ once again or send
            us a <a href=\"http://www.phpmyfaq.de\" target=\"_blank\">bug report</a>.</p>';
            printf('<p class="alert alert-error"><strong>DB error:</strong> %s</p>', $db->error());
            printf('<code>%s</code>', htmlentities($executeQuery));
            $system->dropTables($uninst);
            $system->cleanInstallation();
            PMF_System::renderFooter(true);
        }
        usleep(2500);
        $count++;
        if (!($count % 10)) {
            echo '| ';
        }
    }

    $link = new PMF_Link(null, $configuration);

    // add main configuration, add personal settings
    include 'configurationdata.php';
    $mainConfig['main.metaPublisher']      = $realname;
    $mainConfig['main.administrationMail'] = $email;
    $mainConfig['main.language']           = $language;
    $mainConfig['security.permLevel']      = $permLevel;

    foreach ($mainConfig as $name => $value) {
        $configuration->add($name, $value);
    }

    $configuration->update(array('main.referenceURL' => $link->getSystemUri('/install/setup.php')));
    $configuration->add('security.salt', md5($link->getSystemUri('/install/setup.php')));

    // add admin account and rights
    $admin = new PMF_User($configuration);
    if (! $admin->createUser($loginname, $password, 1)) {
        echo "<p class=\"alert alert-error\"><strong>Fatal installation error:</strong> " .
             "Couldn't create the admin user.</p>\n";
        $system->cleanInstallation();
        PMF_System::renderFooter(true);
    }
    $admin->setStatus('protected');
    $adminData = array(
        'display_name' => $realname,
        'email'        => $email
    );
    $admin->setUserData($adminData);

    // add default rights
    include 'rightdata.php';
    foreach ($mainRights as $right) {
        $admin->perm->grantUserRight(1, $admin->perm->addRight($right));
    }
    
    // Add anonymous user account
    $instanceSetup->createAnonymousUser($configuration);

    // Add master instance
    $instanceData = array(
        'url'      => $link->getSystemUri($_SERVER['SCRIPT_NAME']),
        'instance' => $link->getSystemRelativeUri('install/setup.php'),
        'comment'  => 'phpMyFAQ ' . PMF_System::getVersion()
    );
    $faqInstance = new PMF_Instance($configuration);
    $faqInstance->addInstance($instanceData);

    $faqInstanceMaster = new PMF_Instance_Master($configuration);
    $faqInstanceMaster->createMaster($faqInstance);

    echo '</p>';

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
                    <label class="control-label">How do you act like?</label>
                        <div class="controls">
                        <select name="q[individual]">
                            <option>as an individual</option>
                            <option>as an organisation</option>
                        </select>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label">What kind of organisation is that?</label>
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
                    <label class="control-label">Where did you installed phpMyFAQ?</label>
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
$q = new PMF_Questionnaire_Data($mainConfig);
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
        echo "<p class=\"alert alert-success\">The file <em>./install/setup.php</em> was deleted automatically.</p>\n";
    } else {
        echo "<p class=\"alert alert-error\">Please delete the file <em>./install/setup.php</em> manually.</p>\n";
    }
    // Remove 'update.php' file
    if (@unlink(dirname($_SERVER['PATH_TRANSLATED']) . '/update.php')) {
        echo "<p class=\"alert alert-success\">The file <em>./install/update.php</em> was deleted automatically.</p>\n";
    } else {
        echo "<p class=\"alert alert-error\">Please delete the file <em>./install/update.php</em> manually.</p>\n";
    }
    
    PMF_System::renderFooter();
}