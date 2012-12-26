<?php
/**
 * Cuts out a category
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
 * @copyright 2003-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-12-25
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission["editcateg"]) {
    $category = new PMF_Category($faqConfig, array(), false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $category->buildTree();
    
    $id        = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);
    $parent_id = $category->categoryName[$id]['parent_id'];
    $header    = sprintf('%s: <em>%s</em>',
                    $PMF_LANG['ad_categ_move'],
                    $category->categoryName[$id]['name']);
?>
        <header>
            <h2><?php print $header ?></h2>
        </header>
        <form class="form-horizontal" action="?action=pastecategory" method="post">
            <input type="hidden" name="cat" value="<?php print $id; ?>" />
            <input type="hidden" name="csrf" value="<?php print $user->getCsrfTokenFromSession(); ?>" />
            <div class="control-group">
                <label class="control-label"><?php print $PMF_LANG["ad_categ_paste2"]; ?></label>
                <div class="controls">
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
                    </select>
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