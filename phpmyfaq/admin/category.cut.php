<?php
/**
 * Cuts out a category
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
 * @copyright 2003-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2003-12-25
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission["editcateg"]) {

    $categoryNode         = new PMF_Category_Node();
    $categoryDataProvider = new PMF_Category_Tree_DataProvider_SingleQuery($LANGCODE);
    $categoryTreeHelper   = new PMF_Category_Tree_Helper(new PMF_Category_Tree($categoryDataProvider));
    
    $id           = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);
    $categoryData = $categoryNode->fetch($id);
    $header       = sprintf('%s: <em>%s</em>', $PMF_LANG['ad_categ_move'], $categoryData->name);
    
    printf('<h2>%s</h2>', $header);
?>
        <header>
            <h2><?php print $header ?></h2>
        </header>
        <form action="?action=pastecategory" method="post">
            <input type="hidden" name="cat" value="<?php print $id; ?>" />
            <input type="hidden" name="csrf" value="<?php print $user->getCsrfTokenFromSession(); ?>" />
            <p>
                <label><?php print $PMF_LANG["ad_categ_paste2"]; ?></label>
                <select name="after" size="1">
<?php

    foreach ($categoryTreeHelper as $categoryId => $categoryName) {
        
        $indent = str_repeat('.', $categoryTreeHelper->indent);
        if ($id != $categoryId) {
            printf("<option value=\"%s\">%s%s</option>\n", $categoryId, $indent, $categoryName);
        }
    }

    if ($categoryData->parent_id != 0) {
        printf('<option value="0">%s</option>', $PMF_LANG['ad_categ_new_main_cat']);
    }
?>
                </select>
            </p>
            <p>
                <input class="submit" type="submit" name="submit" value="<?php print $PMF_LANG["ad_categ_updatecateg"]; ?>" />
            </p>
        </form>
<?php
} else {
	print $PMF_LANG["err_NotAuth"];
}