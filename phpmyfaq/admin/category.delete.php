<?php

/**
 * Frontend to delete a category.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-12-20
 */

use phpMyFAQ\Category;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

?>
  <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
      <i aria-hidden="true" class="fa fa-folder"></i> <?= Translation::get('ad_categ_deletesure') ?>
    </h1>
  </div>
<?php
if ($user->perm->hasPermission($user->getUserId(), 'delcateg')) {
    $category = new Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $categories = $category->getAllCategories();

    $categoryHelper = new CategoryHelper();
    $categoryHelper
        ->setConfiguration($faqConfig)
        ->setCategory($category);

    $id = Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);
    ?>
  <div class="row">
    <div class="col-lg-12">
      <form action="?action=removecategory" method="post" accept-charset="utf-8">
        <input type="hidden" name="cat" value="<?= $id ?>">
          <?= Token::getInstance()->getTokenInput('remove-category') ?>

        <div class="row mb-2">
          <label class="col-lg-2 col-form-label" for="categoryName">
              <?= Translation::get('ad_categ_titel') ?>:
          </label>
          <div class="col-lg-4">
            <input type="text" readonly class="form-control-plaintext" id="categoryName"
                   value="<?= $categories[$id]['name'] ?>">
          </div>
        </div>

        <div class="row mb-2">
          <label class="col-lg-2 col-form-label" for="lang">
              <?= Translation::get('ad_entry_locale') ?>:
          </label>
          <div class="col-lg-4">
              <select name="lang" id="lang" class="form-select">
              <?= $categoryHelper->renderAvailableTranslationsOptions($id) ?>
              </select>
          </div>
        </div>

        <div class="row mb-2">
          <div class="offset-lg-2 col-lg-4">
            <a class="btn btn-success" href="?action=category">
                <?= Translation::get('msgCancel') ?>
            </a>
            <button class="btn btn-danger" type="submit" name="submit">
                <?= Translation::get('msgDelete') ?>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
    <?php
} else {
    echo Translation::get('err_NotAuth');
}
