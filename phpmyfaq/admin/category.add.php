<?php
/**
* $Id: category.add.php,v 1.17 2007-02-04 12:46:09 thorstenr Exp $
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
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

print "<h2>".$PMF_LANG["ad_categ_new"]."</h2>\n";
if ($permission["addcateg"]) {
    $cat = new PMF_Category($LANGCODE);
?>
    <form action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>" method="post">
    <fieldset>
    <legend><?php print $PMF_LANG["ad_categ_new"]; ?></legend>
    <input type="hidden" name="action" value="savecategory" />
    <input type="hidden" name="lang" value="<?php print $LANGCODE; ?>" />
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

    <label class="left"><?php print $PMF_LANG["ad_categ_desc"]; ?>:</label>
    <input type="text" name="description" size="30" style="width: 250px;" /><br />

    <label class="left"><?php print $PMF_LANG["ad_categ_owner"]; ?>:</label>
    <select name="user_id" size="1">
    <?php print $user->getAllUserOptions(1); ?>
    </select><br />
    
    <label class="left" for="userpermission"><?php print $PMF_LANG['ad_entry_userpermission']; ?></label>
    <input type="radio" name="userpermission" class="active" value="all" checked="checked" /> <?php print $PMF_LANG['ad_entry_all_users']; ?> <input type="radio" name="userpermission" class="active" value="restricted" /> <?php print $PMF_LANG['ad_entry_restricted_users']; ?> <select name="restricted_users" size="1"><?php print $user->getAllUserOptions(1); ?></select><br />

<?php
    if ($groupSupport) {
?>    
    <label class="left" for="grouppermission"><?php print $PMF_LANG['ad_entry_grouppermission']; ?></label>
    <input type="radio" name="grouppermission" class="active" value="all" checked="checked" /> <?php print $PMF_LANG['ad_entry_all_groups']; ?> <input type="radio" name="grouppermission" class="active" value="restricted" /> <?php print $PMF_LANG['ad_entry_restricted_groups']; ?> <select name="restricted_groups" size="1"><?php print $user->getAllUserOptions(1); ?></select><br />

<?php
    }
?>
    <input class="submit" style="margin-left: 190px;" type="submit" name="submit" value="<?php print $PMF_LANG["ad_categ_add"]; ?>" />

    </fieldset>
    </form>
<?php
} else {
    print $PMF_LANG["err_NotAuth"];
}