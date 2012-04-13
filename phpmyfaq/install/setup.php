<?php
/**
 * The main phpMyFAQ Setup
 *
 * This script checks the complete environment, writes the database connection
 * parameters into the file config/database.php and the configuration into the database.
 * 
 * PHP Version 5.2
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

define('VERSION', '2.7.5');
define('APIVERSION', 1);
define('MINIMUM_PHP_VERSION', '5.2.3');
define('COPYRIGHT', '&copy; 2001-2012 <a href="http://www.phpmyfaq.de/">phpMyFAQ Team</a> | Follow us on <a href="http://twitter.com/phpMyFAQ">Twitter</a> | All rights reserved.');
define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));
define('IS_VALID_PHPMYFAQ', null);

if ((@ini_get('safe_mode') != 'On' || @ini_get('safe_mode') !== 1)) {
    set_time_limit(0);
}

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

    <title>phpMyFAQ <?php print VERSION; ?> Setup</title>

    <meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0;">
    <meta name="application-name" content="phpMyFAQ <?php print VERSION; ?>">
    <meta name="copyright" content="(c) 2001-2011 phpMyFAQ Team">
    
    <link rel="shortcut icon" href="../template/default/favicon.ico">
    <link rel="apple-touch-icon" href="../template/default/apple-touch-icon.png">
    <link rel="stylesheet" href="css/setup.css?v=1">
    <script type="text/javascript" src="../inc/js/jquery.min.js"></script>
    <script type="text/javascript">
    /*<![CDATA[*/
    // <!--
    function cssAddClass(ele, className) {
        if (typeof ele == 'string') {
            ele = document.getElementById(ele);
        }

        ele.className += ' ' + className;
    }

    function cssDelClass(ele, className) {
        if (typeof ele == 'string') {
            ele = document.getElementById(ele);
        }

        var regexp = new RegExp(
            '^'+className+'\\b\\s*|\\s*\\b'+className+'\\b', 'ig'
        );
        ele.className = ele.className.replace(regexp, '');
    }

    function select_database(field) {
        switch (field.value) {
            case 'sqlite':
            case 'sqlite3':
                cssDelClass('dbsqlite', 'collapsed');
                cssAddClass('dbdatafull', 'collapsed');
                break;
            default:
                cssAddClass('dbsqlite', 'collapsed');
                cssDelClass('dbdatafull', 'collapsed');
                break;
        }
    }
    // -->
    /*]]>*/
    </script>
</head>
<body>

<header id="header">
    <h1>
        <h1>phpMyFAQ <?php print VERSION; ?> Setup</h1>
    </h1>
</header>

<nav>
    <ul>
        <li><a target="_blank" href="http://www.phpmyfaq.de/documentation.php">Documentation</a></li>
        <li><a target="_blank" href="http://www.phpmyfaq.de/support.php">Support</a></li>
        <li><a target="_blank" href="http://forum.phpmyfaq.de/">Forums</a></li>
        <li><a target="_blank" href="http://faq.phpmyfaq.de/">FAQ</a></li>
    </ul>
</nav>

<div id="content">
    <div id="mainContent">
<?php

if (version_compare(PHP_VERSION, MINIMUM_PHP_VERSION, '<')) {
    printf('<p class="error">Sorry, but you need PHP %s or later!</p>', MINIMUM_PHP_VERSION);
    HTMLFooter();
    die();
}

