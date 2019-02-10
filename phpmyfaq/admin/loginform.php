<?php
/**
 * The login form.
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
 * @author    Alexander M. Turek <me@derrabus.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2013-02-05
 */
?>
        <div class="row">
            <div class="col-md-4 col-md-offset-4">
                <div class="login-panel panel panel-default">
                    <div class="panel-heading">
                        <header>
                            <h3 class="panel-title">phpMyFAQ Login</h3>
                        </header>
                    </div>
                    <div class="panel-body">
<?php
if (isset($error) && 0 < strlen($error)) {
    $message = sprintf(
        '<p class="alert alert-danger">%s%s</p>',
        '<a class="close" data-dismiss="alert" href="#">&times;</a>',
        $error
    );
} else {
    $message = sprintf('<p>%s</p>', $PMF_LANG['ad_auth_insert']);
}
if ($action == 'logout') {
    $message = sprintf(
        '<p class="alert alert-success">%s%s</p>',
        '<a class="close" data-dismiss="alert" href="#">&times;</a>',
        $PMF_LANG['ad_logout']
    );
}

if ((isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') || !$faqConfig->get('security.useSslForLogins')) {
    ?>

                        <?php echo $message ?>

                        <form action="<?php echo $faqSystem->getSystemUri($faqConfig) ?>admin/index.php" method="post"
                              accept-charset="utf-8" role="form">
                            <fieldset>

                                <div class="form-group">
                                    <input type="text" name="faqusername" id="faqusername" class="form-control input-lg"
                                           placeholder="<?php echo $PMF_LANG['ad_auth_user'] ?>" required>
                                </div>

                                <div class="form-group">
                                    <input type="password" name="faqpassword" id="faqpassword"
                                           class="form-control input-lg" placeholder="<?php echo $PMF_LANG['ad_auth_passwd'] ?>"
                                           required>
                                </div>

                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" id="faqrememberme" name="faqrememberme" value="rememberMe">
                                        <?php echo $PMF_LANG['rememberMe'] ?>
                                    </label>
                                </div>

                                <div class="form-group">
                                    <button class="btn btn-lg btn-primary btn-block" type="submit">
                                        <?php echo $PMF_LANG['msgLoginUser'] ?>
                                    </button>
                                </div>

                                <div class="form-group">
                                    <p class="pull-right">
                                        <a href="../?action=password">
                                            <?php echo $PMF_LANG['lostPassword'] ?>
                                        </a>
                                        <?php if ($faqConfig->get('security.enableRegistration')) { ?>
                                        <br>
                                        <a href="../?action=register">
                                            <?php echo $PMF_LANG['msgRegistration'] ?>
                                        </a>
                                        <?php } ?>
                                    </p>
                                </div>
                            </fieldset>
<?php

} else {
    printf(
        '<p><a href="https://%s%s">%s</a></p>',
        $_SERVER['HTTP_HOST'],
        $_SERVER['REQUEST_URI'],
        $PMF_LANG['msgSecureSwitch']);
}
?>
                        </form>
                    </div>
                </div>
