<?php

/**
 * Shows the admin search frontend for FAQs.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2011-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2011-09-29
 */

use phpMyFAQ\Category;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}
?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i aria-hidden="true" class="fa fa-search"></i>
              <?= Translation::get('ad_menu_searchfaqs') ?>
          </h1>
        </div>

        <div class="row">
            <div class="col-lg-12">
<?php
if ($user->perm->hasPermission($user->getUserId(), 'edit_faq') || $user->perm->hasPermission($user->getUserId(), 'delete_faq')) {
    $category = new Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $category->transform(0);
    $category->buildCategoryTree();

    $categoryHelper = new CategoryHelper();
    $categoryHelper->setCategory($category);
?>
                <form action="?action=view" method="post"  accept-charset="utf-8">

                    <div class="row mb-2">
                        <label class="col-lg-2 col-form-label" for="searchterm">
                            <?= Translation::get('msgSearchWord') ?>:
                        </label>
                        <div class="col-lg-4">
                            <input class="form-control" type="search" name="searchterm" id="searchterm" autofocus>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <label class="col-lg-2 col-form-label" for="searchcat">
                            <?= Translation::get('msgCategory') ?>:
                        </label>
                        <div class="col-lg-4">
                            <select name="searchcat" id="searchcat" class="form-select">
                                <option value="0"><?= Translation::get('msgShowAllCategories') ?></option>
                                <?= $categoryHelper->renderOptions(0) ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="offset-lg-2 col-lg-4 text-end">
                            <button class="btn btn-primary" type="submit" name="submit">
                                <?= Translation::get('msgSearch') ?>
                            </button>
                        </div>
                    </div>

                </form>
    <?php
} else {
    echo Translation::get('err_NotAuth');
}
?>
            </div>
        </div>
