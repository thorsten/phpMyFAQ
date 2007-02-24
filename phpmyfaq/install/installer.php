<?php
/**
 * $Id: installer.php,v 1.87 2007-02-24 07:21:44 thorstenr Exp $
 *
 * The main phpMyFAQ Installer
 *
 * This script tests the complete environment, writes the database connection
 * parameters into the file data.php and the configuration into the database.
 *
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author      Tom Rochester <tom.rochester@gmail.com>
 * @author      Johannes Schlueter <johannes@php.net>
 * @since       2002-08-20
 * @copyright   (c) 2001-2007 phpMyFAQ Team
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
 */

define('VERSION', '2.0.0-beta');
define('COPYRIGHT', '&copy; 2001-2007 <a href="http://www.phpmyfaq.de/">phpMyFAQ Team</a> | All rights reserved.');
define('SAFEMODE', @ini_get('safe_mode'));
define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));
require_once(PMF_ROOT_DIR.'/inc/constants.php');
require_once(PMF_ROOT_DIR.'/inc/functions.php');

$query  = array();
$uninst = array();

// permission levels
$permLevels = array(
    'basic'     => 'Basic (no group support)',
    'medium'    => 'Medium (with group support)'
);

/**
 * Lookup for installed database extensions
 * If the first supported extension is enabled, return true.
 *
 * @param   array   $supported_databases
 * @return  boolean
 * @access  public
 * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
 */
function db_check($supported_databases)
{
    foreach ($supported_databases as $extension => $database) {
        if (extension_loaded($extension)) {
            return true;
        }
    }
    return false;
}

/**
 * Checks for an installed phpMyFAQ version
 *
 * @return  boolean
 * @access  public
 * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
 */
function phpmyfaq_check()
{
    if (@include('../inc/data.php')) {
        include('../inc/data.php');
        // check for version 1.4.x
        if ((isset($DB["server"]) && $DB["server"] != "") || (isset($DB["user"]) && $DB["user"] != "") || (isset($DB["password"]) && $DB["password"] != "") || (isset($DB["db"]) && $DB["db"] != "") || (isset($DB["prefix"]) && $DB["prefix"] != "")) {
            return false;
        }
        // check for version 1.5.x
        if ((isset($DB["server"]) && $DB["server"] != "") || (isset($DB["user"]) && $DB["user"] != "") || (isset($DB["password"]) && $DB["password"] != "") || (isset($DB["db"]) && $DB["db"] != "") || (isset($DB["prefix"]) && $DB["prefix"] != "")  || (isset($DB["type"]) && $DB["type"] != "")) {
            return false;
        }
        return true;
    }
    return true;
}

/**
 * Executes the uninstall queries
 *
 * @return  void
 * @access  public
 * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
 */
function uninstall()
{
    global $uninst, $db;

    while ($each_query = each($uninst)) {
        $db->query($each_query[1]);
    }
}

/**
 * Print out the XHTML Footer
 *
 * @return  void
 * @access  public
 * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
 */
function HTMLFooter()
{
    print '<p class="center">'.COPYRIGHT.'</p></body></html>';
}

/**
 * Removes the data.php and the dataldap.php if an installation failed
 *
 * @return  void
 * @access  public
 * @since   2005-12-18
 * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
 */
