<?php
/**
* $Id: password.php,v 1.6 2005-09-25 09:47:02 thorstenr Exp $
*
* Reset a forgotten password to a new one
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2004-05-11
* @copyright    (c) 2004 - 2005 phpMyFAQ Team
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

require_once('../inc/init.php');
define('IS_VALID_PHPMYFAQ_ADMIN', null);
PMF_Init::cleanRequest();

// Just for security reasons - thanks to Johannes for the hint
$_SERVER['PHP_SELF'] = str_replace('%2F', '/', rawurlencode($_SERVER['PHP_SELF']));
$_SERVER['HTTP_USER_AGENT'] = urlencode($_SERVER['HTTP_USER_AGENT']);

define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));

/* read configuration */
require_once (PMF_ROOT_DIR."/inc/data.php");
require_once (PMF_ROOT_DIR."/inc/config.php");
require_once (PMF_ROOT_DIR."/inc/constants.php");

/* include classes and functions */
require_once (PMF_ROOT_DIR."/inc/db.php");
define("SQLPREFIX", $DB["prefix"]);
$db = db::db_select($DB["type"]);
$db->connect($DB["server"], $DB["user"], $DB["password"], $DB["db"]);
require_once (PMF_ROOT_DIR."/inc/category.php");
require_once (PMF_ROOT_DIR."/inc/functions.php");
require_once (PMF_ROOT_DIR."/inc/idna_convert.class.php");
$IDN = new idna_convert;

/* get language (default: english) */
if ($PMF_CONF["detection"] && !isset($LANG) && isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
    require_once("../lang/language_".substr($_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 2).".php");
    $LANGCODE = substr($_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 2);
    }
elseif (isset($LANGCODE)) {
    require_once("../lang/language_".$LANGCODE.".php");
    }
elseif (!isset($LANGCODE)) {
    require_once ("../lang/language_en.php");
    $LANGCODE = "en";
    }

/* header of the admin page */
require_once ("header.php");
?>
</div>
<div id="bodyText">
<?php
if (isset($_GET["action"]) && $_GET["action"] == "newpassword") {
    
    }
elseif (isset($_GET["action"]) && $_GET["action"] == "savenewpassword") {
    
    }
elseif (isset($_GET["action"]) && $_GET["action"] == "sendmail") {
    if (isset($_POST["username"]) && $_POST["username"] != "" && isset($_POST["email"]) && $_POST["email"] != "" && checkEmail($_POST["email"])) {
        $username = $_POST["username"];
        $email = $_POST["email"];
        $num = $db->num_rows($db->query("SELECT name, email FROM ".SQLPREFIX."faquser WHERE name = '".$username."' AND email = '".$email."'"));
        if ($num == 1) {
            $consonants = array("b","c","d","f","g","h","j","k","l","m","n","p","r","s","t","v","w","x","y","z");
            $vowels = array("a","e","i","o","u");
            $newPassword = "";
            srand((double)microtime()*1000000);
            for ($i = 1; $i <= 4; $i++) {
                $newPassword .= $consonants[rand(0,19)];
                $newPassword .= $vowels[rand(0,4)];
                }
            $db->query("UPDATE ".SQLPREFIX."faquser SET pass = '".md5($newPassword)."' WHERE name = '".$username."' AND email = '".$email."'");
            $text = $PMF_LANG["lostpwd_text_1"]."\nUsername: ".$username."\nNew Password: ".$newPassword."\n\n".$PMF_LANG["lostpwd_text_2"];
            mail($IDN->encode($email), $PMF_CONF["title"].": username / password request", $text, "From: ".$IDN->encode($PMF_CONF["adminmail"]));
            print $PMF_LANG["lostpwd_mail_okay"];
            print "<p><img src=\"images/arrow.gif\" width=\"11\" height=\"11\" alt=\"".$PMF_LANG["ad"]."\" border=\"0\" /> <a href=\"index.php\" title=\"".$PMF_LANG["ad"]."\">".$PMF_LANG["ad"]."</a></p>";
            }
        else {
            print $PMF_LANG["lostpwd_err_1"];
            }
        }
    else {
        print $PMF_LANG["lostpwd_err_2"];
        }
    }
else {
?>
	<form action="<?php print $_SERVER["PHP_SELF"]; ?>?action=sendmail" method="post">
    <fieldset class="login">
        <legend class="login"><?php print $PMF_LANG["ad_passwd_cop"]; ?></legend>
        
        <div class="row"><span class="label"><strong><?php print $PMF_LANG["ad_auth_user"]; ?></strong></span>
        <input class="admin" type="text" name="username" size="30" /></div>
        <div class="row"><span class="label"><strong><?php print $PMF_LANG["ad_entry_email"]; ?></strong></span>
        <input class="admin" type="text"  name="email" size="30" /></div>
        <div class="row"><span class="label">&nbsp;</span>
        <input class="submit" type="submit" value="<?php print $PMF_LANG["msgNewContentSubmit"]; ?>" /></div>
        
        <p><img src="images/arrow.gif" width="11" height="11" alt="<?php print $PMF_LANG["ad_sess_back"]; ?> FAQ" border="0" /> <a href="index.php" title="<?php print $PMF_LANG["ad_sess_back"]; ?> FAQ"><?php print $PMF_LANG["ad_sess_back"]; ?></a></p>
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
