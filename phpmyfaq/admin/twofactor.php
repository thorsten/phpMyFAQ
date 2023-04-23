<?php

/**
 * The 2fa-form for entering the token for two-factor-authentication.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-03-11
 */

use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;

$request = Request::createFromGlobals();

if (isset($error) && 0 < strlen((string) $error)) {
    $message = sprintf(
        '<p class="alert alert-danger alert-dismissible fade show mt-3">%s%s</p>',
        '<button type="button" class="close" data-dismiss="alert">' .
        '<span aria-hidden="true">&times;</span>' .
        '</button>',
        $error
    );
} else {
    $message = '';
}

if ($request->query->get('action') === 'logout') {
    $message = sprintf(
        '<p class="alert alert-success alert-dismissible fade show mt-3">%s%s</p>',
        '<button type="button" class="close" data-dismiss="alert">' .
        '<span aria-hidden="true">&times;</span>' .
        '</button>',
        Translation::get('ad_logout')
    );
}

if ($request->isSecure() || !$faqConfig->get('security.useSslForLogins')) {
?>

<div class="container py-5">
  <div class="row">
    <div class="col-lg-12">
      <div class="row">
        <div class="col-lg-6 mx-auto">
          <div class="card rounded-0" id="login-form">
            <div class="card-header">
              <h3 class="mb-0">
                  <?= Translation::get('msgTwofactorEnabled') ?>
              </h3>

              <?= $message ?>

            </div>
            <div class="card-body">
              <form action="<?= $faqSystem->getSystemUri($faqConfig) ?>admin/index.php" method="post"
                    accept-charset="utf-8" role="form" class="pmf-form-login">
                <input type="hidden" name="userid" id="userid" value="<?= $userid ?>">
                <input type="hidden" name="redirect-action" value="<?= $action ?>">
                <div class="form-group">
                  <label for="token"><?= Translation::get('msgEnterTwofactorToken') ?></label>
                  <div class="col-4 mx-auto my-2">
                    <input type="text" class="form-control form-control-lg text-center rounded-0" name="token"
                           id="token" autocomplete="off" maxlength="6" autofocus required>
                  </div>
                </div>
                <div class="d-grid gap-2 col-6 mx-auto">
                  <button type="submit" class="btn btn-success btn-lg float-right" id="btnLogin">
                    <?= Translation::get('msgTwofactorCheck') ?>
                  </button>
                </div>
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
        $request->getHost(),
        $request->getRequestUri(),
        Translation::get('msgSecureSwitch')
    );
}
