<?php
/**
 * The login form.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Alexander M. Turek <me@derrabus.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2013-02-05
 */

if (isset($error) && 0 < strlen($error)) {
    $message = sprintf(
        '<p class="alert alert-danger alert-dismissible fade show">%s%s</p>',
        '<button type="button" class="close" data-dismiss="alert">'.
        '<span aria-hidden="true">&times;</span>'.
        '</button>',
        $error
    );
} else {
    $message = sprintf('<p>%s</p>', $PMF_LANG['ad_auth_insert']);
}
if ($action == 'logout') {
    $message = sprintf(
        '<p class="alert alert-success alert-dismissible fade show">%s%s</p>',
        '<button type="button" class="close" data-dismiss="alert">'.
        '<span aria-hidden="true">&times;</span>'.
        '</button>',
        $PMF_LANG['ad_logout']
    );
}
if ((isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') || !$faqConfig->get('security.useSslForLogins')) {
?>
      <form action="<?= $faqSystem->getSystemUri($faqConfig) ?>admin/index.php" method="post"
            accept-charset="utf-8" role="form" class="pmf-form-login">
        <input type="hidden" name="redirect-action" value="<?= $action ?>">
        <h1 class="h3 mb-3 font-weight-normal">phpMyFAQ Login</h1>
          <?= $message ?>
        <label for="faqusername" class="sr-only"><?= $PMF_LANG['ad_auth_user'] ?></label>
        <input type="text" id="faqusername" name="faqusername" class="form-control"
               placeholder="<?= $PMF_LANG['ad_auth_user'] ?>" required autofocus>
        <label for="faqpassword" class="sr-only"><?= $PMF_LANG['ad_auth_passwd'] ?></label>
        <input type="password" id="faqpassword" name="faqpassword" class="form-control"
               placeholder="<?= $PMF_LANG['ad_auth_passwd'] ?>" required>
        <div class="checkbox mb-3">
          <label>
            <input type="checkbox" id="faqrememberme" name="faqrememberme" value="rememberMe"> <?= $PMF_LANG['rememberMe'] ?>
          </label>
        </div>
        <button class="btn btn-lg btn-primary btn-block" type="submit">
            <?= $PMF_LANG['msgLoginUser'] ?>
        </button>

        <div class="form-group mb-3">
          <p>
            <a href="../?action=password">
                <?= $PMF_LANG['lostPassword'] ?>
            </a>
              <?php if ($faqConfig->get('security.enableRegistration')) { ?>
                <br>
                <a href="../?action=register">
                    <?= $PMF_LANG['msgRegistration'] ?>
                </a>
              <?php } ?>
          </p>
        </div>
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
