<?php
/**
 * Translates a category.
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
 * @author    Rudi Ferrari <bookcrossers@gmx.de>
 * @copyright 2006-2017 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      http://www.phpmyfaq.de
 * @since     2006-09-10
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($user->perm->checkRight($user->getUserId(), 'editcateg')) {
    $category = new PMF_Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $category->getMissingCategories();
<<<<<<< HEAD
    $id               = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT);
    $user_permission  = $category->getPermissions('user', array($id));
    $group_permission = $category->getPermissions('group', array($id));
    $selectedLanguage = PMF_Filter::filterInput(INPUT_GET, 'trlang', FILTER_SANITIZE_STRING, $LANGCODE);

    $twig->loadTemplate('category/translate.twig')
        ->display(
            array(
                'PMF_LANG'        => $PMF_LANG,
                'categoryName'    => $category->categoryName[$id]['name'],
                'csrfToken'       => $user->getCsrfTokenFromSession(),
                'languageOptions' => $category->getCategoryLanguagesToTranslate($id, $selectedLanguage),
                'groupPermission' => $faqConfig->get('security.permLevel') !== 'basic' ? $group_permission[0] : -1,
                'id'              => $id,
                'parentId'        => $category->categoryName[$id]['parent_id'],
                'showcat'         => $selectedLanguage !== $LANGCODE,
                'translations'    => $category->getCategoryLanguagesTranslated($id),
                'userOptions'     => $user->getAllUserOptions($category->categoryName[$id]['user_id']),
                'userPermission'  => $user_permission[0]
            )
        );

    unset($category, $id, $user_permission, $group_permission, $selectedLanguage);

} else {
    require 'noperm.php';
=======
    $id = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT);
    $header = sprintf('%s %s: <em>%s</em>',
        $PMF_LANG['ad_categ_trans_1'],
        $PMF_LANG['ad_categ_trans_2'],
        $category->categoryName[$id]['name']);

    $selectedLanguage = PMF_Filter::filterInput(INPUT_GET, 'trlang', FILTER_SANITIZE_STRING, $LANGCODE);
    if ($selectedLanguage !== $LANGCODE) {
        $action = 'showcategory';
        $showcat = 'yes';
    } else {
        $action = 'updatecategory';
        $showcat = 'no';
    }

    $user_permission = $category->getPermissions('user', array($id));
    $group_permission = $category->getPermissions('group', array($id));
    ?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header"><i aria-hidden="true" class="fa fa-list"></i> <?php print $header ?></h2>
            </div>
        </header>
    
        <div class="row">
            <div class="col-lg-12">
                <form class="form-horizontal" action="?action=updatecategory" method="post" accept-charset="utf-8">
                    <input type="hidden" name="id" value="<?php print $id;
    ?>" />
                    <input type="hidden" name="parent_id" value="<?php print $category->categoryName[$id]['parent_id'];
    ?>" />
                    <input type="hidden" name="showcat" value="<?php print $showcat;
    ?>" />
                    <?php if ($faqConfig->get('security.permLevel') !== 'basic'): ?>
                    <input type="hidden" name="restricted_groups" value="<?php print $group_permission[0];
    ?>" />
                    <?php else: ?>
                    <input type="hidden" name="restricted_groups" value="-1" />
                    <?php endif;
    ?>
                    <input type="hidden" name="restricted_users" value="<?php print $user_permission[0];
    ?>" />
                    <input type="hidden" name="csrf" value="<?php print $user->getCsrfTokenFromSession();
    ?>" />

                    <div class="form-group">
                        <label class="col-lg-2 control-label"><?php print $PMF_LANG['ad_categ_titel'];
    ?>:</label>
                        <div class="col-lg-4">
                            <input type="text" name="name" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label"><?php print $PMF_LANG['ad_categ_lang'];
    ?>:</label>
                        <div class="col-lg-4">
                            <select name="catlang" size="1" class="form-control">
                                <?php print $category->getCategoryLanguagesToTranslate($id, $selectedLanguage);
    ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label"><?php print $PMF_LANG['ad_categ_desc'];
    ?>:</label>
                        <div class="col-lg-4">
                            <textarea name="description" rows="3" class="form-control"></textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label"><?php print $PMF_LANG['ad_categ_owner'];
    ?>:</label>
                        <div class="col-lg-4">
                            <select name="user_id" size="1" class="form-control">
                                <?php print $user->getAllUserOptions($category->categoryName[$id]['user_id']);
    ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label"><?php print $PMF_LANG['ad_categ_transalready'];
    ?></label>
                        <div class="col-lg-4">
                            <ul class="form-control-static">
                                <?php
                                foreach ($category->getCategoryLanguagesTranslated($id) as $language => $namedesc) {
                                    print '<li><strong>'.$language.'</strong>: '.$namedesc.'</li>';
                                }
    ?>
                            </ul>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-4">
                            <button class="btn btn-primary" type="submit" name="submit">
                                <?php print $PMF_LANG['ad_categ_translatecateg'];
    ?>
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
<?php

} else {
    print $PMF_LANG['err_NotAuth'];
>>>>>>> 2.10
}
