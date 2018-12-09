<?php
/**
 * Cuts out a category.
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
 * @since     2003-12-25
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

if ($user->perm->checkRight($user->getUserId(), 'editcateg')) {
    $category = new Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $category->buildTree();

    $id = Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);
    $parent_id = $category->categoryName[$id]['parent_id'];
    $header = sprintf('%s: <em>%s</em>', $PMF_LANG['ad_categ_move'], $category->categoryName[$id]['name']);
    ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i aria-hidden="true" class="fas fa-cut"></i> <?= $header ?>
          </h1>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <form  action="?action=pastecategory" method="post" accept-charset="utf-8">
                    <input type="hidden" name="cat" value="<?= $id ?>">
                    <input type="hidden" name="csrf" value="<?= $user->getCsrfTokenFromSession() ?>">
                    <div class="form-group row">
                        <label class="col-lg-2 form-control-label"><?= $PMF_LANG['ad_categ_paste2'] ?></label>
                        <div class="col-lg-4">
                            <select name="after" size="1" class="form-control">
<?php
    foreach ($category->catTree as $cat) {
        $indent = '';
        for ($j = 0; $j < $cat['indent']; ++$j) {
            $indent .= '...';
        }
        if ($id != $cat['id']) {
            printf("<option value=\"%s\">%s%s</option>\n", $cat['id'], $indent, $cat['name']);
        }
    }

    if ($parent_id != 0) {
        printf('<option value="0">%s</option>', $PMF_LANG['ad_categ_new_main_cat']);
    }
    ?>
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
            </div>
        </div>
<?php

} else {
    echo $PMF_LANG['err_NotAuth'];
}
