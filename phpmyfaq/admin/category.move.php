<?php
/**
 * Select a category to move
 *
 * @package    phpMyFAQ
 * @subpackage Administration
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2004-04-29
 * @copyright  2004-2009 phpMyFAQ Team
 * @version    SVN: $Id$
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

    printf('<h2>%s</h2>', $header);
?>
	<form action="?action=changecategory" method="post">
    <fieldset>
        <legend><?php print $PMF_LANG["ad_categ_change"]; ?></legend>
	    <input type="hidden" name="cat" value="<?php print $id; ?>" />
	    <div class="row">
               <select name="change" size="1">
<?php
                    foreach ($category->catTree as $cat) {
                       if ($id != $cat["id"]) {
                          printf("<option value=\"%s\">%s</option>", $cat['id'], $cat['name']);
                       }
                   }
?>
               </select>&nbsp;&nbsp;
               <input class="submit" type="submit" name="submit" value="<?php print $PMF_LANG["ad_categ_updatecateg"]; ?>" />
            </div>
    </fieldset>
    </form>

<?php
    printf('<p>%s</p>', $PMF_LANG['ad_categ_remark_move']);
} else {
    print $PMF_LANG["err_NotAuth"];
}