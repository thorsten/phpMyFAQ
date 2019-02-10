<?php
/**
 * Adds a new (sub-)category, a new sub-category inherits the permissions from
 * its parent category.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2003-12-20
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header"><i aria-hidden="true" class="fa fa-list fa-fw"></i> <?php echo $PMF_LANG['ad_categ_new'] ?></h2>
            </div>
        </header>

        <div class="row">
            <div class="col-lg-12">
<?php
if ($user->perm->checkRight($user->getUserId(), 'addcateg')) {
    $category = new PMF_Category($faqConfig, [], false);
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
        $userAllowed = $category->getPermissions('user', array($parentId));
        $groupsAllowed = $category->getPermissions('group', array($parentId));
        ?>
            <input type="hidden" name="restricted_users" value="<?php echo $userAllowed[0] ?>">
            <?php foreach ($groupsAllowed as $group): ?>
            <input type="hidden" name="restricted_groups[]" value="<?php echo $group ?>">
            <?php endforeach;
        ?>
<?php
        printf(
            '<div class="form-group"><label class="col-lg-2 control-label">%s:</label>',
            $PMF_LANG['msgMainCategory']
        );
        printf(
            '<div class="col-lg-4"><p class="form-control-static">%s (%s)</p></div></div>',
            $category->categoryName[$parentId]['name'],
            $languageCodes[PMF_String::strtoupper($category->categoryName[$parentId]['lang'])]
        );
    }
    ?>
                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="name"><?php echo $PMF_LANG['ad_categ_titel'] ?>:</label>
                        <div class="col-lg-4">
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="description"><?php echo $PMF_LANG['ad_categ_desc'] ?>:</label>
                        <div class="col-lg-4">
                            <textarea id="description" name="description" rows="3" class="form-control"></textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-4">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="active" value="1" checked>
                                    <?php echo $PMF_LANG['ad_user_active'] ?>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="user_id"><?php echo $PMF_LANG['ad_categ_owner'] ?>:</label>
                        <div class="col-lg-4">
                            <select name="user_id" id="user_id" size="1" class="form-control">
                            <?php echo $user->getAllUserOptions() ?>
                            </select>
                        </div>
                    </div>
                    <?php if ($faqConfig->get('security.permLevel') !== 'basic') {
    ?>
                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="group_id"><?php echo $PMF_LANG['ad_categ_moderator'] ?>:</label>
                        <div class="col-lg-4">
                            <select name="group_id" id="group_id" size="1" class="form-control">
                                <?php echo $user->perm->getAllGroupsOptions([]) ?>
                            </select>
                        </div>
                    </div>
                    <?php 
} else {
    ?>
                    <input type="hidden" name="group_id" value="-1">
                    <?php 
}
    ?>

<?php
    if ($parentId === 0) {
        if ($faqConfig->get('security.permLevel') !== 'basic') {
            ?>
                    <div class="form-group">
                        <label class="col-lg-2 control-label"><?php echo $PMF_LANG['ad_entry_grouppermission'] ?></label>
                        <div class="col-lg-4">
                            <label class="radio">
                                <input type="radio" name="grouppermission" value="all" checked="checked">
                                <?php echo $PMF_LANG['ad_entry_all_groups'] ?>
                            </label>
                            <label class="radio">
                                <input type="radio" name="grouppermission" value="restricted">
                                <?php echo $PMF_LANG['ad_entry_restricted_groups'] ?>
                            </label>
                            <select name="restricted_groups[]" size="3" class="form-control" multiple>
                                <?php echo $user->perm->getAllGroupsOptions([]) ?>
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
                    <div class="form-group">
                        <label class="col-lg-2 control-label"><?php echo $PMF_LANG['ad_entry_userpermission'] ?></label>
                        <div class="col-lg-4">
                            <label class="radio">
                                <input type="radio" name="userpermission" value="all" checked="checked">
                                <?php echo $PMF_LANG['ad_entry_all_users'] ?>
                            </label>
                            <label class="radio">
                                <input type="radio" name="userpermission" value="restricted">
                                <?php echo $PMF_LANG['ad_entry_restricted_users'] ?>
                            </label>
                            <select name="restricted_users" size="1" class="form-control">
                                <?php echo $user->getAllUserOptions(1) ?>
                            </select>
                        </div>
                    </div>

<?php

    }
    ?>
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-4">
                            <button class="btn btn-primary" type="submit" name="submit">
                                <?php echo $PMF_LANG['ad_categ_add'] ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
<?php

} else {
    echo $PMF_LANG['err_NotAuth'];
}
