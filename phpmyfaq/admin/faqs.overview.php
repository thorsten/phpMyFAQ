<?php

/**
 * Shows the Ajax powered list of records ordered by categories.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2020-11-21
 */

use phpMyFAQ\Category;
use phpMyFAQ\Category\CategoryRelation;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
  <h1 class="h2">
    <i aria-hidden="true" class="fa fa-list-alt"></i>
      <?= Translation::get('ad_entry_aor') ?>
  </h1>
</div>

<div class="row">
    <div class="col-12">
<?php
if (
    $user->perm->hasPermission($user->getUserId(), 'edit_faq') ||
    $user->perm->hasPermission($user->getUserId(), 'delete_faq')
) {
    $category = new Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $category->transform(0);
    $category->buildCategoryTree();

    $categoryRelation = new CategoryRelation($faqConfig, $category);
    $categoryRelation->setGroups($currentAdminGroups);

    $numRecordsByCat = $categoryRelation->getNumberOfFaqsPerCategory(
        $faqConfig->get('main.enableCategoryRestrictions')
    );

    foreach ($category->getCategoryTree() as $cat) {
        ?>
        <form id="recordSelection" name="recordSelection" method="post" accept-charset="utf-8">
          <div class="accordion" id="accordion" role="tablist" aria-multiselectable="true">
            <div class="card card-default">
              <div class="card-header" role="tab" id="category-heading-<?= $cat['id'] ?>">
                <span class="float-right"><?= $cat['name'] ?></span>
                <h5>
                  <i class="icon fa fa-chevron-circle-right "></i>
                  <a role="button" data-bs-toggle="collapse" data-parent="#accordion" href="#category-<?= $cat['id'] ?>"
                     aria-expanded="true" aria-controls="collapseOne">
                    <?= $category->getPath($cat['id']) ?>
                  </a>
                </h5>
              </div>

              <div id="category-<?= $cat['id'] ?>" class="card-collapse collapse" role="tabcard"
                   aria-labelledby="category-heading-<?= $cat['id'] ?>">
                <div class="card-body">
                  <table class="table table-hover table-sm align-middle">
                    <thead class="thead-light">
                    <tr>
                      <th colspan="2" style="width: 24px;">
                        <a href="?action=view&category=<?= $cat['id'] ?>&orderby=id&sortby=desc">
                          <i class="fa fa-sort-desc" aria-hidden="true"></i>
                        </a>
                        <a href="?action=view&category=<?= $cat['id'] ?>&orderby=id&sortby=asc">
                          <i class="fa fa-sort-asc" aria-hidden="true"></i>
                        </a>
                      </th>
                      <th>
                        #
                      </th>
                      <th>
                        <a href="?action=view&category=<?= $cat['id'] ?>&orderby=title&sortby=desc">
                          <i class="fa fa-sort-desc" aria-hidden="true"></i>
                        </a>
                        <a href="?action=view&category=<?= $cat['id'] ?>&orderby=title&sortby=asc">
                          <i class="fa fa-sort-asc" aria-hidden="true"></i>
                        </a>
                      </th>
                      <th style="width: 100px;">
                        <a href="?action=view&category=<?= $cat['id'] ?>&orderby=date&sortby=desc">
                          <i class="fa fa-sort-desc" aria-hidden="true"></i>
                        </a>
                        <a href="?action=view&category=<?= $cat['id'] ?>&orderby=date&sortby=asc">
                          <i class="fa fa-sort-asc" aria-hidden="true"></i>
                        </a>
                      </th>
                      <th colspan="2">
                        &nbsp;
                      </th>

                      <th style="width: 120px;">
                        <label>
                          <input type="checkbox" id="sticky_category_block_<?= $cat['id'] ?>">
                            <?= Translation::get('ad_record_sticky') ?>
                        </label>
                      </th>
                      <th style="width: 120px;">
                          <?php if ($user->perm->hasPermission($user->getUserId(), 'approverec')) { ?>
                            <label>
                              <input type="checkbox" id="active_category_block_<?= $cat['id'] ?>">
                                <?= Translation::get('ad_record_active') ?>
                            </label>
                          <?php } else { ?>
                            <span class="fa-stack">
                              <i class="fa fa-check fa-stack-1x"></i>
                              <i class="fa fa-ban fa-stack-2x text-danger"></i>
                            </span>
                          <?php } ?>
                      </th>
                      <th colspan="2">
                        &nbsp;
                      </th>
                    </tr>
                    </thead>
                    <tbody>

                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </form>
        <?php
    }
} else {
    echo Translation::get('err_NotAuth');
}
?>
    </div>
</div>
