<?php
/**
* $Id: installer.php,v 1.8 2004-12-17 14:27:45 thorstenr Exp $
*
* The main phpMyFAQ Installer
*
* This script tests the complete environment, writes the database connection
* paramters into the file data.php and the configuration into the file
* config.php. After that the script creates all MySQL tables and inserts the
* admin user.
*
* @author      Thorsten Rinne <thorsten@phpmyfaq.de>
* @author      Tom Rochester <tom.rochester@gmail.com>
* @since       2002-08-20
* @copyright   (c) 2001-2004 phpMyFAQ Team
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

define("VERSION", "1.5.0 alpha");
define("COPYRIGHT", "&copy; 2001-2004 <a href=\"http://www.phpmyfaq.de/\">phpMyFAQ-Team</a> | All rights reserved.");
define("SAFEMODE", @ini_get("safe_mode"));
define("PMF_ROOT_DIR", dirname(dirname(__FILE__)));
require_once(PMF_ROOT_DIR."/inc/constants.php");

function php_check ($ist = "", $soll = "", $err_msg = "")
{
    if (empty($ist) OR empty($soll)) {
        return FALSE;
    }
    $ist = explode(".", $ist);
    $soll = explode(".", $soll);
    $num = count($soll);
    for ($i = 0; $i < $num; $i++) {
        if ($ist[$i] <  $soll[$i]) {
            return FALSE;
        }
        if ($ist[$i] == $soll[$i]) {
            continue;
        }
        if ($ist[$i] >= $soll[$i]) {
            return TRUE;
        }
    }
    return TRUE;
}

function phpmyfaq_check($file)
{
    if (@include($file)) {
        include($file);
        // check for version 1.3.x
        if ((isset($mysql_server) && $mysql_server != "") || (isset($mysql_user) && $mysql_user != "") || (isset($mysql_passwort) && $mysql_passwort != "") || (isset($mysql_db) && $mysql_db != "")) {
            return FALSE;
            }
        // check for version 1.4.x
        if ((isset($DB["server"]) && $DB["server"] != "") || (isset($DB["user"]) && $DB["user"] != "") || (isset($DB["password"]) && $DB["password"] != "") || (isset($DB["db"]) && $DB["db"] != "") || (isset($DB["prefix"]) && $DB["prefix"] != "")) {
            return FALSE;
            }
        return TRUE;
        }
    return TRUE;
}

function uninstall() {
	global $uninst, $db;
	while ($each_query = each($uninst)) {
		$result = $db->query($each_query[1]);
		}
	}

function HTMLFooter() {
	print "<p class=\"center\">".COPYRIGHT."</p>\n</body>\n</html>";
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title>phpMyFAQ <?php print VERSION; ?> Installation</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
    <style type="text/css"><!--
    body {
	    margin: 0px;
	    padding: 0px;
	    font-size: 12px;
	    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	    background-color: #B0B0B0;
    }
    #header {
	    margin: auto;
	    padding: 35px;
	    background-color: #6A88B1;
        text-align: center;
    }
    #header h1 {
	    font: bold 36px Garamond, times, serif;
	    margin: auto;
	    color: #f5f5f5;
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
        background-color: #FCE397;
        padding: 4px 4px 4px 4px;
    }
    .input {
        width: 200px;
        background-color: #f5f5f5;
        border: 1px solid black;
    }
    span.text {
        width: 250px;
        float: left;
        text-align: right;
        padding-right: 10px;
        line-height: 20px;
    }
    #admin {
        line-height: 20px;
        font-weight: bold;
    }
    .help {
        cursor: help;
        border-bottom: 1px dotted Black;
        font-size: 14px;
        font-weight: bold;
        padding-left: 5px;
    }
    .button {
        background-color: #ff7f50;
        border: 1px solid #000000;
        color: #ffffff;
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
    --></style>
</head>
<body>

<h1 id="header">phpMyFAQ <?php print VERSION; ?> Installation</h1>

<?php
if (php_check(phpversion(), '4.1.0') == FALSE) {
	print "<p class=\"center\">You need PHP Version 4.1.0 or higher!</p>\n";
	HTMLFooter();
	die();
}
if (!phpmyfaq_check("../inc/data.php")) {
	print "<p class=\"center\">It seems you already running a version of phpMyFAQ.<br />Please use the <a href=\"update.php\">update script</a>.</p>\n";
	HTMLFooter();
	die();
}
if (!is_dir(PMF_ROOT_DIR."/attachments")) {
    if (!mkdir (PMF_ROOT_DIR."/attachments", 0755)) {
        print "<p class=\"center\">The directory ../attachments could not be created. Please create it manually and change access to chmod 755 (or greater if necessary).</p>\n";
	    HTMLFooter();
	    die();
    }
}
if (!is_dir(PMF_ROOT_DIR."/data")) {
    if (!mkdir (PMF_ROOT_DIR."/data", 0755)) {
        print "<p class=\"center\">The directory ../data could not be created. Please create it manually and change access to chmod 755 (or greater if necessary).</p>\n";
	    HTMLFooter();
	    die();
    }
}
if (!is_dir(PMF_ROOT_DIR."/images")) {
    if (!mkdir (PMF_ROOT_DIR."/images", 0755)) {
        print "<p class=\"center\">The directory ../images could not be created. Please create it manually and change access to chmod 755 (or greater if necessary).</p>\n";
	    HTMLFooter();
	    die();
        }
     }
if (!is_dir(PMF_ROOT_DIR."/pdf")) {
    if (!mkdir (PMF_ROOT_DIR."/pdf", 0755)) {
        print "<p class=\"center\">The directory ../pdf could not be created. Please create it manually and change access to chmod 755 (or greater if necessary).</p>\n";
	    HTMLFooter();
	    die();
    }
}
if (!is_dir(PMF_ROOT_DIR."/xml")) {
    if (!mkdir (PMF_ROOT_DIR."/xml", 0755)) {
        print "<p class=\"center\">The directory ../xml could not be created. Please create it manually and change access to chmod 755 (or greater if necessary).</p>\n";
	    HTMLFooter();
	    die();
    }
}
if (!is_writeable(PMF_ROOT_DIR."/inc") || !@copy("index.html", PMF_ROOT_DIR."/inc/index.html")) {
    print "<p class=\"center\">The directory ../inc is not writeable. Please change access to chmod 755 (or greater if necessary).</p>\n";
    HTMLFooter();
    die();
}
if (!is_writeable(PMF_ROOT_DIR."/attachments") || !@copy("index.html", PMF_ROOT_DIR."/attachments/index.html")) {
    print "<p class=\"center\">The directory ../attachments is not writeable. Please change access to chmod 755 (or greater if necessary).</p>\n";
    HTMLFooter();
    die();
}
if (!is_writeable(PMF_ROOT_DIR."/data") || !@copy("index.html", PMF_ROOT_DIR."/data/index.html")) {
    print "<p class=\"center\">The directory ../data is not writeable. Please change access to chmod 755 (or greater if necessary).</p>\n";
    HTMLFooter();
    die();
}
if (!is_writeable(PMF_ROOT_DIR."/images") || !@copy("index.html", PMF_ROOT_DIR."/images/index.html")) {
    print "<p class=\"center\">The directory ../images is not writeable. Please change access to chmod 755 (or greater if necessary).</p>\n";
    HTMLFooter();
    die();
}
if (!is_writeable(PMF_ROOT_DIR."/pdf") || !@copy("index.html", PMF_ROOT_DIR."/pdf/index.html")) {
	print "<p class=\"center\">The directory ../pdf is not writeable. Please change access to chmod 755 (or greater if necessary).</p>\n";
	HTMLFooter();
	die();
}
if (!is_writeable(PMF_ROOT_DIR."/xml") || !@copy("index.html", PMF_ROOT_DIR."/xml/index.html")) {
	print "<p class=\"center\">The directory ../xml is not writeable. Please change access to chmod 755 (or greater if necessary).</p>\n";
	HTMLFooter();
	die();
}
if (!isset($_POST["sql_server"]) AND !isset($_POST["sql_user"]) AND !isset($_POST["sql_db"])) {
?>

<p class="center">Your PHP version: <strong>PHP <?php print phpversion(); ?></strong></p>

<?php
    if (SAFEMODE == 1) {
        print "<p class=\"center\">The PHP safe mode is enabled. You may have problems when phpMyFAQ writes in some directories.</p>\n";
    }
?>

<p class="center">You should read the <a href="../docs/documentation.en.html">documentation</a> carefully before installing phpMyFAQ.</p>

<form action="<?php print $_SERVER["PHP_SELF"]; ?>" method="post">
<fieldset class="installation">
<legend class="installation">Database information</legend>
<p>
<span class="text">SQL server:</span>
<select class="input" name="sql_type" size="1">
<?php
	// check what extensions are loaded in PHP
	if (extension_loaded('mysql')) {
		print '<option value="mysql">MySQL</option>';
	} elseif (extension_loaded('pgsql')) {
		print '<option value="pgsql">PostgreSQL</option>';
	} elseif (extension_loaded('sybase')) {
		print '<option value="sybase">Sybase</option>';
	} else {
		print '<option value="">Sorry, no supported database found in your PHP version!</option>';
	}
?>	
</select>
<span class="help" title="Please enter the type of SQL server here.">?</span>
</p>
<p>
<span class="text">SQL server host:</span>
<input class="input" type="text" name="sql_server" />
<span class="help" title="Please enter the host of your SQL server here.">?</span>
</p>
<p>
<span class="text">SQL username:</span>
<input class="input" type="text" name="sql_user" />
<span class="help" title="Please enter your SQL username here.">?</span>
</p>
<p>
<span class="text">SQL password:</span>
<input class="input" name="sql_passwort" type="password" />
<span class="help" title="Please enter your SQL password here.">?</span>
</p>
<p>
<span class="text">SQL database:</span>
<input class="input" type="text" name="sql_db" />
<span class="help" title="Please enter your SQL database here.">?</span>
</p>
<p>
<span class="text">Table prefix:</span>
<input class="input" type="text" name="sqltblpre" />
<span class="help" title="Please enter a table prefix here if you want to install more phpMyFAQ installations on one database.">?</span>
</p>
</fieldset>
<br />
<?php
    if (extension_loaded('ldap')) {
?>
<fieldset class="installation">
<legend class="installation">LDAP information</legend>
<p>
<span class="text">LDAP server host:</span>
<input class="input" type="text" name="ldap_server" />
<span class="help" title="Please enter the host of your LDAP server here.">?</span>
</p>
<p>
<span class="text">LDAP server port:</span>
<input class="input" type="text" name="ldap_port" value="389" />
<span class="help" title="Please enter the port of your LDAP server here.">?</span>
</p>
<p>
<span class="text">Specified RDN:</span>
<input class="input" type="text" name="ldap_user" />
<span class="help" title="Please enter your specified RDN username here.">?</span>
</p>
<p>
<span class="text">LDAP password:</span>
<input class="input" name="ldap_password" type="password" />
<span class="help" title="Please enter your LDAP password here.">?</span>
</p>
<p>
<span class="text">Distinguished name (dn):</span>
<input class="input" type="text" name="ldap_base" />
<span class="help" title="Please enter your distinguished name, e.g. 'cn=John Smith,ou=Accounts,o=My Company,c=US' here.">?</span>
</p>
</fieldset>
<br />
<?php
    }
?>

<fieldset class="installation">
<legend class="installation">phpMyFAQ information</legend>
<p>
<span class="text">Default language:</span>
<select class="input" name="language" size="1">
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
</select>
<span class="help" title="Please select your default language.">?</span>
</p>
<p>
<span class="text">Administrator's real name:</span>
<input class="input" type="text" name="realname" />
<span class="help" title="Please enter your real name here.">?</span>
</p>
<p>
<span class="text">Administrator's e-mail address:</span>
<input class="input" type="text" name="email" />
<span class="help" title="Please enter your email adress here.">?</span>
</p>
<p>
<span class="text">Administrator's username:</span>
<span id="admin">admin</span>
</p>
<p>
<span class="text">Administrator's password:</span>
<input class="input" type="password" name="password" />
<span class="help" title="Please enter your password for the admin area.">?</span>
</p>
<p>
<span class="text">Retype password:</span>
<input class="input" type="password" name="password_retyped" />
<span class="help" title="Please retype your password for checkup.">?</span>
</p>
<p class="center"><strong>Do not use if you're already running a version of phpMyFAQ!</strong></p>
<p class="center"><input type="submit" value="Install phpMyFAQ" class="button" /></p>
</fieldset>
</form>
<?php
    HTMLFooter();
} else {
    // check database entries
	if (isset($_POST["sql_type"]) && $_POST["sql_type"] != "") {
		$sql_type = $_POST["sql_type"];
		switch ($sql_type) {
		
			case 'mysql':		
											require_once(PMF_ROOT_DIR."/inc/mysql.php");
											break;
			case 'pgsql':
											require_once(PMF_ROOT_DIR."/inc/pgsql.php");
											break;
			case 'sybase':	
											require_once(PMF_ROOT_DIR."/inc/sybase.php");
											break;
			default:				
											print '<p class="error"><strong>Error:</strong> Invalid server type.</p>';
											HTMLFooter();
											die();
		}
	} else {
        print "<p class=\"error\"><strong>Error:</strong> There's no DB server input.</p>\n";
		HTMLFooter();
		die();
	}
    
    if (isset($_POST["sql_server"]) && $_POST["sql_server"] != "") {
		$sql_server = $_POST["sql_server"];
    } else {
        print "<p class=\"error\"><strong>Error:</strong> There's no DB server input.</p>\n";
		HTMLFooter();
		die();
    }
	if (isset($_POST["sql_user"]) && $_POST["sql_user"] != "") {
		$sql_user = $_POST["sql_user"];
    } else {
        print "<p class=\"error\"><strong>Error:</strong> There's no DB username input.</p>\n";
		HTMLFooter();
		die();
    }
    if (isset($_POST["sql_passwort"]) && $_POST["sql_passwort"] != "") {
		$sql_passwort = $_POST["sql_passwort"];
    } else {
        print "<p class=\"error\"><strong>Error:</strong> There's no DB password input.</p>\n";
		HTMLFooter();
		die();
    }
    if (isset($_POST["sql_db"]) && $_POST["sql_db"] != "") {
		$sql_db = $_POST["sql_db"];
    } else {
        print "<p class=\"error\"><strong>Error:</strong> There's no DB database input.</p>\n";
		HTMLFooter();
		die();
    }
    
    // check database connection
	$func = "db_".$sql_type;
	$db = new $func();
	$db->connect($sql_server, $sql_user, $sql_passwort, $sql_db);
	if (!$db) {
		print "<p class=\"error\"><strong>DB Error:</strong> ".$db->error()."</p>\n";
		HTMLFooter();
		die();
    }
    
    // check LDAP if available
    if (extension_loaded('ldap')) {
        
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
            $ldap_password = $_POST["ldap_passwort"];
        } else {
            print "<p class=\"error\"><strong>Error:</strong> There's no LDAP password input.</p>\n";
            HTMLFooter();
            die();
        }
        if (isset($_POST["ldap_base"]) && $_POST["ldap_base"] != "") {
            $ldap_password = $_POST["ldap_base"];
        } else {
            print "<p class=\"error\"><strong>Error:</strong> There's no distinguished name input for LDAP.</p>\n";
            HTMLFooter();
            die();
        }
        
        // check LDAP connection
        require_once(PMF_ROOT_DIR."/inc/ldap.php");
        $ldap = new LDAP($ldap_server, $ldap_port, $ldap_user, $ldap_base);
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
		print "<p class=\"error\"><strong>Error:</strong> There's no password. Please set your password.</p>\n";
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
    if (isset($_POST["sqltblpre"]) && $_POST["sqltblpre"] != "") {
        $sqltblpre = $_POST["sqltblpre"];
    } else {
        $sqltblpre = "";
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
    
    // Write the DB variables in data.php
	if ($fp = @fopen(PMF_ROOT_DIR."/inc/data.php","w")) {
		@fputs($fp,"<?php\n\$DB[\"server\"] = '".$sql_server."';\n\$DB[\"user\"] = '".$sql_user."';\n\$DB[\"password\"] = '".$sql_passwort."';\n\$DB[\"db\"] = '".$sql_db."';\n\$DB[\"prefix\"] = '".$sqltblpre."';\n\$DB[\"type\"] = '".$sql_type."';\n?>");
		@fclose($fp);
    } else {
		print "<p class=\"error\"><strong>Error:</strong> Cannot write to data.php.</p>";
        HTMLFooter();
		die();
	}
    
    // check LDAP if available
    if (extension_loaded('ldap')) {
        
        if ($fp = @fopen(PMF_ROOT_DIR."/inc/dataldap.php","w")) {
            @fputs($fp,"<?php\n\$PMF_LDAP[\"ldap_server\"] = '".$ldap_server."';\n\$PMF_LDAP[\"ldap_port\"] = '".$ldap_port."';\n\$PMF_LDAP[\"ldap_user\"] = '".$ldap_user."';\n\$PMF_LDAP[\"ldap_password\"] = '".$ldap_passwort."';\n\$PMF_LDAP[\"ldap_base\"] = '".$ldap_base."';\n;\n?>");
            @fclose($fp);
        } else {
            print "<p class=\"error\"><strong>Error:</strong> Cannot write to dataldap.php.</p>";
            HTMLFooter();
            die();
        }
        
    }
	
    // Create config.php and write the language variables in the file
    if (@file_exists(PMF_ROOT_DIR."/inc/config.php")) {
    	print "<p class=\"center\">A config file was found. Please backup ../inc/config.php and remove the file.</p>\n";
    	HTMLFooter();
    	die();
    }
    if (!@copy(PMF_ROOT_DIR."/inc/config.php.original", PMF_ROOT_DIR."/inc/config.php")) {
    	print "<p class=\"center\">Could not copy the file ../inc/config.php.original to ../inc/config.php.</p>\n";
    	HTMLFooter();
    	die();
    }
	if ($fp = @fopen(PMF_ROOT_DIR."/inc/config.php","r")) {
		$anz = 0;
		while($dat = fgets($fp,1024)) {
			$anz++;
			$inp[$anz] = $dat;
		}
		@fclose($fp);
		for ($h = 1; $h <= $anz; $h++) {
			if (str_replace("\$PMF_CONF[\"language\"] = \"en\";", "", $inp[$h]) != $inp[$h]) {
				$inp[$h] = "\$PMF_CONF[\"language\"] = \"".$language."\";\n";
			}
		}
		if ($fp = @fopen(PMF_ROOT_DIR."/inc/config.php","w")) {
			for ($h = 1; $h <= $anz; $h++) {
				fputs($fp,$inp[$h]);
			}
			@fclose($fp);
		} else {
			print "<p>Cannot write to config.php.</p></td>";
            HTMLFooter();
		    die();
		}
	} else {
		print "<p>Cannot read config.php.</p></td>";
        HTMLFooter();
		die();
	}
	
    // connect to the database using inc/data.php
    require_once(PMF_ROOT_DIR."/inc/data.php");
	$func = "db_".$DB["type"];
	$db = new $func();
	$db->connect($DB["server"], $DB["user"], $DB["password"], $DB["db"]);
	if (!$db) {
		print "<p class=\"error\"><strong>DB Error:</strong> ".$db->error()."</p>\n";
		HTMLFooter();
		die();
	}
	include_once($sql_type.".sql.php");
	print "<p class=\"center\"><strong>";
	while ($each_query = each($query)) {
		$result = $db->query($each_query[1]);
		print "|&nbsp;";
		if (!$result) {
			print "<!-- ".$each_query[1]." --><p class=\"error\"><strong>Error:</strong> Please install your version of phpMyFAQ once again or send us a <a href=\"http://bugs.phpmyfaq.de\" target=\"_blank\">bug report</a>.<br />DB error: ".$db->error()."</p>\n";
			uninstall();
		    HTMLFooter();
		    die();
		}
        usleep(250);
	}
	print "</strong></p>\n";
	
    print "<p class=\"center\">All tables were created and filled with the data.</p>\n";
    print "<p class=\"center\">Congratulation! Everything seems to be okay.</p>\n";
    print "<p class=\"center\">You can visit <a href=\"../index.php\">your version of phpMyFAQ</a> or</p>\n";
    print "<p class=\"center\">login into your <a href=\"../admin/index.php\">admin section</a>.</p>\n";
    
    if (@unlink(basename($_SERVER["PHP_SELF"]))) {
        print "<p class=\"center\">This file was deleted automatically.</p>\n";
    } else {
        print "<p class=\"center\">Please delete this file manually.</p>\n";
    }
    if (@unlink(dirname($_SERVER["PATH_TRANSLATED"])."/update.php")) {
        print "<p class=\"center\">The file 'update.php' was deleted automatically.</p>\n";
    } else {
        print "<p class=\"center\">Please delete the file 'update.php' manually.</p>\n";
    }
    HTMLFooter();
}
?>