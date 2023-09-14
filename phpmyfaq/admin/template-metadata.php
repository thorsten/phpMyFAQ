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

use phpMyFAQ\Component\Alert;
use phpMyFAQ\Entity\TemplateMetaDataEntity;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Template\TemplateMetaData;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$defaultMetaAction = 'list';
$metaAction = Filter::filterInput(INPUT_GET, 'meta_action', FILTER_SANITIZE_SPECIAL_CHARS, $defaultMetaAction);
$csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);
$metaId = Filter::filterInput(INPUT_POST, 'meta_id', FILTER_VALIDATE_INT);
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

// Update meta data
if ('meta.update' === $action && is_integer($metaId)) {
    if (!Token::getInstance()->verifyToken('template-metadata', $csrfToken)) {
        echo Translation::get('err_NotAuth');
    } else {
        $entity = new TemplateMetaDataEntity();
        $entity
            ->setPageId(Filter::filterInput(INPUT_POST, 'page_id', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setType(Filter::filterInput(INPUT_POST, 'type', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setContent(Filter::filterInput(INPUT_POST, 'content', FILTER_SANITIZE_SPECIAL_CHARS));

        if ($meta->update($metaId, $entity)) {
            echo Alert::success('ad_config_saved');
        } else {
            echo Alert::danger('ad_entryins_fail', $faqConfig->getDb()->error());
        }
    }
}

$metaData = $meta->getAll();
?>
<table class="table table-striped align-middle">
    <thead class="thead-dark">
    <tr>
        <th>#</th>
        <th><?= Translation::get('ad_meta_page_id') ?></th>
        <th><?= Translation::get('ad_meta_type') ?></th>
        <th colspan="2"><?= Translation::get('ad_meta_content') ?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    foreach ($metaData as $data) {
        $csrfToken = Token::getInstance()->getTokenString('template-metadata');
    ?>
        <tr id="row-meta-<?= $data->getId() ?>">
            <td><?= $data->getId() ?></td>
            <td><?= $data->getPageId() ?></td>
            <td><?= $data->getType() ?></td>
            <td><?= $data->getContent() ?></td>
            <td class="text-end">
                <a href="?action=meta.edit&id=<?= $data->getId() ?>" class="btn btn-sm btn-success">
                    <i aria-hidden="true" class="fa fa-pencil"></i>
                </a>
                <a href="#" data-delete-meta-id="<?= $data->getId() ?>" class="btn btn-sm btn-danger pmf-meta-delete"
                   data-csrf-token="<?= $csrfToken ?>">
                    <i aria-hidden="true" class="fa fa-trash" data-delete-meta-id="<?= $data->getId() ?>"
                       data-csrf-token="<?= $csrfToken ?>"></i>
                </a>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#codeModal"
                        data-code-snippet="<?= $data->getContent() ?>">
                    <i aria-hidden="true" class="fa fa-code"></i>
                </button>
            </td>
        </tr>
    <?php } ?>
    </tbody>
</table>

<!-- Modal to add new meta data -->
<div class="modal fade" id="addMetaModal" tabindex="-1" role="dialog" aria-labelledby="addMetaModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMetaModalLabel"><?= Translation::get('ad_meta_add') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="post" accept-charset="utf-8" class="needs-validation" novalidate>
                    <?= Token::getInstance()->getTokenInput('add-metadata') ?>

                    <div class="row mb-2">
                        <label for="page_id" class="col-sm-2 col-form-label"><?= Translation::get('ad_meta_page_id') ?></label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="page_id" required>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <label for="type" class="col-sm-2 col-form-label"><?= Translation::get('ad_meta_type') ?></label>
                        <div class="col-sm-10">
                            <select class="form-select" id="type" required>
                                <option value="text">Text</option>
                                <option value="html">HTML</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <label for="meta-content"
                               class="col-sm-2 col-form-label"><?= Translation::get('ad_meta_content') ?></label>
                        <div class="col-sm-10">
                            <textarea class="form-control" id="meta-content" rows="5" required></textarea>
                        </div>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary pmf-meta-add"><?= Translation::get('msgSave') ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal to copy meta data code snippet -->
<div class="modal fade" id="codeModal" tabindex="-1" role="dialog" aria-labelledby="codeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="codeModalLabel"><?= Translation::get('ad_meta_copy_snippet') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label class="sr-only" for="copy-code-snippet"><?= Translation::get('ad_meta_copy_snippet') ?></label>
                <textarea class="form-control" id="copy-code-snippet"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                    <?= Translation::get('ad_att_close') ?>
                </button>
            </div>
        </div>
    </div>
</div>
