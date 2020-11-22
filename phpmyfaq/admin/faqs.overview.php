<?php

/**
 * Shows the Ajax powered list of records ordered by categories.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2020-11-21
 */

use phpMyFAQ\Category;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
  <h1 class="h2">
    <i aria-hidden="true" class="fa fa-list-alt"></i>
      <?= $PMF_LANG['ad_entry_aor'] ?>
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
    $category->buildTree();

    foreach ($category->getCategoryTree() as $categoryId => $cat) {
?>
        <form id="recordSelection" name="recordSelection" method="post" accept-charset="utf-8">
            <div class="accordion" id="accordion" role="tablist" aria-multiselectable="true">
              <div class="card card-default">
                <div class="card-header" role="tab" id="category-heading-<?= $cat['id'] ?>">
                  <span class="float-right"><?= $cat['name'] ?></span>
                  <h5>
                    <a role="button" data-toggle="collapse" data-parent="#accordion" href="#category-<?= $cat['id'] ?>"
                       aria-expanded="true" aria-controls="collapseOne">
                      <i class="icon fa fa-chevron-circle-right "></i>
                        <?= $category->getPath($cat['id']) ?>
                    </a>
                  </h5>
                </div>
              </div>

            </div>
        </form>
<?php
    }
} else {
    echo $PMF_LANG['err_NotAuth'];
}
?>
    </div>
</div>
