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
 * @copyright 2003-2015 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-23
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON'){
        $protocol = 'https';
    }
    header('Location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

?>
        <header>
            <h2><i class="icon-lock"></i> <?php echo $PMF_LANG['ad_passwd_cop']; ?></h2>
        </header>
<?php
if ($permission["passwd"]) {
    
    // If we have to save a new password, do that first
    $save = PMF_Filter::filterInput(INPUT_POST, 'save', FILTER_SANITIZE_STRING);
    if (!is_null($save)) {

        // Define the (Local/Current) Authentication Source
        $auth       = new PMF_Auth($faqConfig);
        $authSource = $auth->selectAuth($user->getAuthSource('name'));
        $authSource->selectEncType($user->getAuthData('encType'));
        $authSource->setReadOnly($user->getAuthData('readOnly'));
        
        $oldPassword     = PMF_Filter::filterInput(INPUT_POST, 'opass', FILTER_SANITIZE_STRING);
        $newPassword     = PMF_Filter::filterInput(INPUT_POST, 'npass', FILTER_SANITIZE_STRING);
        $retypedPassword = PMF_Filter::filterInput(INPUT_POST, 'bpass', FILTER_SANITIZE_STRING);

        if (($authSource->checkPassword($user->getLogin(), $oldPassword)) && ($newPassword == $retypedPassword)) {
            if (!$user->changePassword($newPassword)) {
                printf('<p class="alert alert-error">%s</p>', $PMF_LANG["ad_passwd_fail"]);
            }
            printf('<p class="alert alert-success">%s</p>', $PMF_LANG["ad_passwdsuc"]);
        } else {
            printf('<p class="alert alert-error">%s</p>', $PMF_LANG["ad_passwd_fail"]);
        }
    }
?>
        <form class="form-horizontal" action="?action=passwd" method="post" accept-charset="utf-8">
        <input type="hidden" name="save" value="newpassword" />
            <div class="control-group">
                <label class="control-label" for="opass"><?php echo $PMF_LANG["ad_passwd_old"]; ?></label>
                <div class="controls">
                    <input type="password" name="opass" id="opass" required="required" />
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="npass"><?php echo $PMF_LANG["ad_passwd_new"]; ?></label>
                <div class="controls">
                    <input type="password" name="npass" id="npass" required="required" />
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="bpass"><?php echo $PMF_LANG["ad_passwd_con"]; ?></label>
                <div class="controls">
                    <input type="password" name="bpass" id="bpass" required="required"  />
                </div>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit">
                    <?php echo $PMF_LANG["ad_passwd_change"]; ?>
                </button>
            </div>
        </form>
<?php
} else {
    echo $PMF_LANG["err_NotAuth"];
}