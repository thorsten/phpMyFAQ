<?php
/**
 * Select a category to move
 * 
 * PHP Version 5.2
 * 
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2004-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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
    $category   = new PMF_Category($current_admin_user, $current_admin_groups, false);
    $categories = $category->getAllCategories();
    $category->categories = null;
    unset($category->categories);
    $category->getCategories($parent_id, false);
    $category->buildTree($parent_id);
    
    $header = sprintf('%s: <em>%s</em>',
        $PMF_LANG['ad_categ_move'],
        $category->categoryName[$id]['name']);
?>
        <header>
            <h2><?php print $header ?></h2>
        </header>
        <form action="?action=changecategory" method="post">
            <input type="hidden" name="cat" value="<?php print $id; ?>" />
            <input type="hidden" name="csrf" value="<?php print $user->getCsrfTokenFromSession(); ?>" />
            <p>
                <label><?php print $PMF_LANG["ad_categ_change"]; ?></label>
                   <select name="change" size="1">
<?php
                    foreach ($category->catTree as $cat) {
                       if ($id != $cat["id"]) {
                          printf("<option value=\"%s\">%s</option>", $cat['id'], $cat['name']);
                       }
                   }
?>
               </select
            </p>
            <p>
                <input class="submit" type="submit" name="submit" value="<?php print $PMF_LANG["ad_categ_updatecateg"]; ?>" />
            </p>
        </form>
<?php
    printf('<p>%s</p>', $PMF_LANG['ad_categ_remark_move']);
} else {
    print $PMF_LANG["err_NotAuth"];
}