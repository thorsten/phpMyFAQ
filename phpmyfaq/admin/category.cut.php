<?php
/**
 * Cuts out a category
 *
 * @package    phpMyFAQ
 * @subpackage Administration
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2003-12-25
 * @copyright  2003-2009 phpMyFAQ Team
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
    $category = new PMF_Category($current_admin_user, $current_admin_groups, false);
    $category->buildTree();
    
    $id        = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);
    $parent_id = $category->categoryName[$id]['parent_id'];
    $header    = sprintf('%s: <em>%s</em>',
                    $PMF_LANG['ad_categ_move'],
                    $category->categoryName[$id]['name']);

    printf('<h2>%s</h2>', $header);
?>

    <form action="?action=pastecategory" method="post">
    <fieldset>
        <legend><?php print $PMF_LANG["ad_categ_paste2"]; ?></legend>
	    <input type="hidden" name="cat" value="<?php print $id; ?>" />
	    <div class="row">
            <select name="after" size="1">
<?php

    foreach ($category->catTree as $cat) {
        $indent = '';
        for ($j = 0; $j < $cat['indent']; $j++) {
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
            </select>&nbsp;&nbsp;
            <input class="submit" type="submit" name="submit" value="<?php print $PMF_LANG["ad_categ_updatecateg"]; ?>" />
        </div>
    </fieldset>
    </form>
<?php
} else {
	print $PMF_LANG["err_NotAuth"];
}