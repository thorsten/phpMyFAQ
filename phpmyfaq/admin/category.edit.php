<?php
/**
 * Edits a category
 *
 * PHP Version 5.4
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
 * @since     2003-03-10
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON'){
        $protocol = 'https';
    }
    header('Location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission['editcateg']) {

    $categoryId = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);
    $category   = new PMF_Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $categories     = $category->getAllCategories();
    $userPermission = $category->getPermissions('user', array($categoryId));

    if ($userPermission[0] == -1) {
        $allUsers        = true;
        $restrictedUsers = false;
    } else {
        $allUsers        = false;
        $restrictedUsers = true;
    }

    $groupPermission = $category->getPermissions('group', array($categoryId));
    if ($groupPermission[0] == -1) {
        $allGroups        = true;
        $restrictedGroups = false;
    } else {
        $allGroups        = false;
        $restrictedGroups = true;
    }

    $header = $PMF_LANG['ad_categ_edit_1'] . ' ' . $categories[$categoryId]['name'] . ' ' . $PMF_LANG['ad_categ_edit_2'];
?>

        <header>
            <h2><i class="icon-list"></i> <?php echo $header; ?></h2>
        </header>

        <form class="form-horizontal" action="?action=updatecategory" method="post" accept-charset="utf-8">
            <input type="hidden" name="id" value="<?php echo $categoryId; ?>">
            <input type="hidden" id="catlang" name="catlang" value="<?php echo $categories[$categoryId]['lang']; ?>">
            <input type="hidden" name="parent_id" value="<?php echo $categories[$categoryId]['parent_id']; ?>">
            <input type="hidden" name="csrf" value="<?php echo $user->getCsrfTokenFromSession(); ?>">

            <div class="control-group">
                <label><?php echo $PMF_LANG['ad_categ_titel']; ?>:</label>
                <div class="controls">
                    <input type="text" id="name" name="name" value="<?php echo $categories[$categoryId]['name']; ?>">
                </div>
            </div>

            <div class="control-group">
                <label><?php echo $PMF_LANG['ad_categ_desc']; ?>:</label>
                <div class="controls">
                    <textarea id="description" name="description" rows="3" cols="80"><?php echo $categories[$categoryId]['description']; ?></textarea>
                </div>
            </div>

            <div class="control-group">
                <label><?php echo $PMF_LANG['ad_categ_owner']; ?>:</label>
                <div class="controls">
                    <select name="user_id" size="1">
                        <?php echo $user->getAllUserOptions($categories[$categoryId]['user_id']); ?>
                    </select>
                </div>
            </div>
<?php
    if ($faqConfig->get('security.permLevel') != 'basic') {
?>
            <div class="control-group">
                <label><?php echo $PMF_LANG['ad_entry_grouppermission']; ?></label>
                <div class="controls">
                    <label class="radio">
                        <input type="radio" name="grouppermission" value="all" <?php echo ($allGroups ? 'checked="checked"' : ''); ?>>
                        <?php echo $PMF_LANG['ad_entry_all_groups']; ?>
                    </label>
                    <label class="radio">
                        <input type="radio" name="grouppermission" value="restricted" <?php echo ($restrictedGroups ? 'checked="checked"' : ''); ?>>
                        <?php echo $PMF_LANG['ad_entry_restricted_groups']; ?>
                    </label>
                    <select name="restricted_groups[]" size="3" multiple>
                        <?php echo $user->perm->getAllGroupsOptions($groupPermission); ?>
                    </select>
                </div>
            </div>
<?php
    } else {
?>
            <input type="hidden" name="grouppermission" value="all">
<?php 
    }
?>
            <div class="control-group">
                <label><?php echo $PMF_LANG['ad_entry_userpermission']; ?></label>
                <div class="controls">
                    <label class="radio">
                        <input type="radio" name="userpermission" value="all" <?php echo ($allUsers ? 'checked="checked"' : ''); ?>>
                        <?php echo $PMF_LANG['ad_entry_all_users']; ?>
                    </label>
                    <label class="radio">
                        <input type="radio" name="userpermission" value="restricted" <?php echo ($restrictedUsers ? 'checked="checked"' : ''); ?>>
                        <?php echo $PMF_LANG['ad_entry_restricted_users']; ?>
                    </label>
                    <select name="restricted_users" size="1">
                        <?php echo $user->getAllUserOptions($userPermission[0]); ?>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit" name="submit">
                    <?php echo $PMF_LANG['ad_categ_updatecateg']; ?>
                </button>
            </div>
    </form>
<?php
} else {
    echo $PMF_LANG['err_NotAuth'];
}
