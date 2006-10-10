<?php
/**
* $Id: category.move.php,v 1.11 2006-10-10 16:48:59 thorstenr Exp $
*
* Select a category to move
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2004-04-29
* @copyright    (c) 2004-2006 phpMyFAQ Team
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

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission["editcateg"]) {
    $id = $_GET['cat'];
    $parent_id = $_GET['parent_id'];
    $cat = new PMF_Category($LANGCODE);
    $categories = $cat->getAllCategories();
    unset($cat->categories);
    $cat->getCategories($parent_id, false);
    $cat->buildTree($parent_id);
    
    $header = sprintf('%s: <em>%s</em>',
        $PMF_LANG['ad_categ_move'],
        $cat->categoryName[$id]['name']);

    printf('<h2>%s</h2>', $header);
?>
	<form action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>" method="post">
    <fieldset>
        <legend><?php print $PMF_LANG["ad_categ_change"]; ?></legend>
	    <input type="hidden" name="action" value="changecategory" />
	    <input type="hidden" name="cat" value="<?php print $id; ?>" />
	    <div class="row">
               <select name="change" size="1">
<?php
                    foreach ($cat->catTree as $cat) {
                       if ($id != $cat["id"]) {
                          printf("<option value=\"%s\">%s%s</option>",$cat["id"],$indent,$cat["name"]);
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
?>
