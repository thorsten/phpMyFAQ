<?php
/******************************************************************************
 * File:				category.main.php
 * Description:			list all categories
 * Author:				Thorsten Rinne <thorsten@phpmyfaq.de>
 * Date:				2003-12-20
 * Last change:			2004-12-10
 * Copyright:           (c) 2001-2004 Thorsten Rinne
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
 ******************************************************************************/

print "<h2>".$PMF_LANG["ad_categ_existing"]."</h2>\n";

if ($permission["editcateg"]) {
    
    if (isset($_POST['language'])) {
        $lang = $_POST['language'];
    } else {
        $lang = $LANGCODE;
    }
?>

    <form action="<?php print $_SERVER['PHP_SELF'].$linkext; ?>" enctype="multipart/form-data" method="POST">
    <input type="hidden" name="aktion" value="category" />
    <fieldset>
    <legend>Select category language</legend>
    <select name="language" id="language">
    <?php print languageOptions($lang); ?>
	</select>
	<input type="submit" class="submit" value="OK" />
    </fieldset>
    </form>

<?php
    $tree = new Category($lang);
    $tree->buildTree();
    
    foreach ($tree->catTree as $cat) {
        $indent = "";
        for ($i = 0; $i < $cat["indent"]; $i++) {
            $indent .= "&nbsp;&nbsp;&nbsp;";
        }
        print $indent."<strong style=\"vertical-align: top;\">&middot; ".$cat["name"]."</strong> ";
        print "<a href=\"".$_SERVER["PHP_SELF"].$linkext."&amp;aktion=addcategory&amp;cat=".$cat["id"]."\" title=\"".$PMF_LANG["ad_kateg_add"]."\"><img src=\"images/add.gif\" width=\"17\" height=\"18\" alt=\"".$PMF_LANG["ad_kateg_add"]."\" title=\"".$PMF_LANG["ad_kateg_add"]."\" border=\"0\" /></a>";
        print "<a href=\"".$_SERVER["PHP_SELF"].$linkext."&amp;aktion=editcategory&amp;cat=".$cat["id"]."\" title=\"".$PMF_LANG["ad_kateg_rename"]."\"><img src=\"images/edit.gif\" width=\"18\" height=\"18\" border=\"0\" /></a>";
        if (count($tree->getChildren($cat["id"])) == 0) {
            print "<a href=\"".$_SERVER["PHP_SELF"].$linkext."&amp;aktion=deletecategory&amp;cat=".$cat["id"]."\" title=\"".$PMF_LANG["ad_categ_delete"]."\"><img src=\"images/delete.gif\" width=\"17\" height=\"18\" alt=\"".$PMF_LANG["ad_categ_delete"]."\" title=\"".$PMF_LANG["ad_categ_delete"]."\" border=\"0\" /></a>";
        }
        print "<a href=\"".$_SERVER["PHP_SELF"].$linkext."&amp;aktion=cutcategory&amp;cat=".$cat["id"]."\" title=\"".$PMF_LANG["ad_categ_cut"]."\"><img src=\"images/cut.gif\" width=\"16\" height=\"16\" alt=\"".$PMF_LANG["ad_categ_cut"]."\" border=\"0\" title=\"".$PMF_LANG["ad_categ_cut"]."\" /></a>\n";
        if ($cat["parent_id"] == 0) {
            print "<a href=\"".$_SERVER["PHP_SELF"].$linkext."&amp;aktion=movecategory&amp;cat=".$cat["id"]."\" title=\"".$PMF_LANG["ad_categ_move"]."\"><img src=\"images/move.gif\" width=\"16\" height=\"16\" alt=\"".$PMF_LANG["ad_categ_move"]."\" border=\"0\" title=\"".$PMF_LANG["ad_categ_move"]."\" /></a>\n";
        }
        print "<br />";
    }
?>
	<p><img src="images/arrow.gif" width="11" height="11" alt="" border="0"> <a href="<?php print $_SERVER["PHP_SELF"].$linkext."&amp;aktion=addcategory"; ?>"><?php print $PMF_LANG["ad_kateg_add"]; ?></a></p>
	<p><?php print $PMF_LANG["ad_categ_remark"]; ?></p>
<?php
} else {
	print $PMF_LANG["err_NotAuth"];
}
?>