<?php
/**
* $Id: category.delete.php,v 1.10 2006-09-19 21:39:38 matteo Exp $
*
* Deletes a category
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2003-12-20
* @copyright    (c) 2003-2006 phpMyFAQ Team
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

print "<h2>".$PMF_LANG["ad_menu_categ_edit"]."</h2>\n";
if ($permission["delcateg"]) {
    $cat = new PMF_Category($LANGCODE);
    $categories = $cat->getAllCategories();
    $id = $_GET["cat"];
?>
	<form action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>" method="post">
    <fieldset>
    <legend><?php print $PMF_LANG["ad_categ_deletesure"]; ?></legend>

	<input type="hidden" name="action" value="removecategory" />
	<input type="hidden" name="cat" value="<?php print $id; ?>" />
        <input type="hidden" name="lang" value="<?php print $LANGCODE; ?>" />

        <label class="left"><?php print $PMF_LANG["ad_categ_titel"]; ?>:</label>
        <?php print $categories[$id]["name"]; ?> <br />

        <label class="left"><?php print $PMF_LANG["ad_categ_desc"]; ?>:</label>
        <?php print $categories[$id]["description"]; ?> <br />

        <label class="left"><?php print $PMF_LANG["ad_categ_deletealllang"]; ?></label>
        <input type="radio" checked name="deleteall" value="yes" /> <br /> 
        <label class="left"><?php print $PMF_LANG["ad_categ_deletethislang"]; ?></label>
        <input type="radio" name="deleteall" value="no" />  <br />           

        <br />
	<input class="submit" style="margin-left: 190px;color: Red;" type="submit" name="submit" value="<?php print $PMF_LANG["ad_categ_del_yes"]; ?>" />&nbsp;&nbsp;
	<input class="submit" type="reset" onclick="javascript:history.back();" value="<?php print $PMF_LANG["ad_categ_del_no"]; ?>" />

    </fieldset>
	</form>
	</div>
<?php
} else {
	print $PMF_LANG["err_NotAuth"];
}
?>