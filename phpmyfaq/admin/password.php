<?php
/**
* $Id: password.php,v 1.18 2007-03-29 15:57:53 thorstenr Exp $
*
* Reset a forgotten password to a new one
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2004-05-11
* @copyright    (c) 2004-2006 phpMyFAQ Team
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

//
// Check if data.php exist -> if not, redirect to installer
//
if (!file_exists('../inc/data.php')) {
    header("Location: ".str_replace('admin/index.php', '', $_SERVER["PHP_SELF"])."install/installer.php");
    exit();
}

//
// Prepend
//
define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));
define('IS_VALID_PHPMYFAQ_ADMIN', null);
require_once(PMF_ROOT_DIR.'/inc/Init.php');
PMF_Init::cleanRequest();
session_name('pmf_auth_'.$faqconfig->get('phpMyFAQToken'));
session_start();

require_once(PMF_ROOT_DIR.'/inc/PMF_User/CurrentUser.php');
require_once(PMF_ROOT_DIR.'/inc/libs/idna_convert.class.php');
$user = new PMF_CurrentUser();
$IDN = new idna_convert;

// get language (default: english)
$pmf = new PMF_Init();
$LANGCODE = $pmf->setLanguage((isset($PMF_CONF['main.languageDetection']) ? true : false), $PMF_CONF['main.language']);
// Preload English strings
require_once ('../lang/language_en.php');

if (isset($LANGCODE) && PMF_Init::isASupportedLanguage($LANGCODE)) {
    // Overwrite English strings with the ones we have in the current language
    require_once('../lang/language_'.$LANGCODE.'.php');
} else {
    $LANGCODE = 'en';
}

/* header of the admin page */
require_once ("header.php");
?>
</div>
<div id="bodyText">
<?php
if (isset($_GET["action"]) && $_GET["action"] == "newpassword") {

    } elseif (isset($_GET["action"]) && $_GET["action"] == "savenewpassword") {

    } elseif (isset($_GET["action"]) && $_GET["action"] == "sendmail") {
        if (    isset($_POST["username"]) && $_POST["username"] != ""
             && isset($_POST["email"]) && $_POST["email"] != "" && checkEmail($_POST["email"])
            ) {
            $username   = $db->escape_string($_POST["username"]);
            $email      = $db->escape_string($_POST["email"]);
            $loginExist = $user->getUserByLogin($username);
            if ($loginExist && ($_POST["email"] == $user->getUserData('email'))) {
                $consonants = array("b","c","d","f","g","h","j","k","l","m","n","p","r","s","t","v","w","x","y","z");
                $vowels = array("a","e","i","o","u");
                $newPassword = "";
                srand((double)microtime()*1000000);
                for ($i = 1; $i <= 4; $i++) {
                    $newPassword .= $consonants[rand(0,19)];
                    $newPassword .= $vowels[rand(0,4)];
                }
                $user->changePassword($newPassword);
                $text = $PMF_LANG["lostpwd_text_1"]."\nUsername: ".$username."\nNew Password: ".$newPassword."\n\n".$PMF_LANG["lostpwd_text_2"];
                mail($IDN->encode($email), $PMF_CONF["title"].": username / password request", $text, "From: ".$IDN->encode($PMF_CONF['main.administrationMail']));
                print $PMF_LANG["lostpwd_mail_okay"];
                print "<p><img src=\"images/arrow.gif\" width=\"11\" height=\"11\" alt=\"".$PMF_LANG["ad"]."\" border=\"0\" /> <a href=\"index.php\" title=\"".$PMF_LANG["ad"]."\">".$PMF_LANG["ad"]."</a></p>";
            } else {
                print $PMF_LANG["lostpwd_err_1"];
            }
        } else {
            print $PMF_LANG["lostpwd_err_2"];
        }
    } else {
?>
    <form action="<?php print $_SERVER["PHP_SELF"]; ?>?action=sendmail" method="post">
    <fieldset class="login">
        <legend class="login"><?php print $PMF_LANG["ad_passwd_cop"]; ?></legend>

        <label class="left"><?php print $PMF_LANG["ad_auth_user"]; ?></label>
        <input type="text" name="username" size="30" /><br />

        <label class="left"><?php print $PMF_LANG["ad_entry_email"]; ?></label>
        <input type="text"  name="email" size="30" /><br />

        <input class="submit" style="margin-left: 190px;" type="submit" value="<?php print $PMF_LANG["msgNewContentSubmit"]; ?>" />

        <p><img src="images/arrow.gif" width="11" height="11" alt="<?php print $PMF_LANG["ad_sess_back"]; ?> FAQ" border="0" /> <a href="index.php" title="<?php print $PMF_LANG["ad_sess_back"]; ?> FAQ"><?php print $PMF_LANG["ad_sess_back"]; ?></a></p>
        <p><img src="images/arrow.gif" width="11" height="11" alt="<?php print PMF_htmlentities($PMF_CONF["title"], ENT_QUOTES, $PMF_LANG['metaCharset']); ?> FAQ" border="0" /> <a href="../index.php" title="<?php print PMF_htmlentities($PMF_CONF["title"], ENT_QUOTES, $PMF_LANG['metaCharset']); ?> FAQ"><?php print PMF_htmlentities($PMF_CONF["title"], ENT_QUOTES, $PMF_LANG['metaCharset']); ?> FAQ</a></p>

    </fieldset>
    </form>
<?php
}

if (DEBUG) {
    print "<p>DEBUG INFORMATION:</p>\n";
    print "<p>".$db->sqllog()."</p>";
}

require_once ("footer.php");
$db->dbclose();
?>
