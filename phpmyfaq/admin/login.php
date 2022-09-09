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
 * @copyright 2013-2022 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2013-02-05
 */

use phpMyFAQ\Component\Alert;
use phpMyFAQ\Translation;

if (isset($error) && 0 < strlen($error)) {
    $message = sprintf(
        '<div class="alert alert-danger alert-dismissible fade show" role="alert">%s' .
        '  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' .
        '</div>',
        $error
    );
} else {
    $message = sprintf('<p>%s</p>', Translation::get('ad_auth_insert'));
}
if ($action === 'logout') {
    $message = Alert::success('ad_logout');
}
if ((isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') || !$faqConfig->get(
        'security.useSslForLogins'
    )) {
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
                                          method="post"
                                          accept-charset="utf-8" role="form">
                                        <input type="hidden" name="redirect-action" value="<?= $action ?>">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="faqusername" name="faqusername" type="text"
                                                   placeholder="<?= Translation::get('ad_auth_user') ?>" />
                                            <label for="faqusername"><?= Translation::get('ad_auth_user') ?></label>
                                        </div>
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="faqpassword" name="faqpassword"
                                                   type="password" autocomplete="off"
                                                   placeholder="<?= Translation::get('ad_auth_passwd') ?>" />
                                            <label for="faqpassword"><?= Translation::get('ad_auth_passwd') ?></label>
                                        </div>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" id="faqrememberme" type="checkbox"
                                                   value="rememberMe" />
                                            <label class="form-check-label"
                                                   for="faqrememberme"><?= Translation::get('rememberMe') ?></label>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between mt-4 mb-0">
                                            <a class="small"
                                               href="../?action=password"><?= Translation::get('lostPassword') ?></a>
                                            <button type="submit"
                                                    class="btn btn-primary"><?= Translation::get('msgLoginUser') ?></button>
                                        </div>
                                    </form>
                                </div>
                                <?php
                                if ($faqConfig->get('security.enableRegistration')) { ?>
                                    <div class="card-footer text-center py-3">
                                        <div class="small">
                                            <a href="../?action=register"><?= Translation::get('msgRegistration') ?></a>
                                        </div>
                                        <hr>
                                        <div class="small">
                                            <a href="../services/azure">Login via Azure</a>
                                        </div>
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
        $_SERVER['HTTP_HOST'],
        $_SERVER['REQUEST_URI'],
        Translation::get('msgSecureSwitch')
    );
}
