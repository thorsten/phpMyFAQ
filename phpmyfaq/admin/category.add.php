<?php
/**
 * Adds a new (sub-)category, a new sub-category inherits the permissions from
 * its parent category.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-12-20
 */

use phpMyFAQ\Category;
use phpMyFAQ\Filter;
use phpMyFAQ\Strings;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}
?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i aria-hidden="true" class="fas fa-folder"></i> <?= $PMF_LANG['ad_categ_new'] ?>
          </h1>
        </div>

        <div class="row">
          <div class="col-lg-12">
<?php

$currentUserId = $user->getUserId();

if ($user->perm->checkRight($user->getUserId(), 'addcateg')) {
    $category = new Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $parentId = Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);
    ?>
                <form enctype="multipart/form-data"  action="?action=savecategory" method="post">
                    <input type="hidden" id="lang" name="lang" value="<?= $LANGCODE ?>">
                    <input type="hidden" name="parent_id" value="<?= $parentId ?>">
                    <input type="hidden" name="csrf" value="<?= $user->getCsrfTokenFromSession() ?>">
<?php
    if ($parentId > 0) {
        $userAllowed = $category->getPermissions('user', array($parentId));
        $groupsAllowed = $category->getPermissions('group', array($parentId));
        ?>
            <input type="hidden" name="restricted_users" value="<?= $userAllowed[0] ?>">
            <?php foreach ($groupsAllowed as $group): ?>
            <input type="hidden" name="restricted_groups[]" value="<?= $group ?>">
            <?php endforeach;
        ?>
<?php
        printf(
            '<div class="form-group row"><label class="col-lg-2 col-form-label">%s:</label>',
            $PMF_LANG['msgMainCategory']
        );
        printf(
            '<div class="col-lg-4"><input type="text" readonly class="form-control-plaintext" id="staticEmail" value="%s (%s)"></div></div>',
            $category->categoryName[$parentId]['name'],
            $languageCodes[Strings::strtoupper($category->categoryName[$parentId]['lang'])]
        );
    }
    ?>
                  <div class="form-group row">
                    <label class="col-lg-2 col-form-label" for="name"><?= $PMF_LANG['ad_categ_titel'] ?>:</label>
                    <div class="col-lg-4">
                      <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-lg-2 col-form-label" for="description"><?= $PMF_LANG['ad_categ_desc'] ?>:</label>
                    <div class="col-lg-4">
                      <textarea id="description" name="description" rows="3" class="form-control"></textarea>
                    </div>
                  </div>

                  <div class="form-group row">
                    <div class="offset-lg-2 col-lg-4">
                      <div class="checkbox">
                        <label>
                          <input type="checkbox" name="active" value="1" checked>
                            <?= $PMF_LANG['ad_user_active'] ?>
                        </label>
                      </div>
                    </div>
                  </div>

                  <div class="form-group row">
                    <div class="offset-lg-2 col-lg-4">
                      <div class="checkbox">
                        <label>
                          <input type="checkbox" name="show_home" value="1" checked>
                            <?= $PMF_LANG['ad_user_show_home'] ?>
                        </label>
                      </div>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-lg-2 col-form-label" for="pmf-category-image-upload">
                        <?= $PMF_LANG['ad_category_image'] ?>:
                    </label>
                    <div class="col-lg-4">
                      <div class="custom-file">
                        <input type="file" class="custom-file-input" name="image" id="pmf-category-image-upload">
                        <label class="custom-file-label" for="pmf-category-image-upload">Choose file</label>
                      </div>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-lg-2 col-form-label" for="user_id">
                        <?= $PMF_LANG['ad_categ_owner'] ?>:
                    </label>
                    <div class="col-lg-4">
                      <select name="user_id" id="user_id" size="1" class="custom-select">
                          <?= $user->getAllUserOptions() ?>
                      </select>
                    </div>
                  </div>
                    <?php if ($faqConfig->get('security.permLevel') !== 'basic') { ?>
                      <div class="form-group row">
                        <label class="col-lg-2 col-form-label" for="group_id"><?= $PMF_LANG['ad_categ_moderator'] ?>
                          :</label>
                        <div class="col-lg-4">
                          <select name="group_id" id="group_id" size="1" class="custom-select">
                              <?php
                              if ($faqConfig->get('main.enableCategoryRestrictions')) {
                                  echo $user->perm->getAllGroupsOptions([], $currentUserId);
                              } else {
                                  echo $user->perm->getAllGroupsOptions([]);
                              }
                              ?>
                          </select>
                        </div>
                      </div>
                    <?php } else { ?>
                      <input type="hidden" name="group_id" value="-1">
                    <?php } ?>
                    <?php
                    if ($parentId === 0) {
                        if ($faqConfig->get('security.permLevel') !== 'basic') { ?>
                          <div class="form-group row">
                            <label class="col-lg-2 col-form-label"><?= $PMF_LANG['ad_entry_grouppermission'] ?></label>
                            <div class="col-lg-4">
                              <label class="radio">
                                <input type="radio" name="grouppermission" value="all" checked>
                                  <?= $PMF_LANG['ad_entry_all_groups'] ?>
                              </label>
                              <label class="radio">
                                <input type="radio" name="grouppermission" value="restricted">
                                  <?= $PMF_LANG['ad_entry_restricted_groups'] ?>
                              </label>
                              <select name="restricted_groups[]" size="3" class="custom-select" multiple>
                                  <?php
                                  if ($faqConfig->get('main.enableCategoryRestrictions')) {
                                      echo $user->perm->getAllGroupsOptions([], $currentUserId);
                                  } else {
                                      echo $user->perm->getAllGroupsOptions([]);
                                  }
                                  ?>
                              </select>
                            </div>
                          </div>
                        <?php } else { ?>
                          <input type="hidden" name="grouppermission" value="all">
                        <?php } ?>
                      <div class="form-group row">
                        <label class="col-lg-2 col-form-label"><?= $PMF_LANG['ad_entry_userpermission'] ?></label>
                        <div class="col-lg-4">
                          <label class="radio">
                            <input type="radio" name="userpermission" value="all" checked>
                              <?= $PMF_LANG['ad_entry_all_users'] ?>
                          </label>
                          <label class="radio">
                            <input type="radio" name="userpermission" value="restricted">
                              <?= $PMF_LANG['ad_entry_restricted_users'] ?>
                          </label>
                          <select name="restricted_users" size="1" class="custom-select">
                              <?= $user->getAllUserOptions(1) ?>
                          </select>
                        </div>
                      </div>
                    <?php } ?>
                  <div class="form-group row">
                    <div class="offset-lg-2 col-lg-4">
                      <button class="btn btn-primary" type="submit" name="submit">
                          <?= $PMF_LANG['ad_categ_add'] ?>
                      </button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
<?php

} else {
    echo $PMF_LANG['err_NotAuth'];
}
