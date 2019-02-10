<?php
/**
 * Deletes a category.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2003-12-20
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header"><i aria-hidden="true" class="fa fa-list"></i> <?php echo $PMF_LANG['ad_categ_deletesure'] ?></h2>
            </div>
        </header>
<?php
if ($user->perm->checkRight($user->getUserId(), 'delcateg')) {
    $category = new PMF_Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $categories = $category->getAllCategories();
    $id = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);
    ?>
        <div class="row">
            <div class="col-lg-12">
                <form class="form-horizontal" action="?action=removecategory" method="post" accept-charset="utf-8">
                    <input type="hidden" name="cat" value="<?php echo $id;
    ?>" />
                    <input type="hidden" name="lang" value="<?php echo $LANGCODE;
    ?>" />
                    <input type="hidden" name="csrf" value="<?php echo $user->getCsrfTokenFromSession();
    ?>" />

                    <div class="form-group">
                        <label class="col-lg-2 control-label"><?php echo $PMF_LANG['ad_categ_titel'];
    ?>:</label>
                        <div class="col-lg-4">
                            <p class="form-control-static"><?php echo $categories[$id]['name'];
    ?></p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label"><?php echo $PMF_LANG['ad_categ_desc'];
    ?>:</label>
                        <div class="col-lg-4">
                            <?php echo $categories[$id]['description'];
    ?>
                            <label class="radio">
                                <input type="radio" checked name="deleteall" value="yes" />
                                <?php echo $PMF_LANG['ad_categ_deletealllang'];
    ?>
                            </label>
                            <label class="radio">
                                <input type="radio" name="deleteall" value="no" />
                                <?php echo $PMF_LANG['ad_categ_deletethislang'];
    ?>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-4">
                            <button class="btn btn-danger" type="submit" name="submit">
                                <?php echo $PMF_LANG['ad_categ_del_yes'];
    ?>
                            </button>
                            <a class="btn btn-success" onclick="javascript:history.back();">
                                <?php echo $PMF_LANG['ad_categ_del_no'];
    ?>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
<?php

} else {
    echo $PMF_LANG['err_NotAuth'];
}
