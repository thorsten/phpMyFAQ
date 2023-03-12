<?php

/**
 * The 2fa-form for entering the token for two-factor-authentification.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Alexander M. Turek <me@derrabus.de>
 * @author Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2005-2023 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2023-03-11
 */

if (isset($error) && 0 < strlen($error)) {
    $message = sprintf(
        '<p class="alert alert-danger alert-dismissible fade show mt-3">%s%s</p>',
        '<button type="button" class="close" data-dismiss="alert">' .
        '<span aria-hidden="true">&times;</span>' .
        '</button>',
        $error
    );
} else {
    $message = sprintf('<p>%s</p>', $PMF_LANG['ad_auth_insert']);
}
if ($action == 'logout') {
    $message = sprintf(
        '<p class="alert alert-success alert-dismissible fade show mt-3">%s%s</p>',
        '<button type="button" class="close" data-dismiss="alert">' .
        '<span aria-hidden="true">&times;</span>' .
        '</button>',
        $PMF_LANG['ad_logout']
    );
}
if ((isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') || !$faqConfig->get('security.useSslForLogins')) {
    ?>

<div class="container py-5">
  <div class="row">
    <div class="col-lg-12">
      <div class="row">
        <div class="col-lg-6 mx-auto">
          <div class="card rounded-0" id="login-form">
            <div class="card-header">
              <h3 class="mb-0"><?= $PMF_LANG['msgTwofactorEnabled'] ?></h3>
                <?= $message ?>
            </div>
            <div class="card-body">
              <form action="<?= $faqSystem->getSystemUri($faqConfig) ?>admin/index.php" method="post"
                    accept-charset="utf-8" role="form" class="pmf-form-login">
                <input type="hidden" name="userid" id="userid" value="<?= $userid ?>">
                <input type="hidden" name="redirect-action" value="<?= $action ?>">
                <div class="form-group">
                  <label for="faqusername"><?= $PMF_LANG['msgEnterTwofactorToken'] ?></label>
                  <input type="text" class="form-control form-control-lg rounded-0" name="token" id="token"
                         required>
                </div>
                <button type="submit" class="btn btn-success btn-lg float-right" id="btnLogin">
                    <?= $PMF_LANG['msgTwofactorCheck'] ?>
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

    <?php
} else {
    printf(
        '<p><a href="https://%s%s">%s</a></p>',
        $_SERVER['HTTP_HOST'],
        $_SERVER['REQUEST_URI'],
        $PMF_LANG['msgSecureSwitch']
    );
}
