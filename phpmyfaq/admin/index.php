<?php
/**
* $Id: index.php,v 1.7 2004-12-25 07:14:02 thorstenr Exp $
*
* The main admin backend index file
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Bastian Poettner <bastian@poettner.net>
* @author       Meikel Katzengreis <meikel@katzengreis.com>
* @since        2002-09-16
* @copyright    (c) 2001-2004 phpMyFAQ Team
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

/* debug mode:
 * - FALSE	debug mode disabled
 * - TRUE	debug mode enabled
 */
define("DEBUG", FALSE);
define("PMF_ROOT_DIR", dirname(dirname(__FILE__)));

if (DEBUG == FALSE) {
	error_reporting(E_ALL);
	}

/* delete cookie before sending a header */
if (isset($_REQUEST["aktion"]) && $_REQUEST["aktion"] == "delcookie") {
	setcookie("cuser", "", time()+30);
	setcookie("cpass", "", time()+30);
	}

/* read configuration, include classes and functions */
require_once (PMF_ROOT_DIR."/inc/data.php");
require_once (PMF_ROOT_DIR."/inc/db.php");
define("SQLPREFIX", $DB["prefix"]);
$db = new db($DB["type"]);
$db->connect($DB["server"], $DB["user"], $DB["password"], $DB["db"]);
require_once (PMF_ROOT_DIR."/inc/config.php");
require_once (PMF_ROOT_DIR."/inc/constants.php");
require_once (PMF_ROOT_DIR."/inc/category.php");
require_once (PMF_ROOT_DIR."/inc/functions.php");

/* set cookie before sending a header */
if (isset($_POST["aktion"]) && $_POST["aktion"] == "setcookie") {
	if (isset($_POST["cuser"])) {
		setcookie("cuser", $_POST["cuser"], time() + (86400 * $PMF_CONST["timeout"]));
		}
	if (isset($_POST["cpass"])) {
		setcookie("cpass", $_POST["cpass"], time() + (86400 * $PMF_CONST["timeout"]));
		}
	}

/* get language (default: english) */
if (isset($PMF_CONF["detection"]) && isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
    if (@is_file(PMF_ROOT_DIR."/lang/language_".substr($_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 2).".php")) {
        require_once(PMF_ROOT_DIR."/lang/language_".substr($_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 2).".php");
        $LANGCODE = substr($_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 2);
        @setcookie(PMF_ROOT_DIR."/lang", $LANGCODE, time()+3600);
    }
} elseif (!isset($PMF_CONF["detection"])) {
    require_once(PMF_ROOT_DIR."/lang/".$PMF_CONF["language"]);
    $LANGCODE = $PMF_LANG["metaLanguage"];
}
if (isset($LANGCODE)) {
    require_once(PMF_ROOT_DIR."/lang/language_".$LANGCODE.".php");
} else {
    require_once (PMF_ROOT_DIR."/lang/language_en.php");
    $LANGCODE = "en";
}

unset($auth);

/* if the cookie is set, take the data from it */
if (isset($_COOKIE["cuser"])) {
	$user = $_COOKIE["cuser"];
}
if (isset($_COOKIE["cpass"])) {
	$pass = $_COOKIE["cpass"];
}

/* delete old sessions */
$db->query("DELETE FROM ".SQLPREFIX."faqadminsessions WHERE time < ".(time()-($PMF_CONST["timeout"] * 60)));

/* is there an UIN? -> take it for authentication */
if (isset($_REQUEST["uin"])) {
	$uin = $_REQUEST["uin"];
}
if (isset($uin)) {
	$query = "SELECT usr, pass FROM ".SQLPREFIX."faqadminsessions WHERE uin = '".$uin."'";
	if ($PMF_CONF["ipcheck"]) {
		$query .= " AND ip = '".$_SERVER["REMOTE_ADDR"]."'";
	}
	list($user, $pass) = $db->fetch_row($db->query($query));
	$db->query ("UPDATE ".SQLPREFIX."faqadminsessions SET time = ".time()." WHERE uin = '".$uin."'");
}

