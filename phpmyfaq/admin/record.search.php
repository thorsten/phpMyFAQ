<?php

/**
 * Shows the admin search frontend for FAQs.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2011-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2011-09-29
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
        <header>
            <div class="col-lg-12">
                <h2 class="page-header">
                    <i aria-hidden="true" class="fa fa-pencil"></i> <?php echo $PMF_LANG['ad_menu_searchfaqs'] ?>
                </h2>
            </div>
        <header>


        <div class="row">
            <div class="col-lg-12">
<?php
if ($user->perm->checkRight($user->getUserId(), 'editbt') || $user->perm->checkRight($user->getUserId(), 'delbt')) {
    $searchcat = PMF_Filter::filterInput(INPUT_POST, 'searchcat', FILTER_VALIDATE_INT);
    $searchterm = PMF_Filter::filterInput(INPUT_POST, 'searchterm', FILTER_SANITIZE_STRIPPED);

    $category = new PMF_Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $category->transform(0);

    // Set the Category for the helper class
    $categoryHelper = new PMF_Helper_Category();
    $categoryHelper->setCategory($category);

    $category->buildTree();

    $linkVerifier = new PMF_Linkverifier($faqConfig, $user->getLogin());
    ?>

                <form action="?action=view" method="post" class="form-horizontal" accept-charset="utf-8">

                    <div class="form-group">
                        <label class="col-lg-2 control-label"><?php echo $PMF_LANG['msgSearchWord'] ?>:</label>
                        <div class="col-lg-4">
                            <input class="form-control" type="search" name="searchterm" autofocus
                                   value="<?php echo $searchterm ?>">

                        </div>
                    </div>

                    <?php if ($linkVerifier->isReady() === true): ?>
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-4 checkbox">
                            <label>
                                <input type="checkbox" name="linkstate" value="linkbad">
                                <?php echo $PMF_LANG['ad_linkcheck_searchbadonly'] ?>
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="col-lg-2 control-label"><?php echo $PMF_LANG['msgCategory'] ?>:</label>
                        <div class="col-lg-4">
                            <select name="searchcat" class="form-control">
                                <option value="0"><?php echo $PMF_LANG['msgShowAllCategories'] ?></option>
                                <?php echo $categoryHelper->renderOptions($searchcat) ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-4">
                            <button class="btn btn-primary" type="submit" name="submit">
                                <?php echo $PMF_LANG['msgSearch'] ?>
                            </button>
                        </div>
                    </div>
                </form>
<?php

} else {
    echo $PMF_LANG['err_NotAuth'];
}
?>
            </div>
        </div>