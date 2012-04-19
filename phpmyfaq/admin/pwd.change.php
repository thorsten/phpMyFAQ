<?php
/**
 * Form to change password of the current user
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
        $user = PMF_User_CurrentUser::getFromSession($faqConfig);

        // Define the (Local/Current) Authentication Source
        $auth = new PMF_Auth($faqConfig);
        $_authSource = $auth->selectAuth($user->getAuthSource('name'));
        $_authSource->selectEncType($user->getAuthData('encType'));
        $_authSource->setReadOnly($user->getAuthData('readOnly'));
        
        $opasswd = PMF_Filter::filterInput(INPUT_POST, 'opass', FILTER_SANITIZE_STRING);
        $npasswd = PMF_Filter::filterInput(INPUT_POST, 'npass', FILTER_SANITIZE_STRING);
        $bpasswd = PMF_Filter::filterInput(INPUT_POST, 'bpass', FILTER_SANITIZE_STRING);

        if (($_authSource->checkPassword($user->getLogin(), $opasswd)) && ($npasswd == $bpasswd)) {
            if (!$user->changePassword($npasswd)) {
                printf('<p class="alert alert-error">%s</p>', $PMF_LANG["ad_passwd_fail"]);
            }
            printf('<p class="alert alert-success">%s</p>', $PMF_LANG["ad_passwdsuc"]);
        } else {
            printf('<p class="alert alert-error">%s</p>', $PMF_LANG["ad_passwd_fail"]);
        }
    }
?>

        <form class="form-horizontal" action="?action=passwd" method="post">
        <input type="hidden" name="save" value="newpassword" />
            <div class="control-group">
                <label class="control-label" for="opass"><?php print $PMF_LANG["ad_passwd_old"]; ?></label>
                <div class="controls">
                    <input type="password" name="opass" id="opass" required="required" />
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="npass"><?php print $PMF_LANG["ad_passwd_new"]; ?></label>
                <div class="controls">
                    <input type="password" name="npass" id="npass" required="required" />
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="bpass"><?php print $PMF_LANG["ad_passwd_con"]; ?></label>
                <div class="controls">
                    <input type="password" name="bpass" id="bpass" required="required"  />
                </div>
            </div>

            <div class="form-actions">
                <input class="btn-primary" type="submit" value="<?php print $PMF_LANG["ad_passwd_change"]; ?>" />
            </div>
        </form>
<?php
} else {
    print $PMF_LANG["err_NotAuth"];
}