/* authenticate the user */
if (isset($_REQUEST["faqusername"])) {
    $user = $_REQUEST["faqusername"];
}
if (isset($_REQUEST["faqpassword"])) {
    $pass = md5($_REQUEST["faqpassword"]);
}

if ((isset($user) && isset($pass)) || isset($uin)) {
	$result = $db->query("SELECT id, name, realname, email, pass, rights FROM ".SQLPREFIX."faquser WHERE name = '".$user."' AND pass = '".$pass."'");
	if ($db->num_rows($result) != 1) {
		// error
		if (!isset($uin)) {
			adminlog("Loginerror\nLogin: ".$user."\nPass: ".$pass);
			$error = $PMF_LANG["ad_auth_fail"]." (".$user." / *)";
			}
		else {
			adminlog("Session expired\nUIN: ".$uin."\nUser: ".$user."\nPass: ******");
			$error = $PMF_LANG["ad_auth_sess"];
			}
		unset($auth);
		$_REQUEST["aktion"] = "";
	} else {
		// okay, write new session, if not written
		$auth = 1;
		if (!isset($uin)) {
			$ok = 0;
			while (!$ok) {
				srand((double)microtime()*1000000);
  				$uin = md5(uniqid(rand())); 
				if ($db->num_rows($db->query("SELECT uin FROM ".SQLPREFIX."faqadminsessions WHERE uin = '".$uin."'")) < 1) {
					$ok = 1;
                } else {
					$ok = 0;
			    }
			}
			$db->query("INSERT INTO ".SQLPREFIX."faqadminsessions (uin, time, ip, usr, pass) VALUES ('".$uin."',".time().",'".$_SERVER["REMOTE_ADDR"]."','".$user."','".$pass."')");
		}
		$linkext = "?uin=".$uin;
		list($auth_user, $auth_name, $auth_realname, $auth_email, $auth_pass, $auth_rights) = $db->fetch_row($result);
		$user = $auth_name;
		$pass = $auth_pass;
        $permission = array_combine($faqrights, explode(",", substr(chunk_split($auth_rights,1,","), 0, -1)));
	}
}

/* logout - delete session */
if (isset($_REQUEST["aktion"]) && $_REQUEST["aktion"] == "logout" && $auth) {
	$db->query("DELETE FROM ".SQLPREFIX."faqadminsessions WHERE uin = '".$uin."'");
	unset($auth);
	unset($uid);
}

