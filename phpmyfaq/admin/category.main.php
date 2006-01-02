<?php
/**
* $Id: category.main.php,v 1.10 2006-01-02 20:06:28 thorstenr Exp $
*
* List all categories in the admin section
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2003-12-20
* @copyright    (c) 2001-2006 phpMyFAQ Team
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
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

print "<h2>".$PMF_LANG["ad_categ_existing"]."</h2>\n";

if ($permission["editcateg"]) {
    if (isset($_POST['language'])) {
        $lang = $_POST['language'];
    } else {
        $lang = $LANGCODE;
    }
?>
    <form action="<?php print $currentLink; ?>" enctype="multipart/form-data" method="post">
    <input type="hidden" name="aktion" value="category" />
    <fieldset>
        <legend><?php print $PMF_LANG['ad_categ_select']; ?></legend>
        <?php print selectLanguages($lang); ?>
	    <input type="submit" class="submit" value="OK" />
    </fieldset>
    </form>
<?php
    $tree = new Category($lang);
    $tree->buildTree();

    foreach ($tree->catTree as $cat) {
        $indent = '';
        for ($i = 0; $i < $cat['indent']; $i++) {
            $indent .= '&nbsp;&nbsp;&nbsp;';
        }
        printf("%s<strong style=\"vertical-align: top;\">&middot; %s</strong> ", $indent, $cat["name"]);
        printf('<a href="?aktion=addcategory&amp;cat=%s" title="%s"><img src="images/add.gif" width="17" height="18" alt="%s" title="%s" border="0" /></a>', $cat['id'], $PMF_LANG['ad_kateg_add'], $PMF_LANG['ad_kateg_add'], $PMF_LANG['ad_kateg_add']);
        printf('<a href="?aktion=editcategory&amp;cat=%s" title="%s"><img src="images/edit.gif" width="18" height="18" border="0" title="%s" alt="%s" /></a>', $cat['id'], $PMF_LANG['ad_kateg_rename'], $PMF_LANG['ad_kateg_rename'], $PMF_LANG['ad_kateg_rename']);
        if (count($tree->getChildren($cat['id'])) == 0) {
            printf('<a href="?aktion=deletecategory&amp;cat=%s" title="%s"><img src="images/delete.gif" width="17" height="18" alt="%s" title="%s" border="0" /></a>', $cat['id'], $PMF_LANG['ad_categ_delete'], $PMF_LANG['ad_categ_delete'], $PMF_LANG['ad_categ_delete']);
        }
        printf('<a href="?aktion=cutcategory&amp;cat=%s" title="%s"><img src="images/cut.gif" width="16" height="16" alt="%s" border="0" title="%s" /></a>', $cat['id'], $PMF_LANG['ad_categ_cut'], $PMF_LANG['ad_categ_cut'], $PMF_LANG['ad_categ_cut']);
        printf('<a href="?aktion=movecategory&amp;cat=?parent_id=%s" title="%s"><img src="images/move.gif" width="16" height="16" alt="%s" border="0" title="%s" /></a>', $cat['id'], $cat['parent_id'], $PMF_LANG['ad_categ_move'], $PMF_LANG['ad_categ_move'], $PMF_LANG['ad_categ_move']);
        print "<br />";
    }
?>
    <p><img src="images/arrow.gif" width="11" height="11" alt="" border="0" /> <a href="<?php print $currentLink.'&amp;aktion=addcategory'; ?>"><?php print $PMF_LANG['ad_kateg_add']; ?></a></p>
	<p><?php print $PMF_LANG['ad_categ_remark']; ?></p>
<?php
} else {
	print $PMF_LANG["err_NotAuth"];
}
?>
