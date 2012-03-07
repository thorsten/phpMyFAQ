<?php
/**
 * Reset a forgotten password to a new one.
 *
 * @todo: Move this to the frontend, check #300
 *
 * PHP Version 5.2.3
 *

 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administraion
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2004-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2004-05-11
 */

define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));

//
// Check if config/database.php exist -> if not, redirect to installer
//
if (!file_exists(PMF_ROOT_DIR . '/config/database.php')) {
    header("Location: ".str_replace('admin/index.php', '', $_SERVER['SCRIPT_NAME'])."install/setup.php");
    exit();
}

//
// Define the named constant used as a check by any included PHP file
//
define('IS_VALID_PHPMYFAQ', null);

//
// Autoload classes, prepend and start the PHP session
//
require_once PMF_ROOT_DIR.'/inc/Bootstrap.php';
PMF_Init::cleanRequest();
session_name(PMF_COOKIE_NAME_AUTH.trim($faqConfig->get('main.phpMyFAQToken')));
session_start();

//
// get language (default: english)
//
$Language = new PMF_Language();
$LANGCODE = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));

// Preload English strings
require_once PMF_ROOT_DIR.'/lang/language_en.php';

if (isset($LANGCODE) && PMF_Language::isASupportedLanguage($LANGCODE)) {
    // Overwrite English strings with the ones we have in the current language
    require_once PMF_ROOT_DIR.'/lang/language_'.$LANGCODE.'.php';
} else {
    $LANGCODE = 'en';
}

//
// Initalizing static string wrapper
//
PMF_String::init($LANGCODE);

/* header of the admin page */
$permission = array();
$action     = PMF_Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_STRING);

require 'header.php';

$message = '';

if ($action == "sendmail") {

    $username = PMF_Filter::filterInput(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email    = PMF_Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    
    if (!is_null($username) && !is_null($email)) {

        $user       = new PMF_User_CurrentUser();
        $loginExist = $user->getUserByLogin($username);

        if ($loginExist && ($email == $user->getUserData('email'))) {
            $consonants = array(
                'b','c','d','f','g','h','j','k','l','m','n','p','r','s','t','v','w','x','y','z'
            );
            $vowels = array(
                'a','e','i','o','u'
            );
            $newPassword = '';
            srand((double)microtime()*1000000);
            for ($i = 1; $i <= 4; $i++) {
                $newPassword .= $consonants[rand(0,19)];
                $newPassword .= $vowels[rand(0,4)];
            }
            $user->changePassword($newPassword);
            $text = $PMF_LANG['lostpwd_text_1']."\nUsername: ".$username."\nNew Password: ".$newPassword."\n\n".$PMF_LANG["lostpwd_text_2"];

            $mail = new PMF_Mail();
            $mail->addTo($email);
            $mail->subject = '[%sitename%] Username / password request';
            $mail->message = $text;
            $result = $mail->send();
            unset($mail);
            // Trust that the email has been sent
            $message = sprintf('<p class="alert alert-success">%s</p>', $PMF_LANG["lostpwd_mail_okay"]);
            print "<p><img src=\"images/arrow.gif\" width=\"11\" height=\"11\" alt=\"".$PMF_LANG["ad"]."\" border=\"0\" /> <a href=\"index.php\" title=\"".$PMF_LANG["ad"]."\">".$PMF_LANG["ad"]."</a></p>";
        } else {
            $message = sprintf('<p class="alert alert-error">%s</p>', $PMF_LANG["lostpwd_err_1"]);
        }
    } else {
        $message = sprintf('<p class="alert alert-error">%s</p>', $PMF_LANG["lostpwd_err_2"]);
    }
}
?>

            <header>
                <h2><?php print $PMF_LANG["ad_passwd_cop"]; ?></h2>
            </header>

            <?php print $message ?>

            <form action="?action=sendmail" method="post" class="form-horizontal">

                <div class="control-group">
                    <label class="control-label"><?php print $PMF_LANG["ad_auth_user"]; ?></label>
                    <div class="controls">
                        <input type="text" name="username" required="required" autofocus="autofocus" />
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label"><?php print $PMF_LANG["ad_entry_email"]; ?></label>
                    <div class="controls">
                        <input type="email" name="email" required="required" />
                    </div>
                </div>

                <div class="form-actions">
                    <input class="btn-primary" type="submit" value="<?php print $PMF_LANG["msgNewContentSubmit"]; ?>" />
                </div>
            </form>
            <p>
                <img src="images/arrow.gif" width="11" height="11" alt="<?php print $PMF_LANG["ad_sess_back"]; ?> FAQ" border="0" />
                <a href="index.php" title="<?php print $PMF_LANG["ad_sess_back"]; ?> FAQ">
                    <?php print $PMF_LANG["ad_sess_back"]; ?>
                </a>
            </p>
            <p>
                <img src="images/arrow.gif" width="11" height="11" alt="<?php print $faqConfig->get('main.titleFAQ'); ?> FAQ" border="0" />
                <a href="../index.php" title="<?php print $faqConfig->get('main.titleFAQ'); ?> FAQ">
                    <?php print $faqConfig->get('main.titleFAQ'); ?>
                </a>
            </p>
        </section>

<?php
require 'footer.php';
$db->close();