function cleanInstallation()
{
    // Remove 'data.php' file: no need of prompt anything to the user
    if (file_exists(PMF_ROOT_DIR.'/inc/data.php')) {
        @unlink(PMF_ROOT_DIR.'/inc/data.php');
    }
    // Remove 'dataldap.php' file: no need of prompt anything to the user
    if (file_exists(PMF_ROOT_DIR.'/inc/dataldap.php')) {
        @unlink(PMF_ROOT_DIR.'/inc/dataldap.php');
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title>phpMyFAQ <?php print VERSION; ?> Installation</title>
    <meta http-equiv="Content-Type" content="application/xhtml+xml; charset=iso-8859-1" />
    <link rel="shortcut icon" href="../template/favicon.ico" type="image/x-icon" />
    <link rel="icon" href="../template/favicon.ico" type="image/x-icon" />
    <script language="javascript" type="text/javascript">
    /*<![CDATA[*/
    <!--
    function select_database(field) {
        switch (field.value) {
            case 'sqlite':
                document.getElementById('dbsqlite').style.display='inline';
                document.getElementById('dbdatafull').style.display='none';
                break;
            default:
                document.getElementById('dbsqlite').style.display='none';
                document.getElementById('dbdatafull').style.display='inline';
                break;
        }
    }
    // -->
    /*]]>*/
    </script>
    <style media="screen" type="text/css">
    /*<![CDATA[*/
    <!--
    body {
        margin: 10px;
        padding: 0px;
        font-size: 12px;
        font-family: "Bitstream Vera Sans", "Trebuchet MS", Geneva, Verdana, Arial, Helvetica, sans-serif;
        background: #ffffff;
        color: #000000;
    }
    #header {
        margin: auto;
        padding: 25px;
        background: #E1F0A6;
        color: #234361;
        font-size: 36px;
        font-weight: bold;
        text-align: center;
        border-right: 3px solid silver;
        border-bottom: 3px solid silver;
        -moz-border-radius: 20px 20px 20px 20px;
        border-radius: 20px 20px 20px 20px;
    }
    #header h1 {
        font-family: "Trebuchet MS", Geneva, Verdana, Arial, Helvetica, sans-serif;
        margin: auto;
        text-align: center;
    }
    .center {
        text-align: center;
    }
    fieldset.installation {
        margin: auto;
        border: 1px solid black;
        width: 550px;
        margin-bottom: 10px;
        clear: both;
    }
    legend.installation {
        border: 1px solid black;
        background-color: #D5EDFF;
        padding: 4px 8px 4px 8px;
        font-size: 14px;
        font-weight: bold;
        -moz-border-radius: 5px 5px 5px 5px;
        border-radius: 5px 5px 5px 5px;
    }
    .input {
        width: 200px;
        background-color: #f5f5f5;
        border: 1px solid black;
        margin-bottom: 8px;
    }
    .checkbox {
        background-color: #f5f5f5;
        border: 1px solid black;
        margin-bottom: 8px;
    }
    label.left {
        width: 200px;
        float: left;
        text-align: right;
        padding-right: 10px;
        line-height: 20px;
    }
    #admin {
        line-height: 20px;
        font-weight: bold;
        margin-bottom: 10px;
    }
    .button {
        background-color: #89AC15;
        border: 3px solid #000000;
        color: #ffffff;
        font-weight: bold;
        font-size: 24px;
        padding: 10px 30px 10px 30px;
        -moz-border-radius: 10px 10px 10px 10px;
        border-radius: 10px 10px 10px 10px;
    }
    .error {
        margin: auto;
        margin-top: 20px;
        width: 600px;
        text-align: center;
        padding: 10px;
        line-height: 20px;
        background-color: #f5f5f5;
        border: 1px solid black;
    }
    -->
    /*]]>*/
    </style>
</head>
<body>

<h1 id="header">phpMyFAQ <?php print VERSION; ?> Installation</h1>

<?php

if (version_compare(PHP_VERSION, '4.3.3', '<')) {
    print "<p class=\"center\">You need PHP 4.3.3 or later!</p>\n";
    HTMLFooter();
    die();
}

if (db_check($supported_databases) == false) {
    print "<p class=\"center\">No supported database found! Please install one of the following database systems and enable the m     corresponding PHP extension:</p>\n";
    print "<ul>\n";
    foreach ($supported_databases as $database) {
        printf('    <li>%s</li>', $database[1]);
    }
    print "</ul>\n";
    HTMLFooter();
    die();
}

