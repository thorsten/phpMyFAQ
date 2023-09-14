<?php

/**
 * The template metadata administration frontend.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2018-08-10
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Template\TemplateMetaData;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
      <i aria-hidden="true" class="fa fa-code"></i>
      <?= Translation::get('ad_menu_meta') ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group mr-2">
            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addMetaModal">
                <i aria-hidden="true" class="fa fa-plus"></i>
                <?= Translation::get('ad_meta_add') ?>
            </button>
        </div>
    </div>
</div>

<?php

if (!$user->perm->hasPermission($user->getUserId(), 'editconfig')) {
    echo Translation::get('err_NotAuth');
}

$meta = new TemplateMetaData($faqConfig);
$metaId = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$metaData = $meta->getById((int)$metaId);
?>

<form action="?action=meta.update" method="post" accept-charset="utf-8">
  <input type="hidden" name="meta_id" value="<?= $metaData->getId() ?>">
    <?= Token::getInstance()->getTokenInput('update-metadata') ?>

  <div class="row mb-2">
    <label for="page_id" class="col-sm-2 col-form-label"><?= Translation::get('ad_meta_page_id') ?></label>
    <div class="col-sm-10">
      <input type="text" class="form-control" name="page_id" id="page_id" value="<?= $metaData->getPageId() ?>" required>
    </div>
  </div>

  <div class="row mb-2">
    <label for="type" class="col-sm-2 col-form-label"><?= Translation::get('ad_meta_type') ?></label>
    <div class="col-sm-10">
      <select class="form-select" name="type" id="type" required>
        <option value="text" <?= $metaData->getType() === 'text' ? 'selected' : '' ?>>Text</option>
        <option value="html" <?= $metaData->getType() === 'html' ? 'selected' : '' ?>>HTML</option>
      </select>
    </div>
  </div>

  <div class="row mb-2">
    <label for="content" class="col-sm-2 col-form-label"><?= Translation::get('ad_meta_content') ?></label>
    <div class="col-sm-10">
      <textarea class="form-control" name="content" id="content" rows="5" required><?= $metaData->getContent() ?></textarea>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-12 text-end">
      <a class="btn btn-secondary" href="?action=meta">
        <?= Translation::get('msgCancel') ?>
      </a>
      <button class="btn btn-primary" type="submit">
          <?= Translation::get('ad_passwd_change') ?>
      </button>
    </div>
  </div>
</form>
