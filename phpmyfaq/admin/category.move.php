<?php
/**
 * Select a category to move.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2004-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2004-04-29
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
    $id = Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT);
    $parent_id = Filter::filterInput(INPUT_GET, 'parent_id', FILTER_VALIDATE_INT);
    $category = new Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $categories = $category->getAllCategories();

    $category->categories = [];
    unset($category->categories);
    $category->getCategories($parent_id, false);
    $category->buildTree($parent_id);

    $header = sprintf('%s: <em>%s</em>',
        $PMF_LANG['ad_categ_move'],
        $category->categories[$id]['name']
    );
    ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i aria-hidden="true" class="fas fa-folder"></i> <?= $header ?>
          </h1>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <form  action="?action=changecategory" method="post" accept-charset="utf-8">
                    <input type="hidden" name="cat" value="<?= $id ?>" />
                    <input type="hidden" name="csrf" value="<?= $user->getCsrfTokenFromSession() ?>" />
                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label" for="change"><?= $PMF_LANG['ad_categ_change'] ?></label>
                        <div class="col-lg-4">
                           <select id="change" name="change" class="form-control">
                    <?php
                    foreach ($category->categories as $cat) {
                        if ($id != $cat['id']) {
                            printf('<option value="%s">%s</option>', $cat['id'], $cat['name']);
                        }
                    } ?>
                            </select>
                            <?php printf(
                                '<span class="form-text text-muted"><i aria-hidden="true" class="fas fa-info-circle fa-fw"></i> %s</span>',
                                $PMF_LANG['ad_categ_remark_move']
                            ); ?>
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
<?php

} else {
    echo $PMF_LANG['err_NotAuth'];
}