if (!function_exists('date_default_timezone_set')) {
    print '<p class="error">Sorry, but setting a default timezone doesn\'t work in your environment!</p>';
    HTMLFooter();
    die();
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

// permission levels
$permLevels = array(
    'basic'  => 'Basic (no group support)',
    'medium' => 'Medium (with group support)'
);

$enabledExtensions = array(
    'gd',
    'json',
    'xmlwriter',
    'filter'
);

/**
 * Executes the uninstall set of queries
 *
 * @return void
 */
function db_uninstall()
{
    global $uninst, $db;

    while ($each_query = each($uninst)) {
        $db->query($each_query[1]);
    }
}

/**
 * Print out the HTML5 Footer
 *
 * @return void
 */
function HTMLFooter()
{
    printf('</div></div><footer><div><p id="copyrightnote">%s</p><div></footer></body></html>', COPYRIGHT);
}

/**
 * Removes the data.php and the dataldap.php if an installation failed
 *
 * @return void
 */
function cleanInstallation()
{
    // Remove 'database.php' file: no need of prompt anything to the user
    if (file_exists(PMF_ROOT_DIR.'/config/database.php')) {
        @unlink(PMF_ROOT_DIR.'/config/database.php');
    }
    // Remove 'dataldap.php' file: no need of prompt anything to the user
    if (file_exists(PMF_ROOT_DIR.'/config/ldap.php')) {
        @unlink(PMF_ROOT_DIR.'/config/ldap.php');
    }
}

$system = new PMF_System();

if (!$system->checkDatabase($supported_databases)) {

    print '<p class="error">No supported database detected! Please install one of the following' .
          ' database systems and enable the corresponding PHP extension:</p>';
    print '<ul>';
    foreach ($supported_databases as $database) {
        printf('    <li>%s</li>', $database[1]);
    }
    print '</ul>';
    HTMLFooter();
    die();
}

if (!$system->checkExtension($enabledExtensions)) {
    print "<p class=\"error\">The following extensions are missing! Please enable the PHP extension:</p>\n";
    print "<ul>\n";
    foreach ($system->getMissingExtensions() as $extension) {
        printf('    <li>ext/%s</li>', $extension);
    }
    print "</ul>\n";
    HTMLFooter();
    die();
}

if (!$system->checkphpMyFAQInstallation()) {
    print '<p class="error">It seems you\'re already running a version of phpMyFAQ. Please use the ' .
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
    print '<p class="error">The following directory/-ies could not be created or are not writable:</p><ul>';
    foreach ($faileddirs as $dir) {
        print "<li>$dir</li>\n";
    }
    print '</ul><p class="error">Please create it manually and/or change access to chmod 755 (or greater if ' .
          'necessary).</p>';
    HTMLFooter();
    die();
}

if (!isset($_POST["sql_server"]) && !isset($_POST["sql_user"]) && !isset($_POST["sql_db"])) {
?>

<p class="center">Your PHP version: <strong>PHP <?php print PHP_VERSION; ?></strong></p>

<?php
    if ((@ini_get('safe_mode') == 'On' || @ini_get('safe_mode') === 1)) {
        print '<p class="error">The PHP safe mode is enabled. You may have problems when phpMyFAQ writes in some ' .
              'directories.</p>';
    }
    if (!extension_loaded('gd')) {
        print '<p class="error">You don\'t have GD support enabled in your PHP installation. Please enable GD ' .
              'support in your php.ini file otherwise you can\'t use Captchas for spam protection.</p>';
    }
    if (!function_exists('imagettftext')) {
        print '<p class="error">You don\'t have Freetype support enabled in the GD extension of your PHP ' .
              'installation. Please enable Freetype support in GD extension otherwise the Captchas for spam ' .
              'protection will be quite easy to break.</p>';
    }
    if (!extension_loaded('curl') || !extension_loaded('openssl')) {
        print '<p class="error">You don\'t have cURL and/or OpenSSL support enabled in your PHP installation. ' .
              'Please enable cURL and/or OpenSSL support in your php.ini file otherwise you can\'t use the Twitter ' .
              ' support.</p>';
    }
?>
        <p class="hint">
            Did you already read the <a href="http://www.phpmyfaq.de/documentation.php">documentation</a> carefully
            before starting the phpMyFAQ setup?
        </p>

        <p class="hint">
            Your password will be saved using the
            <strong>
                <?php print PMF_ENCRYPTION_TYPE; ?>
            </strong>
            encryption. You can change this in <em>config/constants.php</em>.
        </p>

        <form action="setup.php" method="post">
        <fieldset>
        <legend>Please add your database connection setup information</legend>

            <p>
                <label>SQL server:</label>
                <select name="sql_type" id="sql_selector" size="1" onchange="select_database(this);">

<?php
    // check what extensions are loaded in PHP
    foreach ($supported_databases as $extension => $database) {
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
            </p>

            <div id="dbdatafull">
            <p>
                <label>SQL server host:</label>
                <input type="text" name="sql_server" title="Please enter the host of your SQL server here." />
            </p>
            <p>
                <label>SQL username:</label>
                <input type="text" name="sql_user" title="Please enter your SQL username here." />
            </p>
            <p>
                <label>SQL password:</label>
                <input name="sql_passwort" type="password" title="Please enter your SQL password here." />
            </p>
            <p>
                <label>SQL database:</label>
                <input type="text" name="sql_db" title="Please enter your SQL database name here." />
            </p>
            </div>

            <div id="dbsqlite" class="collapsed">
            <p>
                <label>SQLite database file:</label>
                <input type="text" name="sql_sqlitefile" value="<?php print dirname(dirname(__FILE__)); ?>"
                       title="Please enter the full path to your SQLite datafile which should be outside your documentation root." />
            </p>
            </div>

            <p>
                <label>Table prefix:</label>
                <input type="text" name="sqltblpre"
                       title="Please enter a table prefix here if you want to install more phpMyFAQ installations on one database." />
            </p>

        </fieldset>
            
        <script language="javascript" type="text/javascript">
        obj = document.getElementById("sql_selector");
        if (obj.options.length > obj.selectedIndex) {
            select_database(obj.options[obj.selectedIndex]);
        }
        </script>

        <?php if (extension_loaded('ldap')): ?>
        <fieldset>
        <legend>LDAP information</legend>
            <p>
                <label>Enable LDAP support?</label>
                <input class="checkbox" type="checkbox" name="ldap_enabled" value="yes" />
            </p>
            <p>
                <label>LDAP server host:</label>
                <input type="text" name="ldap_server" title="Please enter the host of your LDAP server here." />
            </p>
            <p>
                <label>LDAP server port:</label>
                <input type="text" name="ldap_port" value="389" title="Please enter the port of your LDAP server here." />
            </p>
            <p>
                <label>LDAP user DN:</label>
                <input type="text" name="ldap_user" title="Please enter your specified RDN username here." />
            </p>
            <p>
                <label>LDAP password:</label>
                <input name="ldap_password" type="password" title="Please enter your LDAP password here." />
            </p>
            <p>
                <label>LDAP base search DN:</label>
                <input type="text" name="ldap_base"
                       title="Please enter your distinguished name, e.g. 'cn=John Smith,ou=Accounts,o=My Company,c=US' here." />
            </p>
            <p>You can add additional LDAP configuration informations in the file config/constants_ldap.php.</p>

        </fieldset>

<?php endif; ?>

        <fieldset>
        <legend>phpMyFAQ information</legend>
            <p>
                <label>Default language:</label>
                <select name="language" size="1" title="Please select your default language.">
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
            </p>
            <p>
                <label>Permission level:</label>
                <select name="permLevel" size="1" title="Complexity of user and right administration. Basic: users may
                have user-rights. Medium: users may have user-rights; group administration; groups may have group-rights;
                user have group-rights via group-memberships." required="required">
                <?php foreach ($permLevels as $level => $desc) {
                    printf('    <option value="%s">%s</option>', $level, $desc);
                } ?>
                </select>
            </p>
            <p>
                <label>Admin's real name:</label>
                <input type="text" name="realname" title="Please enter your real name here." required="required" />
            </p>
            <p>
                <label>E-mail address:</label>
                <input type="email" name="email" title="Please enter your email adress here." required="required" />
            </p>
            <p>
                <label>Admin's username:</label>
                <input type="text" name="username" title="You don't have to do anything here." value="admin"
                       readonly="readonly" />
            </p>
            <p>
                <label>Admin's password:</label>
                <input type="password" name="password" title="Please enter your password for the admin area."
                       required="required" />
            </p>
            <p>
                <label>Retype password:</label>
                <input type="password" name="password_retyped" title="Please retype your password for checkup."
                       required="required" />
            </p>
            </fieldset>

            <p class="hint"><strong>Do not use it if you're already running a version of phpMyFAQ!</strong></p>
            <p>
                <input class="submit" type="submit" value="Click to install phpMyFAQ <?php print VERSION; ?>" />
            </p>
        </form>
<?php
    HTMLFooter();
} else {
    
    // Ckeck table prefix
    $sqltblpre = PMF_Filter::filterInput(INPUT_POST, 'sqltblpre', FILTER_SANITIZE_STRING, '');
    if (!defined('SQLPREFIX')) {
        define('SQLPREFIX', $sqltblpre);
    }

    // check database entries
    $sql_type = PMF_Filter::filterInput(INPUT_POST, 'sql_type', FILTER_SANITIZE_STRING);
    if (!is_null($sql_type)) {
        $sql_type = trim($sql_type);
        if (file_exists(PMF_ROOT_DIR . '/install/' . $sql_type . '.sql.php')) {
            require PMF_ROOT_DIR . '/install/' . $sql_type . '.sql.php';
        } else {
            print '<p class="error"><strong>Error:</strong> Invalid server type.</p>';
            HTMLFooter();
            die();
        }
    } else {
        print "<p class=\"error\"><strong>Error:</strong> There's no DB server input.</p>\n";
        HTMLFooter();
        die();
    }

    $sql_server = PMF_Filter::filterInput(INPUT_POST, 'sql_server', FILTER_SANITIZE_STRING);
    if (is_null($sql_server) && $sql_type != 'sqlite' &&  $sql_type != 'sqlite3') {
        print "<p class=\"error\"><strong>Error:</strong> There's no DB server input.</p>\n";
        HTMLFooter();
        die();
    }

    $sql_user = PMF_Filter::filterInput(INPUT_POST, 'sql_user', FILTER_SANITIZE_STRING);
    if (is_null($sql_user) && $sql_type != 'sqlite' &&  $sql_type != 'sqlite3') {
        print "<p class=\"error\"><strong>Error:</strong> There's no DB username input.</p>\n";
        HTMLFooter();
        die();
    }

    $sql_passwort = PMF_Filter::filterInput(INPUT_POST, 'sql_passwort', FILTER_SANITIZE_STRING);
    if (is_null($sql_passwort) && $sql_type == 'sqlite' &&  $sql_type == 'sqlite3') {
        // Password can be empty...
        $sql_passwort = '';
    }

    $sql_db = PMF_Filter::filterInput(INPUT_POST, 'sql_db', FILTER_SANITIZE_STRING);
    if (is_null($sql_db) && ($sql_type != 'sqlite' ||  $sql_type != 'sqlite3')) {
        print "<p class=\"error\"><strong>Error:</strong> There's no DB database input.</p>\n";
        HTMLFooter();
        die();
    }

    if ($sql_type == 'sqlite' ||  $sql_type == 'sqlite3') {
        $sql_sqllitefile = PMF_Filter::filterInput(INPUT_POST, 'sql_sqlitefile', FILTER_SANITIZE_STRING);
        if (!is_null($sql_sqllitefile)) {
            $sql_server = $sql_sqllitefile; // We're using $sql_server, too!
        } else {
            print "<p class=\"error\"><strong>Error:</strong> There's no SQLite database filename input.</p>\n";
            HTMLFooter();
            die();
        }
    }

    // check database connection
    require PMF_ROOT_DIR . "/inc/Db.php";
    require PMF_ROOT_DIR . "/inc/PMF_DB/Driver.php";
    $db = PMF_Db::dbSelect($sql_type);
    $db->connect($sql_server, $sql_user, $sql_passwort, $sql_db);
    if (!$db) {
        print "<p class=\"error\"><strong>DB Error:</strong> ".$db->error()."</p>\n";
        HTMLFooter();
        die();
    }

    // check LDAP if available
    $ldap_enabled = PMF_Filter::filterInput(INPUT_POST, 'ldap_enabled', FILTER_SANITIZE_STRING);
    if (extension_loaded('ldap') && !is_null($ldap_enabled)) {

        // check LDAP entries
        $ldap_server = PMF_Filter::filterInput(INPUT_POST, 'ldap_server', FILTER_SANITIZE_STRING);
        if (is_null($ldap_server)) {
            print "<p class=\"error\"><strong>Error:</strong> There's no LDAP server input.</p>\n";
            HTMLFooter();
            die();
        }
        
        $ldap_port = PMF_Filter::filterInput(INPUT_POST, 'ldap_port', FILTER_VALIDATE_INT);
        if (is_null($ldap_port)) {
            print "<p class=\"error\"><strong>Error:</strong> There's no LDAP port input.</p>\n";
            HTMLFooter();
            die();
        }
        
        $ldap_user = PMF_Filter::filterInput(INPUT_POST, 'ldap_user', FILTER_SANITIZE_STRING);
        if (is_null($ldap_user)) {
            print "<p class=\"error\"><strong>Error:</strong> There's no LDAP user DN input.</p>\n";
            HTMLFooter();
            die();
        }
        
        $ldap_password = PMF_Filter::filterInput(INPUT_POST, 'ldap_password', FILTER_SANITIZE_STRING);
        if (is_null($ldap_password)) {
            print "<p class=\"error\"><strong>Error:</strong> There's no LDAP password input.</p>\n";
            HTMLFooter();
            die();
        }
        
        $ldap_base = PMF_Filter::filterInput(INPUT_POST, 'ldap_base', FILTER_SANITIZE_STRING);
        if (is_null($ldap_base)) {
            print "<p class=\"error\"><strong>Error:</strong> There's no LDAP base search DN input.</p>\n";
            HTMLFooter();
            die();
        }

        // check LDAP connection
        require PMF_ROOT_DIR . "/inc/Ldap.php";
        $ldap = new PMF_Ldap($ldap_server, $ldap_port, $ldap_base, $ldap_user, $ldap_password);
        if (!$ldap) {
            print "<p class=\"error\"><strong>LDAP Error:</strong> ".$ldap->error()."</p>\n";
            HTMLFooter();
            die();
        }
    }

    // check user entries
    $password = PMF_Filter::filterInput(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    if (is_null($password)) {
        print '<p class="error"><strong>Error:</strong> There\'s no password for the administrator\'s account.' .
              ' Please set your password.</p>';
        HTMLFooter();
        die();
    }
    
    $password_retyped = PMF_Filter::filterInput(INPUT_POST, 'password_retyped', FILTER_SANITIZE_STRING);
    if (is_null($password_retyped)) {
        print '<p class="error"><strong>Error:</strong> There\'s no retyped password.' .
              ' Please set your retyped password.</p>';
        HTMLFooter();
        die();
    }
    
    if (strlen($password) <= 5 || strlen($password_retyped) <= 5) {
        print '<p class="error"><strong>Error:</strong> Your password and retyped password are too short.' .
              ' Please set your password and your retyped password with a minimum of 6 characters.</p>';
        HTMLFooter();
        die();
    }
    if ($password != $password_retyped) {
        print '<p class="error"><strong>Error:</strong> Your password and retyped password are not equal.' .
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
        "<?php\n\$DB[\"server\"] = '" . $sql_server . "';\n" .
        "\$DB[\"user\"] = '" . $sql_user . "';\n" .
        "\$DB[\"password\"] = '" . $sql_passwort . "';\n" .
        "\$DB[\"db\"] = '" . $sql_db . "';\n" .
        "\$DB[\"prefix\"] = '" . $sqltblpre . "';\n" .
        "\$DB[\"type\"] = '" . $sql_type . "';",
        LOCK_EX
    );
    
    if (!$ret) {
        print "<p class=\"error\"><strong>Error:</strong> Cannot write to ./config/database.php.</p>";
        HTMLFooter();
        cleanInstallation();
        die();
    }

    // check LDAP if available
    if (extension_loaded('ldap') && !is_null($ldap_enabled)) {
        $datafile = PMF_ROOT_DIR . '/config/ldap.php';
        $ret = file_put_contents(
            $datafile,
            "<?php\n\$PMF_LDAP[\"ldap_server\"] = '" . $ldap_server . "';\n" .
            "\$PMF_LDAP[\"ldap_port\"] = '" . $ldap_port . "';\n" .
            "\$PMF_LDAP[\"ldap_user\"] = '" . $ldap_user . "';\n" .
            "\$PMF_LDAP[\"ldap_password\"] = '" . $ldap_password . "';\n" .
            "\$PMF_LDAP[\"ldap_base\"] = '" . $ldap_base . "';",
            LOCK_EX
        );
        if (!$ret) {
            print "<p class=\"error\"><strong>Error:</strong> Cannot write to ./config/ldap.php.</p>";
            HTMLFooter();
            cleanInstallation();
            die();
        }
    }

    // connect to the database using inc/data.php
    require PMF_ROOT_DIR . '/config/database.php';
    $db = PMF_Db::dbSelect($sql_type);
    $db->connect($DB['server'], $DB['user'], $DB['password'], $DB['db']);
    if (!$db) {
        print "<p class=\"error\"><strong>DB Error:</strong> ".$db->error()."</p>\n";
        HTMLFooter();
        cleanInstallation();
        die();
    }

    require_once $sql_type . '.sql.php'; // CREATE TABLES
    require_once 'config.sql.php';       // INSERTs for configuration
    require_once 'stopwords.sql.php';    // INSERTs for stopwords

    print '<p>';

    // Erase any table before starting creating the required ones
    if ('sqlite' != $sql_type &&  $sql_type != 'sqlite3') {
        db_uninstall();
    }
    // Start creating the required tables
    $count = 0;
    while ($each_query = each($query)) {
        $result = @$db->query($each_query[1]);
        if (!$result) {
            print '<p class="error"><strong>Error:</strong> Please install your version of phpMyFAQ once again or send
            us a <a href=\"http://www.phpmyfaq.de\" target=\"_blank\">bug report</a>.</p>';
            printf('<p class="error"><strong>DB error:</strong> %s</p>', $db->error());
            printf('<code>%s</code>', htmlentities($each_query[1]));
            db_uninstall();
            cleanInstallation();
            HTMLFooter();
            die();
        }
        usleep(2500);
        $count++;
        if (!($count % 10)) {
            print '| ';
        }
    }

    // add admin account and rights
    $admin = new PMF_User();
    $admin->createUser('admin', $password, 1);
    $admin->setStatus('protected');
    $adminData = array(
        'display_name' => $realname,
        'email'        => $email);
    $admin->setUserData($adminData);
    $adminID = $admin->getUserId();
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
    );
    foreach ($rights as $right) {
        $rightID = $admin->perm->addRight($right);
        $admin->perm->grantUserRight($adminID, $rightID);
    }
    // Add anonymous user account
    $anonymous = new PMF_User();
    $anonymous->createUser('anonymous', null, -1);
    $anonymous->setStatus('protected');
    $anonymousData = array(
        'display_name' => 'Anonymous User',
        'email'        => null);
    $anonymous->setUserData($anonymousData);

    $oConf = PMF_Configuration::getInstance();
    $oConf->getAll();
    $configs = $oConf->config;

    $configs['spam.enableCaptchaCode'] = (extension_loaded('gd') ? 'true' : 'false');
    $configs['main.referenceURL']      = PMF_Link::getSystemUri('/install/setup.php');
    $configs['main.phpMyFAQToken']     = md5(uniqid(rand()));

    $oConf->update($configs);

    print '</p>';

?>
        <p class="success">
            All database tables were successfully created.<br />Congratulation! Everything seems to be okay.
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
            cssAddClass(item, 'collapsed');
        }

        function show(item) {
            cssDelClass(item, 'collapsed');
        }
        //]]>
        </script>
        <iframe onload="iframeUpdated();" name="questionaireResult" style="display:none"></iframe>
        <form action="http://www.phpmyfaq.de/stats/getstatdata.php" method="post" target="questionaireResult"
              id="questionnaireForm">

            <p>
                For further development we would like to get some feedback from our users.<br />
                Therefore we'd ask you to take a few minutes of your time to answer a few questions.
            </p>
            <p>
                If you don't want to participate in the survey, you can directly visit
                <a href="../index.php">your version of phpMyFAQ</a> or login into your
                <a href="../admin/index.php">admin section</a>.
            </p>

            <fieldset>
                <legend>General questions</legend>
                
                <p>
                    <label>How do you act like?</label>
                    <select name="q[individual]">
                        <option>as an individual</option>
                        <option>as an organisation</option>
                    </select>
                </p>
                <p>
                    <label>What kind of organisation is that?</label>
                    <select name="q[organisation]">
                         <option>private held</option>
                         <option>public held</option>
                         <option>government organisation</option>
                         <option>foundation</option>
                         <option>other</option>
                     </select>
                </p>
             </fieldset>

            <fieldset>
                <legend>Technical questions</legend>
                <p>
                    <label>Where did you installed phpMyFAQ?</label>
                    <select name="q[server]">
                        <option>server run by a hosting company</option>
                        <option>public server run by you/your organisation</option>
                        <option>private server run by you/your organisation</option>
                        <option>Don't know</option>
                    </select>
                </p>
            </fieldset>

            <fieldset>
                <legend>Beyond our own nose</legend>
                <p>
                    <label>Which PHP software do you also use?</label>
                    <input name="q[other]" />
                </p>
                <p>
                    <label>Are you using other web technologies?</label>
                    <input type="checkbox" name="q[other][]" value="ASP" />ASP
                    <input type="checkbox" name="q[other][]" value="ASP.NET" />ASP.NET
                    <input type="checkbox" name="q[other][]" value="jsp" />JAVA JSP
                    <input type="checkbox" name="q[other][]" value="perl" />Perl
                    <input type="checkbox" name="q[other][]" value="ruby" />Ruby / Ruby on Rails
                    <input type="checkbox" name="q[other][]" value="python" />Python
                    <input type="checkbox" name="q[other][]" value="clojure" />Clojure
             </fieldset>

            <p class="hint">
                Additional to your input we're going to submit some information about your system setup for statstic
                purpose.
            </p>
            <p class="hint">
                We are not storing any personal information. You can see the data by clicking
                <a href="#" onclick="show('configliste'); return false;">here</a>.
            </p>

            <div id="configliste" class="collapsed">
                <a href="#" onclick="hide('configliste'); return false;">hide again</a>
                <dl>
<?php
$q = new PMF_Questionnaire_Data($configs);
$options = $q->get();
array_walk($options, 'data_printer');
echo '</dl><input type="hidden" name="systemdata" value="'.PMF_String::htmlspecialchars(serialize($q->get()), ENT_QUOTES).'" />';
?>
            </div>
            <p>
                <input class="submit" type="submit" value="Click here to submit the data and finish the installation process" />
            </p>
        </form>
        <div id="questionnaireThanks" style="display:none;">
            <p class="success"><b>Thank you for giving your feedback!</b></p>
            <p>You can visit <a href="../index.php">your version of phpMyFAQ</a> or</p>
            <p>login into your <a href="../admin/index.php">admin section</a>.</p>
        </div>
<?php

    // Remove 'scripts' folder: no need of prompt anything to the user
    if (file_exists(PMF_ROOT_DIR."/scripts") && is_dir(PMF_ROOT_DIR."/scripts")) {
        @rmdir(PMF_ROOT_DIR."/scripts");
    }
    // Remove 'phpmyfaq.spec' file: no need of prompt anything to the user
    if (file_exists(PMF_ROOT_DIR."/phpmyfaq.spec")) {
        @unlink(PMF_ROOT_DIR."/phpmyfaq.spec");
    }
    
    // Remove 'setup.php' file
    if (@unlink(basename($_SERVER['SCRIPT_NAME']))) {
        print "<p class=\"success\">The file <em>./install/setup.php</em> was deleted automatically.</p>\n";
    } else {
        print "<p class=\"hint\">Please delete the file <em>./install/setup.php</em> manually.</p>\n";
    }
    // Remove 'update.php' file
    if (@unlink(dirname($_SERVER["PATH_TRANSLATED"])."/update.php")) {
        print "<p class=\"success\">The file <em>./install/update.php</em> was deleted automatically.</p>\n";
    } else {
        print "<p class=\"hint\">Please delete the file <em>./install/update.php</em> manually.</p>\n";
    }
    
    HTMLFooter();
}