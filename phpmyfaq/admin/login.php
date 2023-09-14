<?php

/**
 * The login form.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Alexander M. Turek <me@derrabus.de>
 * @copyright 2013-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2013-02-05
 */

use phpMyFAQ\Component\Alert;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;

if (isset($error) && 0 < strlen((string) $error)) {
    $message = sprintf(
        '<div class="alert alert-danger alert-dismissible fade show" role="alert">%s' .
        '  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' .
        '</div>',
        $error
    );
} else {
    $message = sprintf('<p>%s</p>', Translation::get('ad_auth_insert'));
}

$request = Request::createFromGlobals();

if ($request->query->get('action') === 'logout') {
    $message = Alert::success('ad_logout');
}

if ($request->isSecure() || !$faqConfig->get('security.useSslForLogins')) {
    ?>

    <div id="pmf-admin-login">
        <div id="pmf-admin-login-content">
            <main>
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-5">
                            <div class="card shadow-lg border-0 rounded-lg mt-5">
                                <div class="card-header">
                                    <h3 class="text-center font-weight-light my-4">phpMyFAQ Login</h3>
                                    <?= $message ?>
                                </div>
                                <div class="card-body">
                                    <form action="<?= $faqSystem->getSystemUri($faqConfig) ?>admin/index.php"
                                          method="post" accept-charset="utf-8" role="form">
                                        <input type="hidden" name="redirect-action"
                                               value="<?= $request->query->get('action') ?>">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="faqusername" name="faqusername" type="text"
                                                   placeholder="<?= Translation::get('ad_auth_user') ?>" />
                                            <label for="faqusername"><?= Translation::get('ad_auth_user') ?></label>
                                        </div>
                                        <div class="input-group mb-3">
                                            <div class="form-floating">
                                                <input class="form-control" id="faqpassword" name="faqpassword"
                                                       type="password" autocomplete="off"
                                                       placeholder="<?= Translation::get('ad_auth_passwd') ?>" />
                                                <label for="faqpassword">
                                                    <?= Translation::get('ad_auth_passwd') ?>
                                                </label>
                                            </div>
                                            <span class="input-group-text">
                                                <i class="fa" id="togglePassword"></i>
                                            </span>
                                        </div>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" id="faqrememberme" name="faqrememberme" type="checkbox"
                                                   value="rememberMe" />
                                            <label class="form-check-label"
                                                   for="faqrememberme"><?= Translation::get('rememberMe') ?></label>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between mt-4 mb-0">
                                            <a class="small"
                                               href="../?action=password"><?= Translation::get('lostPassword') ?></a>
                                            <button type="submit" class="btn btn-primary">
                                                <?= Translation::get('msgLoginUser') ?>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                <?php
                                if ($faqConfig->get('security.enableRegistration')) { ?>
                                    <div class="card-footer text-center py-3">
                                        <a class="w-100 py-2 mb-2 btn btn-outline-primary rounded-3"
                                           href="../?action=register">
                                            <?= Translation::get('msgRegistration') ?>
                                        </a>
                                        <?php if ($faqConfig->isSignInWithMicrosoftActive()) { ?>
                                        <a class="w-100 py-2 mb-2 btn btn-outline-secondary rounded-3"
                                           href="../services/azure">
                                            <i class="fa fa-windows" aria-hidden="true"></i>
                                            <?= Translation::get('msgSignInWithMicrosoft') ?>
                                        </a>
                                        <?php } ?>
                                    </div>
                                    <?php
                                } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
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
