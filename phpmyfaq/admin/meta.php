<?php

/**
 * The meta data administration frontend.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018-2022 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2018-08-10
 */

use phpMyFAQ\Entity\MetaEntity;
use phpMyFAQ\Filter;
use phpMyFAQ\Meta;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$defaultMetaAction = 'list';
$metaAction = Filter::filterInput(INPUT_GET, 'meta_action', FILTER_UNSAFE_RAW, $defaultMetaAction);
$csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_UNSAFE_RAW);
$metaId = Filter::filterInput(INPUT_POST, 'meta_id', FILTER_VALIDATE_INT);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
  <h1 class="h2">
    <i aria-hidden="true" class="fa fa-code"></i>
      <?= $PMF_LANG['ad_menu_meta'] ?>
  </h1>
  <div class="btn-toolbar mb-2 mb-md-0">
    <div class="btn-group mr-2">
      <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addMetaModal">
        <i aria-hidden="true" class="fa fa-plus"></i>
          <?= $PMF_LANG['ad_meta_add'] ?>
      </button>
    </div>
  </div>
</div>

<?php

if (!$user->perm->hasPermission($user->getUserId(), 'editconfig')) {
    echo $PMF_LANG['err_NotAuth'];
}

$meta = new Meta($faqConfig);

// Update meta data
if ('meta.update' === $action && is_integer($metaId)) {
    if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
        echo $PMF_LANG['err_NotAuth'];
    } else {
        $entity = new MetaEntity();
        $entity
            ->setPageId(Filter::filterInput(INPUT_POST, 'page_id', FILTER_UNSAFE_RAW))
            ->setType(Filter::filterInput(INPUT_POST, 'type', FILTER_UNSAFE_RAW))
            ->setContent(Filter::filterInput(INPUT_POST, 'content', FILTER_SANITIZE_SPECIAL_CHARS));

        if ($meta->update($metaId, $entity)) {
            printf(
                '<p class="alert alert-success">%s%s</p>',
                '<a class="close" data-dismiss="alert" href="#">&times;</a>',
                $PMF_LANG['ad_config_saved']
            );
        } else {
            printf(
                '<p class="alert alert-danger">%s%s<br/>%s</p>',
                '<a class="close" data-dismiss="alert" href="#">&times;</a>',
                $PMF_LANG['ad_entryins_fail'],
                $faqConfig->getDb()->error()
            );
        }
    }
}

$metaData = $meta->getAll();
?>
<table class="table table-striped">
  <thead class="thead-dark">
  <tr>
    <th>#</th>
    <th><?= $PMF_LANG['ad_meta_page_id'] ?></th>
    <th><?= $PMF_LANG['ad_meta_type'] ?></th>
    <th colspan="2"><?= $PMF_LANG['ad_meta_content'] ?></th>
  </tr>
  </thead>
  <tbody>
  <?php foreach ($metaData as $data) : ?>
    <tr id="row-meta-<?= $data->getId() ?>">
      <td><?= $data->getId() ?></td>
      <td><?= $data->getPageId() ?></td>
      <td><?= $data->getType() ?></td>
      <td><?= $data->getContent() ?></td>
      <td class="text-right">
        <a href="?action=meta.edit&id=<?= $data->getId() ?>" class="btn btn-success">
          <i aria-hidden="true" class="fa fa-pencil"></i>
        </a>
        <a href="#" id="delete-meta-<?= $data->getId() ?>" class="btn btn-danger pmf-meta-delete"
           data-csrf="<?= $user->getCsrfTokenFromSession() ?>">
          <i aria-hidden="true" class="fa fa-trash"></i>
        </a>
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#codeModal"
                data-code-snippet="<?= $data->getContent() ?>">
          <i aria-hidden="true" class="fa fa-code"></i>
        </button>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<!-- Modal to add new meta data -->
<div class="modal fade" id="addMetaModal" tabindex="-1" role="dialog" aria-labelledby="addMetaModalLabel"
     aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addMetaModalLabel"><?= $PMF_LANG['ad_meta_add'] ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="<?= $PMF_LANG['ad_att_close'] ?>">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form action="#" method="post" accept-charset="utf-8">
          <input type="hidden" name="csrf" id="csrf" value="<?= $user->getCsrfTokenFromSession() ?>">

          <div class="form-group row">
            <label for="page_id" class="col-sm-2 col-form-label"><?= $PMF_LANG['ad_meta_page_id'] ?></label>
            <div class="col-sm-10">
              <input type="text" class="form-control" id="page_id" required>
            </div>
          </div>

          <div class="form-group row">
            <label for="type" class="col-sm-2 col-form-label"><?= $PMF_LANG['ad_meta_type'] ?></label>
            <div class="col-sm-10">
              <select class="form-control" id="type" required>
                <option value="text">Text</option>
                <option value="html">HTML</option>
              </select>
            </div>
          </div>

          <div class="form-group row">
            <label for="meta-content" class="col-sm-2 col-form-label"><?= $PMF_LANG['ad_meta_content'] ?></label>
            <div class="col-sm-10">
              <textarea class="form-control" id="meta-content" rows="5" required></textarea>
            </div>
          </div>

        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary pmf-meta-add"><?= $PMF_LANG['msgSave'] ?></button>
      </div>
    </div>
  </div>
</div>

<!-- Modal to copy meta data code snippet -->
<div class="modal fade" id="codeModal" tabindex="-1" role="dialog" aria-labelledby="codeModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="codeModalLabel"><?= $PMF_LANG['ad_meta_copy_snippet'] ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="<?= $PMF_LANG['ad_att_close'] ?>">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <label class="sr-only" for="copy-code-snippet"><?= $PMF_LANG['ad_meta_copy_snippet'] ?></label>
        <textarea class="form-control" id="copy-code-snippet"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal"><?= $PMF_LANG['ad_att_close'] ?></button>
      </div>
    </div>
  </div>
</div>

<script src="assets/js/meta.js"></script>
