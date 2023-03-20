<?php

/**
 * Edits a category.
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
 * @since     2003-03-10
 */

use phpMyFAQ\Category;
use phpMyFAQ\Category\CategoryPermission;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\UserHelper;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$currentUserId = $user->getUserId();

if ($user->perm->hasPermission($user->getUserId(), 'editcateg')) {
    $categoryId = Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);

    $category = new Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);

    $categoryPermission = new CategoryPermission($faqConfig);

    $userHelper = new UserHelper($user);

    $categoryData = $category->getCategoryData($categoryId);
    $userPermission = $categoryPermission->get(CategoryPermission::USER, [$categoryId]);

    if ($userPermission[0] == -1) {
        $allUsers = true;
        $restrictedUsers = false;
    } else {
        $allUsers = false;
        $restrictedUsers = true;
    }

    $groupPermission = $categoryPermission->get(CategoryPermission::GROUP, [$categoryId]);
    if ($groupPermission[0] == -1) {
        $allGroups = true;
        $restrictedGroups = false;
    } else {
        $allGroups = false;
        $restrictedGroups = true;
    }

    $header = Translation::get('ad_categ_edit_1') . ' "' . Strings::htmlentities($categoryData->getName()) . '" ' .
        Translation::get('ad_categ_edit_2');
    ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i aria-hidden="true" class="fa fa-folder"></i> <?= $header ?>
          </h1>
        </div>

        <div class="row mb-2">
          <div class="col-lg-12">
          <form enctype="multipart/form-data" action="?action=updatecategory" method="post">
            <input type="hidden" name="id" value="<?= $categoryId ?>">
            <input type="hidden" name="catlang" id="catlang" value="<?= $categoryData->getLang() ?>">
            <input type="hidden" name="parent_id" value="<?= $categoryData->getParentId() ?>">
              <?= Token::getInstance()->getTokenInput('update-category') ?>
            <input type="hidden" name="existing_image" value="<?= $categoryData->getImage() ?>"
                   id="pmf-category-existing-image">

            <div class="row mb-2">
              <label class="col-lg-2 col-form-label" for="name">
                  <?= Translation::get('ad_categ_titel') ?>
              </label>
              <div class="col-lg-4">
                <input type="text" id="name" name="name" value="<?= Strings::htmlentities($categoryData->getName()) ?>" class="form-control">
              </div>
            </div>

            <div class="row mb-2">
              <label class="col-lg-2 col-form-label" for="description">
                  <?= Translation::get('ad_categ_desc') ?>
              </label>
              <div class="col-lg-4">
                <textarea id="description" name="description" rows="3"
                          class="form-control"><?= Strings::htmlentities($categoryData->getDescription()) ?></textarea>
              </div>
            </div>

            <div class="row mb-2">
              <div class="offset-lg-2 col-lg-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="active" id='active' value="1"
                        <?= (1 === (int)$categoryData->getActive() ? 'checked' : '') ?>>
                    <label class="form-check-label" for="active"><?= Translation::get('ad_user_active') ?></label>
                </div>
              </div>
            </div>

            <div class="row mb-2">
              <div class="offset-lg-2 col-lg-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="show_home" id='show_home' value="1"
                        <?= (1 === (int)$categoryData->getShowHome() ? 'checked' : '') ?>>
                    <label class="form-check-label" for="show_home"><?= Translation::get('ad_user_show_home') ?></label>
                </div>
              </div>
            </div>


              <div class="row mb-2">
                  <label class="col-lg-2 col-form-label" for="pmf-category-image-upload">
                      <?= Translation::get('ad_category_image') ?>:
                  </label>
                  <div class="col-lg-4">
                    <div class="input-group">
                      <input class="form-control" type="file" name="image" id="pmf-category-image-upload"
                             value="<?= $categoryData->getImage() ?>">
                      <span class="input-group-text"><?= $categoryData->getImage() ?></span>
                    </div>
                      <div class="input-group mt-2">
                          <button type="button" class="btn btn-info" id="button-reset-category-image">
                              Reset category image
                          </button>
                      </div>
                  </div>
              </div>

            <div class="row mb-2">
              <label class="col-lg-2 col-form-label" for="user_id">
                  <?= Translation::get('ad_categ_owner') ?>
              </label>
              <div class="col-lg-4">
                <select id="user_id" name="user_id" class="form-select">
                    <?= $userHelper->getAllUserOptions($categoryData->getUserId()) ?>
                </select>
              </div>
            </div>

              <?php if ($faqConfig->get('security.permLevel') != 'basic') { ?>
                  <div class="row mb-2">
                      <label class="col-lg-2 col-form-label" for="group_id"><?= Translation::get('ad_categ_moderator') ?>:</label>
                      <div class="col-lg-4">
                          <select name="group_id" id="group_id" class="form-select">
                              <?= $user->perm->getAllGroupsOptions([$categoryData->getGroupId()], $user) ?>
                          </select>
                      </div>
                  </div>

                  <div class="row mb-2">
                      <label class="col-lg-2 col-form-label" for="restricted_groups">
                          <?= Translation::get('ad_entry_grouppermission') ?>
                      </label>
                      <div class="col-lg-4">
                          <div class="form-check">
                              <input type="radio" name="grouppermission" id="grouppermission_all" value="all"
                                     class="form-check-input" <?php echo($allGroups ? 'checked' : '') ?>>
                              <label class="form-check-label" for="grouppermission_all">
                                  <?= Translation::get('ad_entry_all_groups') ?>
                              </label>
                          </div>
                          <div class="form-check">
                              <input type="radio" name="grouppermission" id="grouppermission" value="restricted"
                                     class="form-check-input" <?php echo($restrictedGroups ? 'checked' : '') ?>>
                              <label class="form-check-label" for="grouppermission">
                                  <?= Translation::get('ad_entry_restricted_groups') ?>
                              </label>
                          </div>
                          <select name="restricted_groups[]" id="restricted_groups" size="3" class="form-select"
                                  multiple>
                              <?= $user->perm->getAllGroupsOptions($groupPermission, $user) ?>
                          </select>
                      </div>
                  </div>

              <?php } else { ?>
                <input type="hidden" name="grouppermission" value="all">
              <?php } ?>
              <div class="row mb-2">
                  <label class="col-lg-2 col-form-label" for="restricted_users">
                      <?= Translation::get('ad_entry_userpermission') ?>
                  </label>
                  <div class="col-lg-4">
                      <div class="form-check">
                          <input type="radio" name="userpermission" id="userpermission_all" value="all"
                                 class="form-check-input" <?= ($allUsers ? 'checked' : '') ?>>
                          <label class="form-check-label" for="userpermission_all">
                              <?= Translation::get('ad_entry_all_users') ?>
                          </label>
                      </div>
                      <div class="form-check">
                          <input type="radio" name="userpermission" id="userpermission" value="restricted" class="form-check-input">
                          <label class="form-check-label" for="userpermission"<?= ($restrictedUsers ? 'checked' : '') ?>>
                              <?= Translation::get('ad_entry_restricted_users') ?>
                          </label>
                      </div>
                      <select name="restricted_users" id="restricted_users" class="form-select">
                          <?= $userHelper->getAllUserOptions($userPermission[0]) ?>
                      </select>
                  </div>
              </div>

            <div class="row mb-2">
              <div class="offset-lg-2 col-lg-4 text-end">
                <button class="btn btn-primary" type="submit" name="submit">
                    <?= Translation::get('ad_categ_updatecateg') ?>
                </button>
              </div>
            </div>
          </form>
          </div>
        </div>
    <?php
} else {
    echo Translation::get('err_NotAuth');
}
