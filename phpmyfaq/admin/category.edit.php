<?php
/**
* $Id: category.edit.php,v 1.8 2006-06-11 15:26:21 matteo Exp $
*
* Edits a category
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2003-03-10
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
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission["editcateg"]) {
    $cat = new PMF_Category;
    $categories = $cat->getAllCategories();
    $id = $_GET["cat"];
    print "<h2>".$PMF_LANG["ad_categ_edit_1"]." <em>".$categories[$id]["name"]."</em> ".$PMF_LANG["ad_categ_edit_2"]."</h2>";
?>
	<form action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>" method="post">
	<fieldset>
	<legend><?php print $PMF_LANG["ad_categ_edit_1"]." <em>".$categories[$id]["name"]."</em> ".$PMF_LANG["ad_categ_edit_2"]; ?></legend>
	<input type="hidden" name="aktion" value="updatecategory" />
	<input type="hidden" name="cat" value="<?php print $id; ?>" /> 
	<div class="row"><span class="label"><strong><?php print $PMF_LANG["ad_categ_titel"]; ?>:</strong></span>
    <input class="admin" type="text" name="name" size="30" style="width: 250px;" value="<?php print $categories[$id]["name"]; ?>" /></div>
    <div class="row"><span class="label"><strong><?php print $PMF_LANG["ad_categ_lang"]; ?>:</strong></span>
    <select name="lang" size="1">
    <?php print languageOptions($categories[$id]["lang"]); ?>
    </select></div>
    <div class="row"><span class="label"><strong><?php print $PMF_LANG["ad_categ_desc"]; ?>:</strong></span>
    <input class="admin" type="text" name="description" size="30" style="width: 250px;" value="<?php print $categories[$id]["description"]; ?>" /></div>
    <div class="row"><span class="label"><strong><?php print $PMF_LANG["ad_categ_owner"]; ?>:</strong></span>
    <!-- <select name="cat_owner" size="1"> -->
<?php
        /*$result = $db->query("SELECT id, name, realname FROM ".SQLPREFIX."faquser ORDER BY id");
        while ($row = $db->fetch_object($result)) {
            print '<option value="'.$row->id.'"';
            if ($row->id == $categories[$id]["user_id"]) {
                print ' selected="selected"';
            }
            print '>';
            print $row->name;
            if (strlen($row->realname) > 0) {
                print ' ('.$row->realname.')';
            }
            print '</option>';
        }*/
?>
    <!-- </select></div> -->
    <div class="row"><span class="label"><strong>&nbsp;</strong></span>
    <input class="submit" type="submit" name="submit" value="<?php print $PMF_LANG["ad_categ_updatecateg"]; ?>" /></div>
    </fieldset>
	</form>
<?php
} else {
	print $PMF_LANG["err_NotAuth"];
}
