<?php
/**
 * Adds a category
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
 * @copyright 2003-2009 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2003-12-20
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

print "<h2>".$PMF_LANG["ad_categ_new"]."</h2>\n";

if ($permission["addcateg"]) {

    $category  = new PMF_Category($current_admin_user, $current_admin_groups, false);
    $parent_id = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);
?>
    <form action="?action=savecategory" method="post">
    <fieldset>
    <legend><?php print $PMF_LANG["ad_categ_new"]; ?></legend>
    <input type="hidden" name="lang" value="<?php print $LANGCODE; ?>" />
    <input type="hidden" name="parent_id" value="<?php print $parent_id; ?>" />
<?php
    if ($parent_id > 0) {
        $user_allowed  = $category->getPermissions('user', array($parent_id));
        $group_allowed = $category->getPermissions('group', array($parent_id));
?>
    <input type="hidden" name="restricted_users" value="<?php print $user_allowed[0]; ?>" />
    <input type="hidden" name="restricted_groups" value="<?php print $group_allowed[0]; ?>" />
<?php
        printf("<p>%s: %s (%s)</p>",
            $PMF_LANG["msgMainCategory"],
            $category->categoryName[$parent_id]["name"],
            $languageCodes[PMF_String::strtoupper($category->categoryName[$parent_id]["lang"])]);
    }
?>
    <label class="left"><?php print $PMF_LANG["ad_categ_titel"]; ?>:</label>
    <input type="text" name="name" size="30" style="width: 300px;" /><br />

    <label class="left"><?php print $PMF_LANG["ad_categ_desc"]; ?>:</label>
    <textarea name="description" rows="3" cols="80" style="width: 300px;"></textarea><br />
    
    <label class="left"><?php print $PMF_LANG["ad_categ_owner"]; ?>:</label>
    <select name="user_id" size="1">
    <?php print $user->getAllUserOptions(1); ?>
    </select><br />

<?php
    if ($parent_id == 0) {
        if ($faqconfig->get('main.permLevel') != 'basic') {
?>
    <label class="left" for="grouppermission"><?php print $PMF_LANG['ad_entry_grouppermission']; ?></label>
    <input type="radio" name="grouppermission" class="active" value="all" checked="checked" /> <?php print $PMF_LANG['ad_entry_all_groups']; ?> 
    <input type="radio" name="grouppermission" class="active" value="restricted" /> <?php print $PMF_LANG['ad_entry_restricted_groups']; ?> 
    <select name="restricted_groups" size="1"><?php print $user->perm->getAllGroupsOptions(1); ?></select><br />

<?php
        } else {
?>
    <input type="hidden" name="grouppermission" class="active" value="all" />
<?php	
        }
?>
    <label class="left" for="userpermission"><?php print $PMF_LANG['ad_entry_userpermission']; ?></label>
    <input type="radio" name="userpermission" class="active" value="all" checked="checked" /> <?php print $PMF_LANG['ad_entry_all_users']; ?> 
    <input type="radio" name="userpermission" class="active" value="restricted" /> <?php print $PMF_LANG['ad_entry_restricted_users']; ?> 
    <select name="restricted_users" size="1"><?php print $user->getAllUserOptions(1); ?></select><br />

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