<?php
/**
 * Form to change password of the current user
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
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-23
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}
?>

        <header>
            <h2><?php print $PMF_LANG['ad_passwd_cop']; ?></h2>
        </header>

<?php
if ($permission["passwd"]) {
    
    // If we have to save a new password, do that first
    $save = PMF_Filter::filterInput(INPUT_POST, 'save', FILTER_SANITIZE_STRING);
    if (!is_null($save)) {

        // Re-evaluate $user
        $user = PMF_User_CurrentUser::getFromSession($faqconfig->get('main.ipCheck'));

        // Define the (Local/Current) Authentication Source
        $_authSource = PMF_Auth::selectAuth($user->auth_data['authSource']['name']);
        $_authSource->selectEncType($user->auth_data['encType']);
        $_authSource->setReadOnly($user->auth_data['readOnly']);
        
        $opasswd = PMF_Filter::filterInput(INPUT_POST, 'opass', FILTER_SANITIZE_STRING);
        $npasswd = PMF_Filter::filterInput(INPUT_POST, 'npass', FILTER_SANITIZE_STRING);
        $bpasswd = PMF_Filter::filterInput(INPUT_POST, 'bpass', FILTER_SANITIZE_STRING);
        
        if (($_authSource->encrypt($opasswd) == $user->encrypted_password) && ($npasswd == $bpasswd)) {
            if (!$user->changePassword($npasswd)) {
                printf('<p class="error">%s</p>', $PMF_LANG["ad_passwd_fail"]);
            }
            printf('<p class="success">%s</p>', $PMF_LANG["ad_passwdsuc"]);
        } else {
            printf('<p class="error">%s</p>', $PMF_LANG["ad_passwd_fail"]);
        }
    }
?>

        <form action="?action=passwd" method="post">
        <input type="hidden" name="save" value="newpassword" />
            <p>
                <label for="opass"><?php print $PMF_LANG["ad_passwd_old"]; ?></label>
                <input type="password" name="opass" id="opass" size="30" />
            </p>

            <p>
                <label for="npass"><?php print $PMF_LANG["ad_passwd_new"]; ?></label>
                <input type="password" name="npass" id="npass" size="30" />
            </p>

            <p>
                <label for="bpass"><?php print $PMF_LANG["ad_passwd_con"]; ?></label>
                <input type="password" name="bpass" id="bpass" size="30" />
            </p>

            <p>
                <input class="submit" type="submit" value="<?php print $PMF_LANG["ad_passwd_change"]; ?>" />
            </p>
        </form>
<?php
} else {
    print $PMF_LANG["err_NotAuth"];
}