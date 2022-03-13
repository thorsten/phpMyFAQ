<?php

/**
 * Translates a category.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Rudi Ferrari <bookcrossers@gmx.de>
 * @copyright 2006-2022 phpMyFAQ Team
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2006-09-10
 */

use phpMyFAQ\Category;
use phpMyFAQ\Category\CategoryPermission;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\UserHelper;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

if ($user->perm->hasPermission($user->getUserId(), 'editcateg')) {
    $category = new Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $category->getMissingCategories();

    $categoryPermission = new CategoryPermission($faqConfig);

    $userHelper = new UserHelper($user);

    $id = Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT);
    $header = sprintf(
        '%s %s: <em>%s</em>',
        $PMF_LANG['ad_categ_trans_1'],
        $PMF_LANG['ad_categ_trans_2'],
        $category->categoryName[$id]['name']
    );

    $selectedLanguage = Filter::filterInput(INPUT_GET, 'trlang', FILTER_UNSAFE_RAW, $faqLangCode);
    if ($selectedLanguage !== $faqLangCode) {
        $action = 'showcategory';
        $showcat = 'yes';
    } else {
        $action = 'updatecategory';
        $showcat = 'no';
    }

    $userPermission = $categoryPermission->get(CategoryPermission::USER, [$id]);
    $groupPermission = $categoryPermission->get(CategoryPermission::GROUP, [$id]);
    ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i aria-hidden="true" class="fa fa-folder"></i> <?= $header ?>
          </h1>
        </div>

        <div class="row">
          <div class="col-lg-12">
            <form action="?action=updatecategory" method="post" accept-charset="utf-8">
              <input type="hidden" name="id" value="<?= $id ?>">
              <input type="hidden" name="parent_id" value="<?= $category->categoryName[$id]['parent_id'] ?>">
              <input type="hidden" name="showcat" value="<?= $showcat ?>">
              <input type="hidden" name="active" value="<?= $category->categoryName[$id]['active'] ?>">
                <?php if ($faqConfig->get('security.permLevel') !== 'basic') : ?>
                  <input type="hidden" name="restricted_groups[]" value="<?= $groupPermission[0] ?>">
                <?php else : ?>
                  <input type="hidden" name="restricted_groups[]" value="-1">
                <?php endif; ?>
              <input type="hidden" name="restricted_users" value="<?= $userPermission[0] ?>">
              <input type="hidden" name="csrf" value="<?= $user->getCsrfTokenFromSession() ?>">

              <div class="row">
                <label class="col-lg-2 col-form-label" for="name"><?= $PMF_LANG['ad_categ_titel'] ?>:</label>
                <div class="col-lg-4">
                  <input type="text" name="name" class="form-control" id="name">
                </div>
              </div>

              <div class="row">
                <label class="col-lg-2 col-form-label" for="catlang"><?= $PMF_LANG['ad_categ_lang'] ?>:</label>
                <div class="col-lg-4">
                  <select name="catlang" id="catlang" class="form-control">
                      <?= $category->getCategoryLanguagesToTranslate($id, $selectedLanguage) ?>
                  </select>
                </div>
              </div>

              <div class="row">
                <label class="col-lg-2 col-form-label"><?= $PMF_LANG['ad_categ_desc'] ?>:</label>
                <div class="col-lg-4">
                  <textarea name="description" rows="3" class="form-control"></textarea>
                </div>
              </div>

              <div class="row">
                <label class="col-lg-2 col-form-label"><?= $PMF_LANG['ad_categ_owner'] ?>:</label>
                <div class="col-lg-4">
                  <select name="user_id" class="form-control">
                      <?= $userHelper->getAllUserOptions($category->categoryName[$id]['user_id']) ?>
                  </select>
                </div>
              </div>

              <div class="row">
                <label class="col-lg-2 col-form-label"><?= $PMF_LANG['ad_categ_transalready'] ?></label>
                <div class="col-lg-4">
                  <ul class="list-unstyled">
                      <?php
                        foreach ($category->getCategoryLanguagesTranslated($id) as $language => $description) {
                            echo '<input type="text" readonly class="form-control-plaintext" id="staticEmail" value="' . $language . ': ' . $description . '">';
                        }
                        ?>
                  </ul>
                </div>
              </div>

              <div class="row">
                <div class="offset-lg-2 col-lg-4">
                  <button class="btn btn-primary" type="submit" name="submit">
                      <?= $PMF_LANG['ad_categ_translatecateg'] ?>
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
