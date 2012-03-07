<?php
/**
 * Deletes a category
 *
 * PHP Version 5.2
 *

 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-12-20
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

?>

        <header>
            <h2><?php print $PMF_LANG['ad_categ_deletesure']; ?></h2>
        </header>

<?php
if ($permission['delcateg']) {
    $category   = new PMF_Category($current_admin_user, $current_admin_groups, false);
    $categories = $category->getAllCategories();
    $id         = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);
    ?>
        <form class="form-horizontal" action="?action=removecategory" method="post">
            <input type="hidden" name="cat" value="<?php print $id; ?>" />
            <input type="hidden" name="lang" value="<?php print $LANGCODE; ?>" />
            <input type="hidden" name="csrf" value="<?php print $user->getCsrfTokenFromSession(); ?>" />

            <div class="control-group">
                <label><?php print $PMF_LANG['ad_categ_titel']; ?>:</label>
                <div class="controls">
                    <?php print $categories[$id]['name']; ?>
                </div>
            </div>

            <div class="control-group">
                <label><?php print $PMF_LANG['ad_categ_desc']; ?>:</label>
                <div class="controls">
                    <?php print $categories[$id]['description']; ?>
                </div>
            </div>

            <div class="control-group">
                <div class="controls">
                    <label class="radio">
                        <input type="radio" checked name="deleteall" value="yes" />
                        <?php print $PMF_LANG['ad_categ_deletealllang']; ?>
                    </label>
                </div>
            </div>

            <div class="control-group">
                <div class="controls">
                    <label class="radio">
                        <input type="radio" name="deleteall" value="no" />
                        <?php print $PMF_LANG['ad_categ_deletethislang']; ?>
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <input class="btn-danger" type="submit" name="submit" value="<?php print $PMF_LANG['ad_categ_del_yes']; ?>" />&nbsp;&nbsp;
                <input class="btn-success" type="reset" onclick="javascript:history.back();" value="<?php print $PMF_LANG['ad_categ_del_no']; ?>" />
            </div>
        </form>
<?php
} else {
    print $PMF_LANG['err_NotAuth'];
}