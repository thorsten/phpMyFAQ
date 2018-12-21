<?php
/**
 * Edits a category.
 *
 *
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-03-10
 */

use phpMyFAQ\Category;
use phpMyFAQ\Filter;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$currentUserId = $user->getUserId();

if ($user->perm->checkRight($user->getUserId(), 'editcateg')) {
    $categoryId = Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);

    $category = new Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);

    $categoryData = $category->getCategoryData($categoryId);
    $userPermission = $category->getPermissions('user', array($categoryId));

    if ($userPermission[0] == -1) {
        $allUsers = true;
        $restrictedUsers = false;
    } else {
        $allUsers = false;
        $restrictedUsers = true;
    }

    $groupPermission = $category->getPermissions('group', array($categoryId));
    if ($groupPermission[0] == -1) {
        $allGroups = true;
        $restrictedGroups = false;
    } else {
        $allGroups = false;
        $restrictedGroups = true;
    }

    $header = $PMF_LANG['ad_categ_edit_1'].' "'.$categoryData->getName().'" '.$PMF_LANG['ad_categ_edit_2'];
    ?>

        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i aria-hidden="true" class="fas fa-folder"></i> <?= $header ?>
          </h1>
        </div>

        <div class="row">
            <div class="col-lg-12">
                
            </div>
        </div>
        <form enctype="multipart/form-data"  action="?action=updatecategory" method="post">
            <input type="hidden" name="id" value="<?= $categoryId ?>">
            <input type="hidden" id="catlang" name="catlang" value="<?= $categoryData->getLang() ?>">
            <input type="hidden" name="parent_id" value="<?= $categoryData->getParentId() ?>">
            <input type="hidden" name="csrf" value="<?= $user->getCsrfTokenFromSession() ?>">
            <input type="hidden" name="existing_image" value="<?= $categoryData->getImage() ?>">

            <div class="form-group row">
                <label class="col-lg-2 form-control-label">
                    <?= $PMF_LANG['ad_categ_titel'] ?>:
                </label>
                <div class="col-lg-4">
                    <input type="text" id="name" name="name" value="<?= $categoryData->getName() ?>"
                        class="form-control">
                </div>
            </div>

            <div class="form-group row">
                <label class="col-lg-2 form-control-label">
                    <?= $PMF_LANG['ad_categ_desc'] ?>:
                </label>
                <div class="col-lg-4">
                    <textarea id="description" name="description" rows="3" class="form-control"><?= $categoryData->getDescription() ?></textarea>
                </div>
            </div>

            <div class="form-group row">
                <div class="col-lg-offset-2 col-lg-4">
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
            <div class="col-lg-offset-2 col-lg-4">
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
                <label class="col-lg-2 form-control-label" for="pmf-category-image-upload">
                    <?= $PMF_LANG['ad_category_image'] ?>:
                </label>
                <div class="col-lg-4">
                    <input id="pmf-category-image-upload" name="image" type="file" class="file"
                           value="<?= $categoryData->getImage() ?>">
                </div>
            </div>

            <div class="form-group row">
                <label class="col-lg-2 form-control-label">
                    <?= $PMF_LANG['ad_categ_owner'] ?>:
                </label>
                <div class="col-lg-4">
                    <select name="user_id" size="1" class="form-control">
                        <?= $user->getAllUserOptions($categoryData->getUserId()) ?>
                    </select>
                </div>
            </div>
<?php
    if ($faqConfig->get('security.permLevel') != 'basic') {
        ?>

            <div class="form-group row">
                <label class="col-lg-2 form-control-label" for="group_id"><?= $PMF_LANG['ad_categ_moderator'] ?>:</label>
                <div class="col-lg-4">
                    <select name="group_id" id="group_id" size="1" class="form-control">
                        <?php 
                            if ($faqConfig->get('main.enableCategoryRestrictions')) {
                                echo $user->perm->getAllGroupsOptions([$categoryData->getGroupId()], $currentUserId);
                            } else {
                                echo $user->perm->getAllGroupsOptions([$categoryData->getGroupId()]); 
                            }
                        ?>
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <label class="col-lg-2 form-control-label">
                    <?= $PMF_LANG['ad_entry_grouppermission'] ?>
                </label>
                <div class="col-lg-4">
                    <div class="radio">
                        <input type="radio" name="grouppermission" value="all"
                            <?php echo($allGroups ? 'checked' : '') ?>>
                        <?= $PMF_LANG['ad_entry_all_groups'] ?>
                    </div>
                    <label class="radio">
                        <input type="radio" name="grouppermission" value="restricted"
                            <?php echo($restrictedGroups ? 'checked' : '') ?>>
                        <?= $PMF_LANG['ad_entry_restricted_groups'] ?>
                    </label>
                    <select name="restricted_groups[]" size="3" class="form-control" multiple>
                        <?php 
                            if ($faqConfig->get('main.enableCategoryRestrictions')) {
                                echo $user->perm->getAllGroupsOptions($groupPermission, $currentUserId);
                            } else {
                                echo $user->perm->getAllGroupsOptions($groupPermission);
                            }
                        ?>

                    </select>
                </div>
            </div>
<?php

    } else {
        ?>
            <input type="hidden" name="grouppermission" value="all">
<?php 
    }
    ?>
            <div class="form-group row">
                <label class="col-lg-2 form-control-label">
                    <?= $PMF_LANG['ad_entry_userpermission'] ?>
                </label>
                <div class="col-lg-4">
                    <label class="radio">
                        <input type="radio" name="userpermission" value="all"
                            <?= ($allUsers ? 'checked' : '') ?>>
                        <?= $PMF_LANG['ad_entry_all_users'] ?>
                    </label>
                    <label class="radio">
                        <input type="radio" name="userpermission" value="restricted"
                            <?= ($restrictedUsers ? 'checked' : '') ?>>
                        <?= $PMF_LANG['ad_entry_restricted_users'] ?>
                    </label>
                    <select name="restricted_users" class="form-control" size="1">
                        <?= $user->getAllUserOptions($userPermission[0]) ?>
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <div class="col-lg-offset-2 col-lg-4">
                    <button class="btn btn-primary" type="submit" name="submit">
                        <?= $PMF_LANG['ad_categ_updatecateg'] ?>
                    </button>
                </div>
            </div>
    </form>

    <script>
        var categoryImageUpload = $('#pmf-category-image-upload');
        categoryImageUpload.fileinput({
            uploadAsync: false,
            showUpload: false,
            uploadUrl: "?action=updatecategory",
            <?php if ('' !== $categoryData->getImage()) { ?>
            initialPreview: [
                '<img src="<?= $faqConfig->getDefaultUrl().'/images/'.$categoryData->getImage() ?>" class="file-preview-image" alt="phpMyFAQ" width="120">'
            ],
            <?php } ?>
            initialPreviewShowDelete: true
        });
        categoryImageUpload.on('fileclear', function(event) {
            $('input[name=existing_image]').val('');
        });
    </script>
<?php

} else {
    echo $PMF_LANG['err_NotAuth'];
}
