<?php
/**
 * $Id: category.edit.php,v 1.23 2007-04-20 10:00:22 thorstenr Exp $
 *
 * Edits a category
 *
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since       2003-03-10
 * @copyright   (c) 2003-2007 phpMyFAQ Team
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

if ($permission['editcateg']) {

    $id = (int)$_GET['cat'];
    $category = new PMF_Category($LANGCODE, $current_admin_user, $current_admin_groups, false);
    $categories = $category->getAllCategories();
    $user_permission = $category->getPermissions('user', array($id));

    if ($user_permission[0] == -1) {
        $all_users = true;
        $restricted_users = false;
    } else {
        $all_users = false;
        $restricted_users = true;
    }

    $group_permission = $category->getPermissions('group', array($id));
    if ($group_permission[0] == -1) {
        $all_groups = true;
        $restricted_groups = false;
    } else {
        $all_groups = false;
        $restricted_groups = true;
    }

    printf("<h2>%s <em>%s</em> %s</h2>\n",
        $PMF_LANG['ad_categ_edit_1'],
        $categories[$id]['name'],
        $PMF_LANG['ad_categ_edit_2']);
?>
    <form action="<?php print $_SERVER['PHP_SELF']; ?>" method="post">
    <input type="hidden" name="action" value="updatecategory" />
    <input type="hidden" name="id" value="<?php print $id; ?>" />
    <input type="hidden" name="lang" value="<?php print $categories[$id]['lang']; ?>" />
    <input type="hidden" name="parent_id" value="<?php print $categories[$id]['parent_id']; ?>" />

    <fieldset>
    <legend><?php print $PMF_LANG['ad_categ_edit_1']." <em>".$categories[$id]['name']."</em> ".$PMF_LANG['ad_categ_edit_2']; ?></legend>

        <label class="left"><?php print $PMF_LANG['ad_categ_titel']; ?>:</label>
        <input type="text" name="name" size="30" style="width: 250px;" value="<?php print $categories[$id]['name']; ?>" /><br />

        <label class="left"><?php print $PMF_LANG['ad_categ_desc']; ?>:</label>
        <input type="text" name="description" size="30" style="width: 250px;" value="<?php print $categories[$id]['description']; ?>" /><br />

        <label class="left"><?php print $PMF_LANG['ad_categ_owner']; ?>:</label>
        <select name="user_id" size="1">
        <?php print $user->getAllUserOptions($categories[$id]['user_id']); ?>
        </select><br />
<?php
    if ($groupSupport) {
?>
        <label class="left" for="grouppermission"><?php print $PMF_LANG['ad_entry_grouppermission']; ?></label>
        <input type="radio" name="grouppermission" class="active" value="all" <?php print ($all_groups ? 'checked="checked"' : ''); ?>/> <?php print $PMF_LANG['ad_entry_all_groups']; ?> <input type="radio" name="grouppermission" class="active" value="restricted" <?php print ($restricted_groups ? 'checked="checked"' : ''); ?>/> <?php print $PMF_LANG['ad_entry_restricted_groups']; ?> <select name="restricted_groups" size="1"><?php print $user->perm->getAllGroupsOptions($group_permission); ?></select><br />
<?php
    }
?>
        <label class="left" for="userpermission"><?php print $PMF_LANG['ad_entry_userpermission']; ?></label>
        <input type="radio" name="userpermission" class="active" value="all" <?php print ($all_users ? 'checked="checked"' : ''); ?>/> <?php print $PMF_LANG['ad_entry_all_users']; ?> <input type="radio" name="userpermission" class="active" value="restricted" <?php print ($restricted_users ? 'checked="checked"' : ''); ?>/> <?php print $PMF_LANG['ad_entry_restricted_users']; ?> <select name="restricted_users" size="1"><?php print $user->getAllUserOptions($user_permission[0]); ?></select><br />

        <input class="submit" style="margin-left: 190px;" type="submit" name="submit" value="<?php print $PMF_LANG['ad_categ_updatecateg']; ?>" />
    </fieldset>
    </form>
<?php
} else {
    print $PMF_LANG['err_NotAuth'];
}
