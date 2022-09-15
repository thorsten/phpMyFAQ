<?php

/**
 * Edits a category.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2022 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2003-03-10
 */

use phpMyFAQ\Category;
use phpMyFAQ\Category\CategoryPermission;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\UserHelper;

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

    $header = $PMF_LANG['ad_categ_edit_1'] . ' "' . $categoryData->getName() . '" ' . $PMF_LANG['ad_categ_edit_2'];
    ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i aria-hidden="true" class="fa fa-folder"></i> <?= $header ?>
          </h1>
        </div>

        <div class="row">
          <div class="col-lg-12">
          <form enctype="multipart/form-data" action="?action=updatecategory" method="post">
            <input type="hidden" name="id" value="<?= $categoryId ?>">
            <input type="hidden" id="catlang" name="catlang" value="<?= $categoryData->getLang() ?>">
            <input type="hidden" name="parent_id" value="<?= $categoryData->getParentId() ?>">
            <input type="hidden" name="csrf" value="<?= $user->getCsrfTokenFromSession() ?>">
            <input type="hidden" name="existing_image" value="<?= $categoryData->getImage() ?>"
                   id="pmf-category-existing-image">

            <div class="form-group row">
              <label class="col-lg-2 col-form-label">
                  <?= $PMF_LANG['ad_categ_titel'] ?>:
              </label>
              <div class="col-lg-4">
                <input type="text" id="name" name="name" value="<?= $categoryData->getName() ?>"
                       class="form-control">
              </div>
            </div>

            <div class="form-group row">
              <label class="col-lg-2 col-form-label">
                  <?= $PMF_LANG['ad_categ_desc'] ?>:
              </label>
              <div class="col-lg-4">
                <textarea id="description" name="description" rows="3"
                          class="form-control"><?= $categoryData->getDescription() ?></textarea>
              </div>
            </div>

            <div class="form-group row">
              <div class="offset-lg-2 col-lg-4">
                <div class="checkbox">
                  <label>
                    <input type="checkbox" name="active" value="1"
                        <?= (1 === (int)$categoryData->getActive() ? 'checked' : '') ?>>
                      <?= $PMF_LANG['ad_user_active'] ?>
                  </label>
                </div>
              </div>
            </div>

            <div class="form-group row">
              <div class="offset-lg-2 col-lg-4">
                <div class="checkbox">
                  <label>
                    <input type="checkbox" name="show_home" value="1"
                        <?= (1 === (int)$categoryData->getShowHome() ? 'checked' : '') ?>>
                      <?= $PMF_LANG['ad_user_show_home'] ?>
                  </label>
                </div>
              </div>
            </div>


            <div class="form-group row">
              <label class="col-lg-2 col-form-label" for="pmf-category-image-upload">
                  <?= $PMF_LANG['ad_category_image'] ?>
              </label>
              <div class="col-lg-4">
                <div class="form-group">
                  <div class="custom-file">
                    <input type="file" class="custom-file-input" name="image" id="pmf-category-image-upload"
                           value="<?= $categoryData->getImage() ?>">
                    <label class="custom-file-label" for="pmf-category-image-upload" id="pmf-category-image-label">
                        <?= $categoryData->getImage() ?>
                    </label>
                  </div>
                </div>
                <div class="form-group">
                  <button type="button" class="btn btn-info" id="button-reset-category-image">
                    Reset category image
                  </button>
                </div>
              </div>
            </div>

            <div class="form-group row">
              <label class="col-lg-2 col-form-label" for="user_id">
                  <?= $PMF_LANG['ad_categ_owner'] ?>
              </label>
              <div class="col-lg-4">
                <select id="user_id" name="user_id" class="form-control">
                    <?= $userHelper->getAllUserOptions($categoryData->getUserId()) ?>
                </select>
              </div>
            </div>
              <?php if ($faqConfig->get('security.permLevel') != 'basic') { ?>
                <div class="form-group row">
                  <label class="col-lg-2 col-form-label" for="group_id"><?= $PMF_LANG['ad_categ_moderator'] ?>:</label>
                  <div class="col-lg-4">
                    <select name="group_id" id="group_id" class="form-control">
                        <?= $user->perm->getAllGroupsOptions([$categoryData->getGroupId()], $user) ?>
                    </select>
                  </div>
                </div>

                <div class="form-group row">
                  <label class="col-lg-2 col-form-label" for="grouppermission">
                      <?= $PMF_LANG['ad_entry_grouppermission'] ?>
                  </label>
                  <div class="col-lg-4">
                    <div class="radio">
                      <input type="radio" id="grouppermission" name="grouppermission" value="all"
                          <?php echo($allGroups ? 'checked' : '') ?>>
                        <?= $PMF_LANG['ad_entry_all_groups'] ?>
                    </div>
                    <label class="radio">
                      <input type="radio" name="grouppermission" value="restricted"
                          <?php echo($restrictedGroups ? 'checked' : '') ?>>
                        <?= $PMF_LANG['ad_entry_restricted_groups'] ?>
                    </label>
                    <select name="restricted_groups[]" size="3" class="form-control" multiple>
                        <?= $user->perm->getAllGroupsOptions($groupPermission, $user) ?>
                    </select>
                  </div>
                </div>
              <?php } else { ?>
                <input type="hidden" name="grouppermission" value="all">
              <?php } ?>
            <div class="form-group row">
              <label class="col-lg-2 col-form-label">
                  <?= $PMF_LANG['ad_entry_userpermission'] ?>
              </label>
              <div class="col-lg-4">
                <div class="radio">
                  <input type="radio" name="userpermission" value="all"
                      <?= ($allUsers ? 'checked' : '') ?>>
                    <?= $PMF_LANG['ad_entry_all_users'] ?>
                </div>
                <div class="radio">
                  <input type="radio" name="userpermission" value="restricted"
                      <?= ($restrictedUsers ? 'checked' : '') ?>>
                    <?= $PMF_LANG['ad_entry_restricted_users'] ?>
                </div>
                <select name="restricted_users" class="form-control">
                    <?= $userHelper->getAllUserOptions($userPermission[0]) ?>
                </select>
              </div>
            </div>

            <div class="form-group row">
              <div class="offset-lg-2 col-lg-4">
                <button class="btn btn-primary" type="submit" name="submit">
                    <?= $PMF_LANG['ad_categ_updatecateg'] ?>
                </button>
              </div>
            </div>
          </form>
          </div>
        </div>
        <script>
          document.addEventListener('DOMContentLoaded', () => {
            bsCustomFileInput.init();

            const resetButton = document.getElementById('button-reset-category-image');
            const categoryExistingImage = document.getElementById('pmf-category-existing-image');
            const categoryImageInput = document.getElementById('pmf-category-image-upload');
            const categoryImageLabel = document.getElementById('pmf-category-image-label');
            resetButton.addEventListener('click', () => {
              categoryImageInput.value = '';
              categoryExistingImage.value = '';
              categoryImageLabel.innerHTML = '';
            });
          });
        </script>
    <?php
} else {
    echo $PMF_LANG['err_NotAuth'];
}
