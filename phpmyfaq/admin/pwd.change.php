<?php
/**
 * Form to change password of the current user.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-23
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header"><i aria-hidden="true" class="fa fa-lock fa-fw"></i> <?php echo $PMF_LANG['ad_passwd_cop']; ?></h2>
            </div>
        </header>
<?php
if ($user->perm->checkRight($user->getUserId(), 'passwd')) {

    // If we have to save a new password, do that first
    $save = PMF_Filter::filterInput(INPUT_POST, 'save', FILTER_SANITIZE_STRING);
    $csrfToken = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);

    if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
        $csrfCheck = false;
    } else {
        $csrfCheck = true;
    }

    if (!is_null($save) && $csrfCheck) {

        // Define the (Local/Current) Authentication Source
        $auth = new PMF_Auth($faqConfig);
        $authSource = $auth->selectAuth($user->getAuthSource('name'));
        $authSource->selectEncType($user->getAuthData('encType'));
        $authSource->setReadOnly($user->getAuthData('readOnly'));

        $oldPassword = PMF_Filter::filterInput(INPUT_POST, 'opass', FILTER_SANITIZE_STRING);
        $newPassword = PMF_Filter::filterInput(INPUT_POST, 'npass', FILTER_SANITIZE_STRING);
        $retypedPassword = PMF_Filter::filterInput(INPUT_POST, 'bpass', FILTER_SANITIZE_STRING);

        if (($authSource->checkPassword($user->getLogin(), $oldPassword)) && ($newPassword == $retypedPassword)) {
            if (!$user->changePassword($newPassword)) {
                printf(
                    '<p class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>%s</p>',
                    $PMF_LANG['ad_passwd_fail']
                );
            }
            printf(
                '<p class="alert alert-success"><button type="button" class="close" data-dismiss="alert">&times;</button>%s</p>',
                $PMF_LANG['ad_passwdsuc']
            );
        } else {
            printf(
                '<p class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>%s</p>',
                $PMF_LANG['ad_passwd_fail']
            );
        }
    }
    ?>
        <div class="row">
            <div class="col-lg-12">
                <form class="form-horizontal" action="?action=passwd" method="post" accept-charset="utf-8">
                    <input type="hidden" name="csrf" value="<?php echo $user->getCsrfTokenFromSession() ?>">
                    <input type="hidden" name="save" value="newpassword">
                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="opass">
                            <?php echo $PMF_LANG['ad_passwd_old'];
    ?>
                        </label>
                        <div class="col-lg-3">
                            <input type="password" name="opass" id="opass" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="npass">
                            <?php echo $PMF_LANG['ad_passwd_new'];
    ?>
                        </label>
                        <div class="col-lg-3">
                            <input type="password" name="npass" id="npass" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="bpass">
                            <?php echo $PMF_LANG['ad_passwd_con'];
    ?>
                        </label>
                        <div class="col-lg-3">
                            <input type="password" name="bpass" id="bpass" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-3">
                            <button class="btn btn-primary" type="submit">
                                <?php echo $PMF_LANG['ad_passwd_change'];
    ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
<?php

} else {
    echo $PMF_LANG['err_NotAuth'];
}
