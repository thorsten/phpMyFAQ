<?php

/**
 * Adds a new (sub-)category, a new sub-category inherits the permissions from
 * its parent category.
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
 * @since     2003-12-20
 */

use phpMyFAQ\Category;
use phpMyFAQ\Category\CategoryPermission;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\UserHelper;
use phpMyFAQ\Language\LanguageCodes;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}
?>

  <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
      <i aria-hidden="true" class="fa fa-folder"></i> <?= Translation::get('ad_categ_new') ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
      <div class="btn-group mr-2">
        <a class="btn btn-sm btn-info" href="?action=showcategory">
          <i aria-hidden="true" class="fa fa-list"></i> <?= Translation::get('ad_categ_show'); ?>
        </a>
      </div>
    </div>
  </div>

  <div class="row mb-2">
    <div class="col-lg-12">
<?php

$currentUserId = $user->getUserId();

if ($user->perm->hasPermission($user->getUserId(), 'addcateg')) {
    $category = new Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);

    $categoryPermission = new CategoryPermission($faqConfig);

    $userHelper = new UserHelper($user);

    $parentId = Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);
    ?>
                <form enctype="multipart/form-data"  action="?action=savecategory" method="post">
                    <input type="hidden" id="lang" name="lang" value="<?= $faqLangCode ?>">
                    <input type="hidden" name="parent_id" value="<?= $parentId ?>">
                    <?= Token::getInstance()->getTokenInput('save-category') ?>
    <?php
    if ($parentId > 0) {
        $userAllowed = $categoryPermission->get(CategoryPermission::USER, [$parentId]);
        $groupsAllowed = $categoryPermission->get(CategoryPermission::GROUP, [$parentId]);
        ?>
            <input type="hidden" name="restricted_users" value="<?= $userAllowed[0] ?>">
            <?php foreach ($groupsAllowed as $group): ?>
            <input type="hidden" name="restricted_groups[]" value="<?= $group ?>">
            <?php endforeach;
            ?>
        <?php
        printf(
            '<div class="row mb-2"><label class="col-lg-2 col-form-label">%s:</label>',
            Translation::get('msgMainCategory')
        );
        printf(
            '<div class="col-lg-4"><input type="text" readonly class="form-control-plaintext" id="staticEmail" value="%s (%s)"></div></div>',
            $category->categoryName[$parentId]['name'],
            LanguageCodes::get($category->categoryName[$parentId]['lang'])
        );
    }
    ?>
                  <div class="row mb-2">
                    <label class="col-lg-2 col-form-label" for="name">
                        <?= Translation::get('ad_categ_titel') ?>
                    </label>
                    <div class="col-lg-4">
                      <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                  </div>

                  <div class="row mb-2">
                    <label class="col-lg-2 col-form-label" for="description">
                        <?= Translation::get('ad_categ_desc') ?>
                    </label>
                    <div class="col-lg-4">
                      <textarea id="description" name="description" rows="3" class="form-control"></textarea>
                    </div>
                  </div>

                  <div class="row mb-2">
                    <div class="offset-lg-2 col-lg-4">
                      <div class="form-check">
                        <input type="checkbox" name="active" id="active" value="1" class="form-check-input" checked>
                        <label class="form-check-label" for="active">
                          <?= Translation::get('ad_user_active') ?>
                        </label>
                      </div>
                    </div>
                  </div>

                  <div class="row mb-2">
                    <div class="offset-lg-2 col-lg-4">
                      <div class="form-check">
                        <input type="checkbox" name="show_home" id="show_home" value="1" class="form-check-input"
                               checked>
                        <label class="form-check-label" for="show_home">
                            <?= Translation::get('ad_user_show_home') ?>
                        </label>
                      </div>
                    </div>
                  </div>

                  <div class="row mb-2">
                    <label class="col-lg-2 col-form-label" for="pmf-category-image-upload">
                        <?= Translation::get('ad_category_image') ?>:
                    </label>
                    <div class="col-lg-4">
                        <input class="form-control" type="file" name="image" id="pmf-category-image-upload">
                    </div>
                  </div>

                  <div class="row mb-2">
                    <label class="col-lg-2 col-form-label" for="user_id">
                        <?= Translation::get('ad_categ_owner') ?>
                    </label>
                    <div class="col-lg-4">
                      <select name="user_id" id="user_id" class="form-select">
                          <?= $userHelper->getAllUserOptions() ?>
                      </select>
                    </div>
                  </div>
                    <?php if ($faqConfig->get('security.permLevel') !== 'basic') { ?>
                      <div class="row mb-2">
                        <label class="col-lg-2 col-form-label" for="group_id">
                            <?= Translation::get('ad_categ_moderator') ?></label>
                        <div class="col-lg-4">
                          <select name="group_id" id="group_id" class="form-select">
                              <?= $user->perm->getAllGroupsOptions([], $user) ?>
                          </select>
                        </div>
                      </div>
                    <?php } else { ?>
                      <input type="hidden" name="group_id" value="-1">
                    <?php } ?>
                    <?php
                    if ($parentId === 0) {
                        if ($faqConfig->get('security.permLevel') !== 'basic') { ?>
                          <div class="row mb-2">
                            <label class="col-lg-2 col-form-label" for="restricted_groups">
                                <?= Translation::get('ad_entry_grouppermission') ?>
                            </label>
                            <div class="col-lg-4">
                              <div class="form-check">
                                <input type="radio" name="grouppermission" id="grouppermission_all" value="all"
                                       class="form-check-input" checked>
                                <label class="form-check-label" for="grouppermission_all">
                                  <?= Translation::get('ad_entry_all_groups') ?>
                                </label>
                              </div>
                              <div class="form-check">
                                <input type="radio" name="grouppermission" id="grouppermission" value="restricted"
                                       class="form-check-input">
                                <label class="form-check-label" for="grouppermission">
                                  <?= Translation::get('ad_entry_restricted_groups') ?>
                                </label>
                              </div>
                              <select name="restricted_groups[]" id="restricted_groups" size="3" class="form-select"
                                      multiple>
                                <?= $user->perm->getAllGroupsOptions([], $user) ?>
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
                                   class="form-check-input" checked>
                            <label class="form-check-label" for="userpermission_all">
                                <?= Translation::get('ad_entry_all_users') ?>
                            </label>
                          </div>
                          <div class="form-check">
                            <input type="radio" name="userpermission" id="userpermission" value="restricted" class="form-check-input">
                            <label class="form-check-label" for="userpermission">
                              <?= Translation::get('ad_entry_restricted_users') ?>
                            </label>
                          </div>
                          <select name="restricted_users" id="restricted_users" class="form-select">
                              <?= $userHelper->getAllUserOptions(1) ?>
                          </select>
                        </div>
                      </div>
                    <?php } ?>
                  <div class="row mb-2">
                    <div class="offset-lg-2 col-lg-4">
                      <button class="btn btn-primary" type="submit" name="submit">
                          <?= Translation::get('ad_categ_add') ?>
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
