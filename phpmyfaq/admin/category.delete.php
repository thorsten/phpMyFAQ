<?php
/******************************************************************************
 * File:				category.delete.php
 * Description:			deletes a category
 * Author:				Thorsten Rinne <thorsten@phpmyfaq.de>
 * Date:				2003-12-20
 * Last change:			2004-07-26
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
print "<h2>".$PMF_LANG["ad_menu_categ_edit"]."</h2>\n";
if ($permission["delcateg"]) {
?>
	<form action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>" method="post">
    <fieldset>
    <legend><?php print $PMF_LANG["ad_categ_deletesure"]; ?></legend>
    <div align="center">
	<input type="hidden" name="aktion" value="removecategory" />
	<input type="hidden" name="cat" value="<?php print $_GET["cat"]; ?>" />
	<input class="submit" type="submit" name="submit" value="<?php print $PMF_LANG["ad_categ_del_yes"]; ?>" style="color: Red;" />&nbsp;&nbsp;
	<input class="submit" type="Reset" value="<?php print $PMF_LANG["ad_categ_del_no"]; ?>" />
    </div>
    </fieldset>
	</form>
	</div>
<?php
	}
else {
	print $PMF_LANG["err_NotAuth"];
	}
?>
