<?php
/**
 * Select a category to move
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2004-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2004-04-29
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission["editcateg"]) {
    $id         = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT);
    $parent_id  = PMF_Filter::filterInput(INPUT_GET, 'parent_id', FILTER_VALIDATE_INT);
    $category = new PMF_Category($faqConfig, array(), false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $categories = $category->getAllCategories();

    $category->categories = null;
    unset($category->categories);
    $category->getCategories($parent_id, false);
    $category->buildTree($parent_id);
    
    $header = sprintf('%s: <em>%s</em>',
        $PMF_LANG['ad_categ_move'],
        $category->categories[$id]['name']
    );
?>
        <header>
            <h2><i class="icon-list"></i> <?php print $header ?></h2>
        </header>
        <form class="form-horizontal" action="?action=changecategory" method="post" accept-charset="utf-8">
            <input type="hidden" name="cat" value="<?php print $id; ?>" />
            <input type="hidden" name="csrf" value="<?php print $user->getCsrfTokenFromSession(); ?>" />
            <div class="control-group">
                <label class="control-label"><?php print $PMF_LANG["ad_categ_change"]; ?></label>
                <div class="controls">
                   <select name="change" size="1">
<?php
                    foreach ($category->categories as $cat) {
                       if ($id != $cat["id"]) {
                          printf("<option value=\"%s\">%s</option>", $cat['id'], $cat['name']);
                       }
                   }
?>
                    </select>
                    <?php printf('<p class="help-block">%s</p>', $PMF_LANG['ad_categ_remark_move']); ?>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit" name="submit">
                    <?php print $PMF_LANG["ad_categ_updatecateg"]; ?>
                </button>
            </div>
        </form>
<?php
} else {
    print $PMF_LANG["err_NotAuth"];
}