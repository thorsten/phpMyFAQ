<?php
/**
 * Form to change password of the current user
 *
 * @package    phpMyFAQ
 * @subpackage Administration
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2003-02-23
 * @copyright  2003-2009 phpMyFAQ Team
 * @version    SVN: $Id$
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

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

printf('<h2>%s</h2>', $PMF_LANG['ad_passwd_cop']);

if ($permission["passwd"]) {
	
	// If we have to save a new password, do that first
	$save = PMF_Filter::filterInput(INPUT_POST, 'save', FILTER_SANITIZE_STRING);
	if (!is_null($save)) {
		
		// Re-evaluate $user
        $user = PMF_User_CurrentUser::getFromSession($faqconfig->get('main.ipCheck'));
		
        // Define the (Local/Current) Authentication Source
        $_authSource = PMF_User_Auth::selectAuth($user->auth_data['authSource']['name']);
        $_authSource->selectEncType($user->auth_data['encType']);
        $_authSource->read_only($user->auth_data['readOnly']);
        
        $opasswd = PMF_Filter::filterInput(INPUT_POST, 'opass', FILTER_SANITIZE_STRING);
        $npasswd = PMF_Filter::filterInput(INPUT_POST, 'npass', FILTER_SANITIZE_STRING);
        $bpasswd = PMF_Filter::filterInput(INPUT_POST, 'bpass', FILTER_SANITIZE_STRING);
        
	   if (($_authSource->encrypt($opasswd) == $user->encrypted_password) && ($npasswd == $bpasswd)) {
            if (!$user->changePassword($npasswd)) {
                print $PMF_LANG["ad_passwd_fail"]."<br />";
            }
    
            print $PMF_LANG["ad_passwdsuc"]."<br />";

            // TODO: Manage the 'Rembember me' Cookie also under 2.0.0.
            if (isset($_COOKIE['cuser'])) {
                if ($_COOKIE["cuser"] == $user) {
                    print $PMF_LANG["ad_passwd_remark"]."<br /><a href=\"?action=setcookie\">".$PMF_LANG["ad_cookie_set"]."</a>\n";
                }
            }
        } else {
        print $PMF_LANG["ad_passwd_fail"];
        }
	}
?>
    <form action="?action=passwd" method="post">
    <fieldset>
    <legend><?php print $PMF_LANG["ad_passwd_cop"]; ?></legend>
    <input type="hidden" name="save" value="newpassword" />

    <label class="left" for="opass"><?php print $PMF_LANG["ad_passwd_old"]; ?></label>
    <input type="password" name="opass" size="30" /><br />

    <label class="left" for="npass"><?php print $PMF_LANG["ad_passwd_new"]; ?></label>
    <input type="password" name="npass" size="30" /><br />

    <label class="left" for="bpass"><?php print $PMF_LANG["ad_passwd_con"]; ?></label>
    <input type="password" name="bpass" size="30" /><br />

    <input class="submit" style="margin-left: 190px;" type="submit" value="<?php print $PMF_LANG["ad_passwd_change"]; ?>" /></div>
    </fieldset>
    </form>
<?php
} else {
    print $PMF_LANG["err_NotAuth"];
}