if (!phpmyfaq_check()) {
    print '<p class="center">It seems you\'re already running a version of phpMyFAQ.<br />Please use the <a href="update.php">update script</a>.</p>';
    HTMLFooter();
    die();
}

$dirs = array('/attachments', '/data', '/images/Image', '/inc', '/pdf', '/xml',);
$faileddirs = array();
foreach ($dirs as $dir) {
    if (!@is_dir(PMF_ROOT_DIR.$dir)) {
        if (!@mkdir (PMF_ROOT_DIR.$dir, 0755)) {
            $faileddirs[] = $dir;
        }
    } else if (!@is_writable(PMF_ROOT_DIR.$dir)) {
        $faileddirs[] = $dir;
    } else {
        @copy("index.html", PMF_ROOT_DIR.$dir.'/index.html');
    }
}

if (sizeof($faileddirs)) {
    print '<p class="center">The following directory/-ies could not be created or are not writable:</p><ul>';
    foreach ($faileddirs as $dir) {
        print "<li>$dir</li>\n";
    }
    print '</ul><p class="center">Please create it manually and/or change access to chmod 755 (or greater if necessary).</p>';
    HTMLFooter();
    die();
}

if (!isset($_POST["sql_server"]) && !isset($_POST["sql_user"]) && !isset($_POST["sql_db"])) {
?>

<p class="center">Your PHP version: <strong>PHP <?php print PHP_VERSION; ?></strong></p>

<?php
    if (SAFEMODE == 1) {
        print '<p class="center">The PHP safe mode is enabled. You may have problems when phpMyFAQ writes in some directories.</p>';
    }
    if (!extension_loaded('gd')) {
        print '<p class="center">You don\'t have GD support enabled in your PHP installation. Please enabled GD support in your php.ini file otherwise you can\'t use Captchas for spam protection.</p>';
    }
    if (!function_exists('imagettftext')) {
        print '<p class="center">You don\'t have Freetype support enabled in the GD extension of your PHP installation. Please enabled Freetype support in GD extension otherwise the Captchas for spam protection are quite easy to break.</p>';
    }
?>
<p class="center">You should read the <a href="../docs/documentation.en.html">documentation</a> carefully before installing phpMyFAQ.</p>

<form action="installer.php" method="post">
<fieldset class="installation">
<legend class="installation">Database information</legend>

    <label class="left">SQL server:</label>
    <select class="input" name="sql_type" size="1" onchange="select_database(this);">
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
    </select><br />
    
    <div id="dbdatafull">
    <label class="left">SQL server host:</label>
    <input class="input" type="text" name="sql_server" title="Please enter the host of your SQL server here." /><br />

    <label class="left">SQL username:</label>
    <input class="input" type="text" name="sql_user" title="Please enter your SQL username here." /><br />

    <label class="left">SQL password:</label>
    <input class="input" name="sql_passwort" type="password" title="Please enter your SQL password here." /><br />

    <label class="left">SQL database:</label>
    <input class="input" type="text" name="sql_db" title="Please enter your SQL database name here." /><br />
    </div>
    
    <div id="dbsqlite" style="display: none;">
    <label class="left">SQLite database file:</label>
    <input class="input" type="text" name="sql_sqlitefile" value="<?php print dirname(dirname(__FILE__)); ?>" title="Please enter the full path to your SQLite datafile which should be outside your documentation root." /><br />
    </div>

    <label class="left">Table prefix:</label>
    <input class="input" type="text" name="sqltblpre" title="Please enter a table prefix here if you want to install more phpMyFAQ installations on one database." />

</fieldset>
<br />
<?php
    if (extension_loaded('ldap')) {
?>
<fieldset class="installation">
<legend class="installation">LDAP information</legend>
    
    <label class="left">Do you want to use LDAP?</label>
    <input class="checkbox" type="checkbox" name="ldap_enabled" value="yes" /><br />
    
    <label class="left">LDAP server host:</label>
    <input class="input" type="text" name="ldap_server" title="Please enter the host of your LDAP server here." /><br />
        
    <label class="left">LDAP server port:</label>
    <input class="input" type="text" name="ldap_port" value="389" title="Please enter the port of your LDAP server here." /><br />
    
    <label class="left">LDAP username:</label>
    <input class="input" type="text" name="ldap_user" title="Please enter your specified RDN username here." /><br />
    
    <label class="left">LDAP password:</label>
    <input class="input" name="ldap_password" type="password" title="Please enter your LDAP password here." /><br />
    
    <label class="left">Distinguished name (dn):</label>
    <input class="input" type="text" name="ldap_base" title="Please enter your distinguished name, e.g. 'cn=John Smith,ou=Accounts,o=My Company,c=US' here." />

</fieldset>
<br />
<?php
    }
?>

<fieldset class="installation">
<legend class="installation">phpMyFAQ information</legend>
    
    <label class="left">Default language:</label>
    <select class="input" name="language" size="1" title="Please select your default language.">
<?php
    if ($dir = @opendir(PMF_ROOT_DIR."/lang")) {
        while ($dat = @readdir($dir)) {
            if (substr($dat, -4) == ".php") {
                print "\t\t<option value=\"".$dat."\"";
                if ($dat == "language_en.php") {
                    print " selected=\"selected\"";
                }
                print ">".$languageCodes[substr(strtoupper($dat), 9, 2)]."</option>\n";
            }
        }
    } else {
        print "\t\t<option>english</option>";
    }
?>
    </select><br />
    
    <label class="left">Permission level:</label>
    <select class="input" name="permLevel" size="1" title="Complexity of user and right administration. Basic: users may have user-rights. Medium: users may have user-rights; group administration; groups may have group-rights; user have group-rights via group-memberships.">
<?php
foreach ($permLevels as $level => $desc) {
    printf('    <option value="%s">%s</option>', $level, $desc);
}
?>
    </select><br />
    
    <label class="left">Administrator's real name:</label>
    <input class="input" type="text" name="realname" title="Please enter your real name here." /><br />
        
    <label class="left">Administrator's e-mail address:</label>
    <input class="input" type="text" name="email" title="Please enter your email adress here." /><br />
    
    <label class="left">Administrator's username:</label>
    <span id="admin">admin</span><br />
    
    <label class="left">Administrator's password:</label>
    <input class="input" type="password" name="password" title="Please enter your password for the admin area." /><br />
    
    <label class="left">Retype password:</label>
    <input class="input" type="password" name="password_retyped" title="Please retype your password for checkup." /><br />

<p class="center"><strong>Do not use if you're already running a version of phpMyFAQ!</strong></p>

<p class="center"><input type="submit" value="Install phpMyFAQ <?php print VERSION; ?> now!" class="button" /></p>
</fieldset>
</form>
<?php
    HTMLFooter();
} else {

    // Ckeck table prefix
    if (isset($_POST['sqltblpre']) && $_POST['sqltblpre'] != '') {
        $sqltblpre = $_POST['sqltblpre'];
    } else {
        $sqltblpre = '';
    }

    // check database entries
    if (isset($_POST["sql_type"]) && $_POST["sql_type"] != "") {
        $sql_type = trim($_POST["sql_type"]);
        if (file_exists(PMF_ROOT_DIR.'/install/'.$sql_type.'.sql.php')) {
            require_once(PMF_ROOT_DIR.'/install/'.$sql_type.'.sql.php');
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

    if (isset($_POST["sql_server"]) && $_POST["sql_server"] != "" || $sql_type == 'sqlite') {
        $sql_server = $_POST["sql_server"];
    } else {
        print "<p class=\"error\"><strong>Error:</strong> There's no DB server input.</p>\n";
        HTMLFooter();
        die();
    }

    if (isset($_POST["sql_user"]) && $_POST["sql_user"] != "" || $sql_type == 'sqlite') {
        $sql_user = $_POST["sql_user"];
    } else {
        print "<p class=\"error\"><strong>Error:</strong> There's no DB username input.</p>\n";
        HTMLFooter();
        die();
    }

    if (isset($_POST["sql_passwort"]) && $_POST["sql_passwort"] != "" || $sql_type == 'sqlite') {
        $sql_passwort = $_POST["sql_passwort"];
    } else {
        $sql_passwort = '';
    }

    if (isset($_POST["sql_db"]) && $_POST["sql_db"] != "" || $sql_type == 'sqlite') {
        $sql_db = $_POST["sql_db"];
    } else {
        print "<p class=\"error\"><strong>Error:</strong> There's no DB database input.</p>\n";
        HTMLFooter();
        die();
    }

    if ($sql_type == 'sqlite') {
        if (isset($_POST["sql_sqlitefile"]) && $_POST["sql_sqlitefile"] != "") {
            $sql_server = $_POST["sql_sqlitefile"]; // We're using $sql_server, too!
        } else {
            print "<p class=\"error\"><strong>Error:</strong> There's no SQLite database filename input.</p>\n";
            HTMLFooter();
            die();
        }
    }

    // check database connection
    require_once(PMF_ROOT_DIR."/inc/Db.php");
    $db = PMF_Db::db_select($sql_type);
    $db->connect($sql_server, $sql_user, $sql_passwort, $sql_db);
    if (!$db) {
        print "<p class=\"error\"><strong>DB Error:</strong> ".$db->error()."</p>\n";
        HTMLFooter();
        die();
    }

    // check LDAP if available
    if (extension_loaded('ldap') && isset($_POST['ldap_enabled']) && $_POST['ldap_enabled'] == 'yes') {

        // check LDAP entries
        if (isset($_POST["ldap_server"]) && $_POST["ldap_server"] != "") {
            $ldap_server = $_POST["ldap_server"];
        } else {
            print "<p class=\"error\"><strong>Error:</strong> There's no LDAP server input.</p>\n";
            HTMLFooter();
            die();
        }
        if (isset($_POST["ldap_port"]) && $_POST["ldap_port"] != "") {
            $ldap_port = $_POST["ldap_port"];
        } else {
            print "<p class=\"error\"><strong>Error:</strong> There's no LDAP port input.</p>\n";
            HTMLFooter();
            die();
        }
        if (isset($_POST["ldap_user"]) && $_POST["ldap_user"] != "") {
            $ldap_user = $_POST["ldap_user"];
        } else {
            print "<p class=\"error\"><strong>Error:</strong> There's no LDAP username input.</p>\n";
            HTMLFooter();
            die();
        }
        if (isset($_POST["ldap_password"]) && $_POST["ldap_password"] != "") {
            $ldap_password = $_POST["ldap_password"];
        } else {
            print "<p class=\"error\"><strong>Error:</strong> There's no LDAP password input.</p>\n";
            HTMLFooter();
            die();
        }
        if (isset($_POST["ldap_base"]) && $_POST["ldap_base"] != "") {
            $ldap_base = $_POST["ldap_base"];
        } else {
            print "<p class=\"error\"><strong>Error:</strong> There's no distinguished name input for LDAP.</p>\n";
            HTMLFooter();
            die();
        }

        // check LDAP connection
        require_once(PMF_ROOT_DIR."/inc/Ldap.php");
        $ldap = new PMF_Ldap($ldap_server, $ldap_port, $ldap_user, $ldap_base);
        if (!$ldap) {
            print "<p class=\"error\"><strong>LDAP Error:</strong> ".$ldap->error()."</p>\n";
            HTMLFooter();
            die();
        }
    }

    // check user entries
    if (isset($_POST["password"]) && $_POST["password"] != "") {
        $password = $_POST["password"];
    } else {
        print "<p class=\"error\"><strong>Error:</strong> There's no password for the administrator's account. Please set your password.</p>\n";
        HTMLFooter();
        die();
    }
    if (isset($_POST["password_retyped"]) && $_POST["password_retyped"] != "") {
        $password_retyped = $_POST["password_retyped"];
    } else {
        print "<p class=\"error\"><strong>Error:</strong> There's no retyped password. Please set your retyped password.</p>\n";
        HTMLFooter();
        die();
    }
    if (strlen($password) <= 5 || strlen($password_retyped) <= 5) {
        print "<p class=\"error\"><strong>Error:</strong> Your password and retyped password are too short. Please set your password and your retyped password with a minimum of 6 characters.</p>\n";
        HTMLFooter();
        die();
    }
    if ($password != $password_retyped) {
        print "<p class=\"error\"><strong>Error:</strong> Your password and retyped password are not equal. Please check your password and your retyped password.</p>\n";
        HTMLFooter();
        die();
    }

    if (isset($_POST["language"]) && $_POST["language"] != "") {
        $language = $_POST["language"];
    } else {
        $language = "en";
    }
    if (isset($_POST["realname"]) && $_POST["realname"] != "") {
        $realname = $_POST["realname"];
    } else {
        $realname = "";
    }
    if (isset($_POST["email"]) && $_POST["email"] != "") {
        $email = $_POST["email"];
    } else {
        $email = "";
    }
    $permLevel = (isset($_POST['permLevel']) && in_array($_POST['permLevel'], $permLevels)) ? $_POST['permLevel'] : 'basic';

    // Write the DB variables in data.php
    if ($fp = @fopen(PMF_ROOT_DIR."/inc/data.php","w")) {
        @fputs($fp,"<?php\n\$DB[\"server\"] = '".$sql_server."';\n\$DB[\"user\"] = '".$sql_user."';\n\$DB[\"password\"] = '".$sql_passwort."';\n\$DB[\"db\"] = '".$sql_db."';\n\$DB[\"prefix\"] = '".$sqltblpre."';\n\$DB[\"type\"] = '".$sql_type."';\n?>");
        @fclose($fp);
    } else {
        print "<p class=\"error\"><strong>Error:</strong> Cannot write to data.php.</p>";
        HTMLFooter();
        cleanInstallation();
        die();
    }

    // check LDAP if available
    if (extension_loaded('ldap') && isset($_POST['ldap_enabled']) && $_POST['ldap_enabled'] == 'yes') {

        if ($fp = @fopen(PMF_ROOT_DIR."/inc/dataldap.php","w")) {
            @fputs($fp,"<?php\n\$PMF_LDAP[\"ldap_server\"] = '".$ldap_server."';\n\$PMF_LDAP[\"ldap_port\"] = '".$ldap_port."';\n\$PMF_LDAP[\"ldap_user\"] = '".$ldap_user."';\n\$PMF_LDAP[\"ldap_password\"] = '".$ldap_passwort."';\n\$PMF_LDAP[\"ldap_base\"] = '".$ldap_base."';\n;\n?>");
            @fclose($fp);
        } else {
            print "<p class=\"error\"><strong>Error:</strong> Cannot write to dataldap.php.</p>";
            HTMLFooter();
            cleanInstallation();
            die();
        }

    }

    // connect to the database using inc/data.php
    require_once(PMF_ROOT_DIR."/inc/data.php");
    require_once(PMF_ROOT_DIR."/inc/Db.php");
    $db = PMF_Db::db_select($sql_type);
    $db->connect($DB["server"], $DB["user"], $DB["password"], $DB["db"]);
    if (!$db) {
        print "<p class=\"error\"><strong>DB Error:</strong> ".$db->error()."</p>\n";
        HTMLFooter();
        cleanInstallation();
        die();
    }

    require_once($sql_type.'.sql.php');
    require_once('config.sql.php');
    print "<p class=\"center\">";
    while ($each_query = each($query)) {
        $result = @$db->query($each_query[1]);
        print "|&nbsp;";
        if (!$result) {
            print "\n<div class=\"error\">\n";
            print "<p><strong>Error:</strong> Please install your version of phpMyFAQ once again or send us a <a href=\"http://bugs.phpmyfaq.de\" target=\"_blank\">bug report</a>.</p>";
            print "<p><strong>DB error:</strong> ".$db->error()."</p>\n";
            print "<div style=\"text-align: left;\"><p>Query:\n";
            print "<pre>".PMF_htmlentities($each_query[1])."</pre></p></div>\n";
            print "</div>";
            uninstall();
            cleanInstallation();
            HTMLFooter();
            die();
        }
    }

    // add admin account and rights
    if (!defined('SQLPREFIX')) {
        define('SQLPREFIX', $sqltblpre);
    }
    require_once(PMF_ROOT_DIR.'/inc/PMF_User/User.php');
    $admin = new PMF_User();
    $admin->createUser('admin', $password);
    $admin->setStatus('protected');
    $adminData = array(
        'display_name' => $realname,
        'email' => $email
    );
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
        //18 => "addatt",
        array(
            'name' => 'addatt',
            'description' => 'Right to add attachments',
            'for_users' => 1,
            'for_groups' => 1
        ),
        //19 => "delatt",
        array(
            'name' => 'delatt',
            'description' => 'Right to delete attachments',
            'for_users' => 1,
            'for_groups' => 1
        ),
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
            'description' => 'Edit revisions',
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
        'email' => null
    );
    $anonymous->setUserData($anonymousData);
    
    require_once(PMF_ROOT_DIR.'/inc/Configuration.php');
    require_once(PMF_ROOT_DIR.'/inc/Link.php');
    $oConf = new PMF_Configuration($db);
    $oConf->getAll();
    $configs = $oConf->config;
    // Disable Captcha if GD is not available
    $configs['spamEnableCatpchaCode'] = (extension_loaded('gd') ? 'true' : 'false');
    // Set link verification base url
    $configs['referenceURL'] = PMF_Link::getSystemUri('/install/installer.php');
    // Create a unique identifier
    $configs['phpMyFAQToken'] = md5(uniqid(rand()));
    $oConf->update($configs);
    print "</p>\n";

    print "<p class=\"center\">All database tables were successfully created.</p>\n";
    print "<p class=\"center\">Congratulation! Everything seems to be okay.</p>\n";
    print "<p class=\"center\">You can visit <a href=\"../index.php\">your version of phpMyFAQ</a> or</p>\n";
    print "<p class=\"center\">login into your <a href=\"../admin/index.php\">admin section</a>.</p>\n";

    // Remove 'scripts' folder: no need of prompt anything to the user
    if (file_exists(PMF_ROOT_DIR."/scripts") && is_dir(PMF_ROOT_DIR."/scripts")) {
        @rmdir(PMF_ROOT_DIR."/scripts");
    }
    // Remove 'phpmyfaq.spec' file: no need of prompt anything to the user
    if (file_exists(PMF_ROOT_DIR."/phpmyfaq.spec")) {
        @unlink(PMF_ROOT_DIR."/phpmyfaq.spec");
    }
    // Remove 'installer.php' file
    if (@unlink(basename($_SERVER["PHP_SELF"]))) {
        print "<p class=\"center\">The file <em>./install/installer.php</em> was deleted automatically.</p>\n";
    } else {
        print "<p class=\"center\">Please delete the file <em>./install/installer.php</em> manually.</p>\n";
    }
    // Remove 'update.php' file
    if (@unlink(dirname($_SERVER["PATH_TRANSLATED"])."/update.php")) {
        print "<p class=\"center\">The file <em>./install/update.php</em> was deleted automatically.</p>\n";
    } else {
        print "<p class=\"center\">Please delete the file <em>./install/update.php</em> manually.</p>\n";
    }

    HTMLFooter();
}
