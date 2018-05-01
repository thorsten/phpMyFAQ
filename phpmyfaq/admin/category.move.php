<?php
/**
 * Select a category to move.
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2004-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2004-04-29
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
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header"><i class="material-icons">list</i> <?php echo $header ?></h2>
            </div>
        </header>

        <div class="row">
            <div class="col-lg-12">
                <form  action="?action=changecategory" method="post" accept-charset="utf-8">
                    <input type="hidden" name="cat" value="<?php echo $id ?>" />
                    <input type="hidden" name="csrf" value="<?php echo $user->getCsrfTokenFromSession() ?>" />
                    <div class="form-group row">
                        <label class="col-lg-2 form-control-label"><?php echo $PMF_LANG['ad_categ_change'] ?></label>
                        <div class="col-lg-4">
                           <select name="change" size="1" class="form-control">
                    <?php
                    foreach ($category->categories as $cat) {
                        if ($id != $cat['id']) {
                            printf('<option value="%s">%s</option>', $cat['id'], $cat['name']);
                        }
                    } ?>
                            </select>
                            <?php printf(
                                '<p class="help-block"><i aria-hidden="true" class="fa fa-info-circle fa-fw"></i> %s</p>',
                                $PMF_LANG['ad_categ_remark_move']
                            ); ?>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-lg-offset-2 col-lg-4">
                            <button class="btn btn-primary" type="submit" name="submit">
                                <?php echo $PMF_LANG['ad_categ_updatecateg'] ?>
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
