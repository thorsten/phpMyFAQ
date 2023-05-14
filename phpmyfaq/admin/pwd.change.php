<?php

/**
 * Form to change the password of the current user.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-23
 */

use phpMyFAQ\Auth;
use phpMyFAQ\Component\Alert;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

?>
  <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
      <i aria-hidden="true" class="fa fa-lock"></i>
        <?= Translation::get('ad_passwd_cop') ?>
    </h1>
  </div>
<?php
if ($user->perm->hasPermission($user->getUserId(), 'passwd')) {
    // If we have to save a new password, do that first
    $save = Filter::filterInput(INPUT_POST, 'save', FILTER_SANITIZE_SPECIAL_CHARS);
    $csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);

    if (!is_null($save) && Token::getInstance()->verifyToken('password', $csrfToken)) {
        // Define the (Local/Current) Authentication Source
        $auth = new Auth($faqConfig);
        $authSource = $auth->selectAuth($user->getAuthSource('name'));
        $authSource->selectEncType($user->getAuthData('encType'));
        $authSource->setReadOnly($user->getAuthData('readOnly'));

        $oldPassword = Filter::filterInput(INPUT_POST, 'faqpassword_old', FILTER_SANITIZE_SPECIAL_CHARS);
        $newPassword = Filter::filterInput(INPUT_POST, 'faqpassword', FILTER_SANITIZE_SPECIAL_CHARS);
        $retypedPassword = Filter::filterInput(INPUT_POST, 'faqpassword_confirm', FILTER_SANITIZE_SPECIAL_CHARS);

        if (strlen((string) $newPassword) <= 7 || strlen((string) $retypedPassword) <= 7) {
            printf(
                '<p class="alert alert-danger alert-dismissible fade show">%s%s</p>',
                Translation::get('ad_passwd_fail'),
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
            );
        } else {
            if (($authSource->checkCredentials(
                    $user->getLogin(),
                    $oldPassword
                )) && ($newPassword == $retypedPassword)) {
                if (!$user->changePassword($newPassword)) {
                    echo Alert::danger('ad_passwd_fail');
                }
                echo Alert::success('ad_passwdsuc');
            } else {
                echo Alert::danger('ad_passwd_fail');
            }
        }
    }
    ?>

      <form action="?action=passwd" method="post" accept-charset="utf-8">
        <input type="hidden" name="save" value="newpassword">
        <?= Token::getInstance()->getTokenInput('password') ?>

        <div class="row mb-2">
          <label class="col-2 col-form-label" for="faqpassword_old">
              <?= Translation::get('ad_passwd_old') ?>
          </label>
          <div class="col-4">
            <input type="password" autocomplete="off" name="faqpassword_old" id="faqpassword_old" class="form-control"
                   required>
          </div>
        </div>

        <div class="row mb-2">
          <label class="col-2 col-form-label" for="faqpassword">
              <?= Translation::get('ad_passwd_new') ?>
          </label>
          <div class="col-4 input-group w-auto">
            <input type="password" autocomplete="off" name="faqpassword" id="faqpassword" class="form-control" required>
            <span class="input-group-text">
              <i class="fa" id="togglePassword"></i>
            </span>
          </div>
          <div class="offset-2 col-lg-8">
            <div class="progress mt-2 w-25">
              <div class="progress-bar progress-bar-striped" id="strength"></div>
            </div>
          </div>
        </div>

        <div class="row mb-2">
          <label class="col-2 col-form-label" for="faqpassword_confirm">
              <?= Translation::get('ad_passwd_con') ?>
          </label>
          <div class="col-4">
            <input type="password" autocomplete="off" name="faqpassword_confirm" id="faqpassword_confirm"
                   class="form-control" required>
          </div>
        </div>

        <div class="row mb-2">
          <div class="offset-lg-2 col-4">
            <button class="btn btn-primary" type="submit">
                <?= Translation::get('ad_passwd_change') ?>
            </button>
          </div>
        </div>

      </form>

    <?php
} else {
    echo Translation::get('err_NotAuth');
}
