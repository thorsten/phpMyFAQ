<?php

/**
 * Cuts out a category.
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
 * @since     2003-12-25
 */

use phpMyFAQ\Category;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

if ($user->perm->hasPermission($user->getUserId(), 'editcateg')) {
    $category = new Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $category->buildCategoryTree();

    $id = Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);
    $parent_id = $category->categoryName[$id]['parent_id'];
    $header = sprintf('%s: <em>%s</em>', Translation::get('ad_categ_move'), $category->categoryName[$id]['name']);
    ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i aria-hidden="true" class="fa fa-cut"></i> <?= $header ?>
          </h1>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <form  action="?action=pastecategory" method="post" accept-charset="utf-8">
                    <input type="hidden" name="cat" value="<?= $id ?>">
                    <?= Token::getInstance()->getTokenInput('paste-category') ?>
                    <div class="row mb-2">
                        <label class="col-lg-2 col-form-label" for="after"><?= Translation::get('ad_categ_paste2') ?></label>
                        <div class="col-lg-4">
                            <select name="after" id="after" class="form-select">
    <?php
    foreach ($category->getCategoryTree() as $cat) {
        $indent = '';
        for ($j = 0; $j < $cat['indent']; ++$j) {
            $indent .= '...';
        }
        if ($id != $cat['id']) {
            printf("<option value=\"%s\">%s%s</option>\n", $cat['id'], $indent, $cat['name']);
        }
    }

    if ($parent_id != 0) {
        printf('<option value="0">%s</option>', Translation::get('ad_categ_new_main_cat'));
    }
    ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="offset-lg-2 col-lg-4">
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
