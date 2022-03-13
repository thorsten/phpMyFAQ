<?php

/**
 * Shows the admin search frontend for FAQs.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2011-2022 phpMyFAQ Team
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2011-09-29
 */

use phpMyFAQ\Category;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Filter;
use phpMyFAQ\LinkVerifier;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}
?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i aria-hidden="true" class="fa fa-search"></i>
              <?= $PMF_LANG['ad_menu_searchfaqs'] ?>
          </h1>
        </div>

        <div class="row">
            <div class="col-lg-12">
<?php
if ($user->perm->hasPermission($user->getUserId(), 'edit_faq') || $user->perm->hasPermission($user->getUserId(), 'delete_faq')) {
    $searchCategory = Filter::filterInput(INPUT_POST, 'searchcat', FILTER_VALIDATE_INT);
    $searchTerm = Filter::filterInput(INPUT_POST, 'searchterm', FILTER_UNSAFE_RAW);

    $category = new Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $category->transform(0);

    // Set the CategoryHelper for the helper class
    $categoryHelper = new CategoryHelper();
    $categoryHelper->setCategory($category);

    $category->buildCategoryTree();

    $linkVerifier = new LinkVerifier($faqConfig, $user->getLogin());
    ?>

                <form action="?action=view" method="post"  accept-charset="utf-8">

                    <div class="row">
                        <label class="col-lg-2 col-form-label"><?= $PMF_LANG['msgSearchWord'] ?>:</label>
                        <div class="col-lg-4">
                            <input class="form-control" type="search" name="searchterm" autofocus
                                   value="<?= $searchTerm ?>">

                        </div>
                    </div>

                    <?php if ($linkVerifier->isReady() === true) : ?>
                    <div class="row">
                        <div class="offset-lg-2 col-lg-4 checkbox">
                            <label>
                                <input type="checkbox" name="linkstate" value="linkbad">
                                <?= $PMF_LANG['ad_linkcheck_searchbadonly'] ?>
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <label class="col-lg-2 col-form-label"><?= $PMF_LANG['msgCategory'] ?>:</label>
                        <div class="col-lg-4">
                            <select name="searchcat" class="form-control">
                                <option value="0"><?= $PMF_LANG['msgShowAllCategories'] ?></option>
                                <?= $categoryHelper->renderOptions($searchCategory) ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="offset-lg-2 col-lg-4">
                            <button class="btn btn-primary" type="submit" name="submit">
                                <?= $PMF_LANG['msgSearch'] ?>
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
