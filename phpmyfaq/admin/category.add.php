<?php
/**
 * Adds a new (sub-)category, a new sub-category inherits the permissions from
 * its parent category.
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2016 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-12-20
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON'){
        $protocol = 'https';
    }
    header('Location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

?>
        <header>
            <h2><i class="icon-list"></i> <?php echo $PMF_LANG['ad_categ_new'] ?></h2>
        </header>
<?php
if ($permission['addcateg']) {

    $category = new PMF_Category($faqConfig, array(), false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $parentId = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);
?>
        <form class="form-horizontal" action="?action=savecategory" method="post" accept-charset="utf-8">
            <input type="hidden" id="lang" name="lang" value="<?php echo $LANGCODE ?>">
            <input type="hidden" name="parent_id" value="<?php echo $parentId ?>">
            <input type="hidden" name="csrf" value="<?php echo $user->getCsrfTokenFromSession() ?>">
<?php
    if ($parentId > 0) {
        $userAllowed   = $category->getPermissions('user', array($parentId));
        $groupsAllowed = $category->getPermissions('group', array($parentId));
?>
            <input type="hidden" name="restricted_users" value="<?php echo $userAllowed[0] ?>">
            <?php foreach ($groupsAllowed as $group): ?>
            <input type="hidden" name="restricted_groups[]" value="<?php echo $group ?>">
            <?php endforeach; ?>
<?php
        printf(
            '<div class="control-group">%s: %s (%s)</div>',
            $PMF_LANG['msgMainCategory'],
            $category->categoryName[$parentId]['name'],
            $languageCodes[PMF_String::strtoupper($category->categoryName[$parentId]['lang'])]
        );
    }
?>
            <div class="control-group">
                <label class="control-label" for="name"><?php echo $PMF_LANG['ad_categ_titel'] ?>:</label>
                <div class="controls">
                    <input type="text" id="name" name="name" required="required">
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="description"><?php echo $PMF_LANG['ad_categ_desc'] ?>:</label>
                <div class="controls">
                    <textarea id="description" name="description" rows="3" cols="80" ></textarea>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="user_id"><?php echo $PMF_LANG['ad_categ_owner'] ?>:</label>
                <div class="controls">
                    <select name="user_id" id="user_id" size="1">
                    <?php echo $user->getAllUserOptions() ?>
                    </select>
                </div>
            </div>

<?php
    if ($parentId === 0) {
        if ($faqConfig->get('security.permLevel') != 'basic') {
?>
            <div class="control-group">
                <label class="control-label"><?php echo $PMF_LANG['ad_entry_grouppermission'] ?></label>
                <div class="controls">
                    <label class="radio">
                        <input type="radio" name="grouppermission" value="all" checked="checked">
                        <?php echo $PMF_LANG['ad_entry_all_groups'] ?>
                    </label>
                    <label class="radio">
                        <input type="radio" name="grouppermission" value="restricted">
                        <?php echo $PMF_LANG['ad_entry_restricted_groups'] ?>
                    </label>
                    <select name="restricted_groups[]" size="3" multiple>
                        <?php echo $user->perm->getAllGroupsOptions() ?>
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
                <label class="control-label"><?php echo $PMF_LANG['ad_entry_userpermission'] ?></label>
                <div class="controls">
                    <label class="radio">
                        <input type="radio" name="userpermission" value="all" checked="checked">
                        <?php echo $PMF_LANG['ad_entry_all_users'] ?>
                    </label>
                    <label class="radio">
                        <input type="radio" name="userpermission" value="restricted">
                        <?php echo $PMF_LANG['ad_entry_restricted_users'] ?>
                    </label>
                    <select name="restricted_users" size="1">
                        <?php echo $user->getAllUserOptions(1) ?>
                    </select>
                </div>
            </div>

<?php
    }
?>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit" name="submit">
                    <?php echo $PMF_LANG['ad_categ_add'] ?>
                </button>
            </div>
        </form>
<?php
} else {
    echo $PMF_LANG['err_NotAuth'];
}