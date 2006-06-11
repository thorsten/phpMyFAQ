<?php
/**
* $Id: category.add.php,v 1.8 2006-06-11 20:35:45 matteo Exp $
*
* Adds a category
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
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));

    exit();
}

print "<h2>".$PMF_LANG["ad_categ_new"]."</h2>\n";
if ($permission["addcateg"]) {
    $cat = new PMF_Category;
?>
    <form action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>" method="post">
    <fieldset>
    <legend><?php print $PMF_LANG["ad_categ_new"]; ?></legend>
    <input type="hidden" name="aktion" value="savecategory" />
    <input type="hidden" name="parent_id" value="<?php if (isset($_GET["cat"])) { print $_GET["cat"]; } else { print "0"; } ?>" />
<?php
    if (isset($_GET["cat"])) {
?>
    <p><?php print $PMF_LANG["msgMainCategory"].": ".$cat->categoryName[$_GET["cat"]]["name"]." (".$languageCodes[strtoupper($cat->categoryName[$_GET["cat"]]["lang"])].")"; ?></p>
<?php
    }
?>
    <label class="left"><?php print $PMF_LANG["ad_categ_titel"]; ?>:</label>
    <input type="text" name="name" size="30" style="width: 250px;" /><br />

    <label class="left"><?php print $PMF_LANG["ad_categ_lang"]; ?>:</label>
    <select name="lang" size="1">
<?php
    if (isset($_GET["cat"])) {
        print languageOptions($cat->categoryName[$_GET["cat"]]["lang"], true);
    } else {
        print languageOptions($LANGCODE);
    }
?>
    </select><br />

    <label class="left"><?php print $PMF_LANG["ad_categ_desc"]; ?>:</label>
    <input type="text" name="description" size="30" style="width: 250px;" /><br />

    <input class="submit" style="margin-left: 190px;" type="submit" name="submit" value="<?php print $PMF_LANG["ad_categ_add"]; ?>" />

    </fieldset>
    </form>
<?php
} else {
    print $PMF_LANG["err_NotAuth"];
}
?>