/* header of the admin page */
require_once ("header.php");
if (isset($auth)) {
	require_once ("menue.php");
}
?>
</div>
<div id="bodyText">
<?php
/* user is authenticated */
if (isset($auth)) {
	if (isset($_REQUEST["aktion"])) {
    /* the various sections of the admin area */
		switch ($_REQUEST["aktion"]) {
			// functions for user administration
			case "user":					require_once ("user.list.php"); break;
			case "deluser":					require_once ("user.delete.php"); break;
			case "useredit":				require_once ("user.edit.php"); break;
			case "usersave":				require_once ("user.save.php"); break;
			case "userdel":					require_once ("user.question.php"); break;
			case "useradd":					require_once ("user.add.php"); break;
			case "addsave":					require_once ("user.addsave.php"); break;
			// functions for record administration
			case "view":					require_once ("record.show.php"); break;
			case "accept":					require_once ("record.show.php"); break;
			case "zeichan":					require_once ("record.show.php"); break;
			case "takequestion":			require_once ("record.edit.php"); break;
			case "editentry":				require_once ("record.edit.php"); break;
            case "editpreview":             require_once ("record.edit.php"); break;
			case "delcomment":				require_once ("record.delcommentform.php"); break;
			case "deletecomment":			require_once ("record.delcomment.php"); break;
			case "insertentry":				require_once ("record.add.php"); break;
			case "saveentry":				require_once ("record.save.php"); break;
			case "delentry":				require_once ("record.delete.php"); break;
			case "delatt":					require_once ("record.delatt.php"); break;
			case "question":				require_once ("record.delquestion.php"); break;
			// news administraion
			case "news":					require_once ("news.php"); break;
			// category administration
			case "category":				require_once ("category.main.php"); break;
            case "addcategory":             require_once ("category.add.php"); break;
            case "savecategory":            require_once ("category.save.php"); break;
            case "editcategory":            require_once ("category.edit.php"); break;
            case "updatecategory":          require_once ("category.update.php"); break;
            case "deletecategory":          require_once ("category.delete.php"); break;
            case "removecategory":          require_once ("category.remove.php"); break;
            case "cutcategory":             require_once ("category.cut.php"); break;
            case "pastecategory":           require_once ("category.paste.php"); break;
            case "movecategory":            require_once ("category.move.php"); break;
            case "changecategory":          require_once ("category.change.php"); break;
			// functions for cookie administration
			case "setcookie":				require_once ("cookie.check.php"); break;
			case "cookies":					require_once ("cookie.check.php"); break;
			case "delcookie":				require_once ("cookie.check.php"); break;
			// adminlog administration
			case "adminlog":				require_once ("adminlog.php"); break;
			// functions for password administration
			case "passwd":					require_once ("pwd.change.php"); break;
			case "savepwd":					require_once ("pwd.save.php"); break;
			// functions for session administration
			case "viewsessions":			require_once ("stat.main.php"); break;
			case "sessionbrowse":			require_once ("stat.browser.php"); break;
			case "sessionsearch":			require_once ("stat.query.php"); break;
			case "sessionsuche":			require_once ("stat.form.php"); break;
			case "viewsession":				require_once ("stat.show.php"); break;
			case "statistik":				require_once ("stat.ratings.php"); break;
			// functions for config administration
			case "editconfig":				require_once ("config.edit.php"); break;
			case "saveconfig":				require_once ("config.save.php"); break;
			// functions for backup administration
			case "csv":						require_once ("backup.main.php"); break;
			case "restore":					require_once ("backup.import.php"); break;
			case "xml":						require_once ("backup.xml.php"); break;
			// functions for FAQ export
			case "export":					require_once ("export.main.php"); break;
			default:						print "Error"; break;
		}
	} else {
        /* start page with some informations about the FAQ */
        $PMF_TABLE_INFO = $db->getTableStatus();
?>
	<h2>phpMyFAQ Information</h2>
    <dl class="table-display">
	    <dt><strong><?php print $PMF_LANG["ad_start_visits"]; ?></strong></dt>
        <dd><?php print $PMF_TABLE_INFO[SQLPREFIX."faqsessions"]; ?></dd>
        <dt><strong><?php print $PMF_LANG["ad_start_articles"]; ?></strong></dt>
        <dd><?php print $PMF_TABLE_INFO[SQLPREFIX."faqdata"]; ?></dd>
        <dt><strong><?php print $PMF_LANG["ad_start_comments"]; ?></strong></dt>
        <dd><?php print $PMF_TABLE_INFO[SQLPREFIX."faqcomments"]; ?></dd>
        <dt><strong><?php print $PMF_LANG["msgOpenQuestions"]; ?></strong></dt>
        <dd><?php print $PMF_TABLE_INFO[SQLPREFIX."faqfragen"]; ?></dd>
    </dl>
<?php
        $rg = @ini_get("register_globals");
		if ($rg == "1") {
			$rg = "on";
		} else {
			$rg = "off";
		}
		$sm = @ini_get("safe_mode");
		if ($sm == "1") {
			$sm = "on";
		} else {
			$sm = "off";
		}
?>
	<h2>System Information</h2>
	<dl class="table-display">
		<dt><strong>phpMyFAQ Version</strong></dt>
		<dd>phpMyFAQ <?php print $PMF_CONF["version"]; ?></dd>
		<dt><strong>Server Software</strong></dt>
		<dd><?php print $_SERVER["SERVER_SOFTWARE"]; ?></dd>
		<dt><strong>PHP Version</strong></dt>
		<dd>PHP <?php print phpversion(); ?></dd>
		<dt><strong>PHP Memory Limit</strong></dt>
		<dd><?php print @ini_get("memory_limit"); ?></dd>
		<dt><strong>Register Globals</strong></dt>
		<dd><?php print $rg; ?></dd>
		<dt><strong>Safe Mode</strong></dt>
		<dd><?php print $sm; ?></dd>
		<dt><strong>Database Client Version</strong></dt>
		<dd><?php print $db->client_version(); ?></dd>
		<dt><strong>Database Server Version</strong></dt>
		<dd><?php print $db->server_version(); ?></dd>
		<dt><strong>Webserver Interface</strong></dt>
		<dd><?php print strtoupper(@php_sapi_name()); ?></dd>
    </dl>
	<h2>Online Version Information</h2>
<?php
        if (isset($_POST["param"]) && $_POST["param"] == "version") {
            require_once (PMF_ROOT_DIR."/inc/xmlrpc.php");
            $param = $_POST["param"];
            $xmlrpc = new xmlrpc_client("/xml/version.php", "www.phpmyfaq.de", 80);
            $msg = new xmlrpcmsg("phpmyfaq.version", array(new xmlrpcval($param, "string")));
            $answer = $xmlrpc->send($msg);
            $result = $answer->value();
            if ($answer->faultCode()) {
                print "<p>Error: ".$answer->faultCode()." (" .htmlspecialchars($answer->faultString()).")</p>";
            } else {
                print "<p>".$PMF_LANG["ad_xmlrpc_latest"]." <a href=\"http://www.phpmyfaq.de\" target=\"_blank\">www.phpmyfaq.de</a>: phpMyFAQ ".$result->scalarval()."</p>";
            }
        } else {
?>
    <form action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>" method="post">
    <input type="hidden" name="param" value="version" />
    <input class="submit" type="submit" value="<?php print $PMF_LANG["ad_xmlrpc_button"]; ?>" />
    </form>
<?php
        }
	}
} else {
?>
	<form action="<?php print $_SERVER["PHP_SELF"]; ?>" method="post">
    <fieldset class="login">
        <legend class="login">phpMyFAQ Login</legend>
<?php 
	if (isset($_REQUEST["aktion"]) && $_REQUEST["aktion"] == "logout") {
		print "<p>".$PMF_LANG["ad_logout"]."</p>";
	}
	if (isset($error)) {
		print "<p><strong>".$error."</strong></p>\n";
	} else {
		print "<p><strong>".$PMF_LANG["ad_auth_insert"]."</strong></p>\n";
	}
?>
        <div class="row"><span class="label"><strong><?php print $PMF_LANG["ad_auth_user"]; ?></strong></span>
        <input class="admin" type="text" name="faqusername" size="20" /></div>
        <div class="row"><span class="label"><strong><?php print $PMF_LANG["ad_auth_passwd"]; ?></strong></span>
        <input class="admin" type="password" size="20" name="faqpassword" /></div>
        <div class="row"><span class="label">&nbsp;</span>
        <input class="submit" type="submit" value="<?php print $PMF_LANG["ad_auth_ok"]; ?>" />
        <input class="submit" type="reset" value="<?php print $PMF_LANG["ad_auth_reset"]; ?>" /></div>
        
        <p><img src="images/arrow.gif" width="11" height="11" alt="<?php print $PMF_LANG["lostPassword"]; ?>" border="0" /> <a href="password.php" title="<?php print $PMF_LANG["lostPassword"]; ?>">
<?php print $PMF_LANG["lostPassword"]; ?>
</a></p>
        <p><img src="images/arrow.gif" width="11" height="11" alt="<?php print $PMF_CONF["title"]; ?> FAQ" border="0" /> <a href="../index.php" title="<?php print $PMF_CONF["title"]; ?> FAQ"><?php print $PMF_CONF["title"]; ?> FAQ</a></p>
        
    </fieldset>
	</form>
    
<?php
}

if (DEBUG == TRUE) {
    print "<p>DEBUG INFORMATION:</p>\n";
	print "<p>".$db->sqllog()."</p>";
}

require_once ("footer.php");
$db->dbclose();
?>
