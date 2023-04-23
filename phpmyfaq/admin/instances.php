<?php

/**
 * The main multi-site instances frontend.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-03-16
 */

use phpMyFAQ\Component\Alert;
use phpMyFAQ\Entity\InstanceEntity;
use phpMyFAQ\Filesystem;
use phpMyFAQ\Filter;
use phpMyFAQ\Instance;
use phpMyFAQ\Instance\Client;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

?>
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i aria-hidden="true" class="fa fa-wrench fa-fw"></i> <?= Translation::get('ad_menu_instances') ?>
        </h1>
        <?php if ($user->perm->hasPermission($user->getUserId(), 'addinstances') &&
            is_writable(PMF_ROOT_DIR . DIRECTORY_SEPARATOR . 'multisite')
        ) : ?>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group mr-2">
                <a class="btn btn-sm btn-success" data-bs-toggle="modal" href="#pmf-modal-add-instance">
                    <i aria-hidden="true" class="fa fa-plus"></i> <?= Translation::get('ad_instance_add') ?>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>

  <div class="row">
  <div class="col-lg-12">
<?php
if ($user->perm->hasPermission($user->getUserId(), 'editinstances')) {
    $fileSystem = new Filesystem(PMF_ROOT_DIR);
    $instance = new Instance($faqConfig);
    $currentClient = new Client($faqConfig);
    $currentClient->setFileSystem($fileSystem);
    $instanceId = Filter::filterInput(INPUT_POST, 'instance_id', FILTER_VALIDATE_INT);

    // Check, if /multisite is writeable
    if (!$currentClient->isMultiSiteWriteable()) {
        echo Alert::danger('ad_instance_error_notwritable');
    }

    // Update client instance
    if ('update-instance' === $action && is_integer($instanceId)) {
        $system = new System();
        $updatedClient = new Client($faqConfig);
        $moveInstance = false;

        // Collect updated data for database
        $updatedData = new InstanceEntity();
        $updatedData->setUrl(Filter::filterInput(INPUT_POST, 'url', FILTER_SANITIZE_SPECIAL_CHARS));
        $updatedData->setInstance(Filter::filterInput(INPUT_POST, 'instance', FILTER_SANITIZE_SPECIAL_CHARS));
        $updatedData->setComment(Filter::filterInput(INPUT_POST, 'comment', FILTER_SANITIZE_SPECIAL_CHARS));

        // Original data
        $originalData = $currentClient->getInstanceById($instanceId);

        if ($originalData->url !== $updatedData->getUrl()) {
            $moveInstance = true;
        }

        if ($updatedClient->updateInstance($instanceId, $updatedData)) {
            if ($moveInstance) {
                $updatedClient->moveClientFolder($originalData->url, $updatedData->getUrl());
                $updatedClient->deleteClientFolder($originalData->url);
            }
            echo Alert::success('ad_config_saved');
        } else {
            echo Alert::danger('ad_entryins_fail', $faqConfig->getDb()->error());
        }
    }
    ?>
  <table class="table">
    <thead>
    <tr>
      <th>#</th>
      <th><?= Translation::get('ad_instance_url') ?></th>
      <th><?= Translation::get('ad_instance_path') ?></th>
      <th colspan="3"><?= Translation::get('ad_instance_name') ?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    foreach ($instance->getAllInstances() as $site) :
        $currentInstance = new Instance($faqConfig);
        $currentInstance->getInstanceById($site->id);
        $currentInstance->setId($site->id);
        ?>
      <tr id="row-instance-<?= $site->id ?>">
        <td><?= $site->id ?></td>
        <td>
          <a href="<?= Strings::htmlentities($site->url . $site->instance, ENT_QUOTES) ?>">
                <?= Strings::htmlentities($site->url, ENT_QUOTES) ?>
          </a>
        </td>
        <td><?= Strings::htmlentities($site->instance, ENT_QUOTES) ?></td>
        <td><?= Strings::htmlentities($site->comment, ENT_QUOTES) ?></td>
        <td>
          <a href="?action=edit-instance&instance_id=<?= $site->id ?>" class="btn btn-info">
            <i aria-hidden="true" class="fa fa-pencil"></i>
          </a>
        </td>
        <td>
            <?php
            if (!$currentInstance->getConfig('isMaster')) {
                $csrfToken = Token::getInstance()->getTokenString('delete-instance');
            ?>
              <button data-delete-instance-id="<?= $site->id ?>" type="button"
                 class="btn btn-danger pmf-instance-delete"
                 data-csrf-token="<?= $csrfToken ?>">
                <i aria-hidden="true" class="fa fa-trash" data-delete-instance-id="<?= $site->id ?>"
                   data-csrf-token="<?= $csrfToken ?>"></i>
              </button>
            <?php } ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <div class="modal fade" id="pmf-modal-add-instance">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h4><?= Translation::get('ad_instance_add') ?></h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form action="#" method="post" accept-charset="utf-8" class="needs-validation" novalidate>
            <?= Token::getInstance()->getTokenInput('add-instance') ?>
            <div class="form-group row">
              <label class="col-form-label col-lg-4" for="url">
                  <?= Translation::get('ad_instance_url') ?>:
              </label>
              <div class="col-lg-8">
                <div class="input-group">
                  <div class="input-group-prepend">
                    <div class="input-group-text">https://</div>
                  </div>
                  <input class="form-control mb-2" type="text" name="url" id="url" required>
                  <div class="input-group-append">
                    <div class="input-group-text">.<?= Request::createFromGlobals()->getHost() ?></div>
                  </div>
                </div>
              </div>
            </div>
            <div class="form-group row">
              <label class="col-form-label col-lg-4" for="instance">
                  <?= Translation::get('ad_instance_path') ?>:
              </label>
              <div class="col-lg-8">
                <input class="form-control mb-2" type="text" name="instance" id="instance" required>
              </div>
            </div>
            <div class="form-group row">
              <label class="col-form-label col-lg-4" for="comment">
                  <?= Translation::get('ad_instance_name') ?>:
              </label>
              <div class="col-lg-8">
                <input class="form-control mb-2" type="text" name="comment" id="comment" required>
              </div>
            </div>
            <div class="form-group row">
              <label class="col-form-label col-lg-4" for="email">
                  <?= Translation::get('ad_instance_email') ?>:
              </label>
              <div class="col-lg-8">
                <input class="form-control mb-2" type="email" name="email" id="email" required>
              </div>
            </div>
            <div class="form-group row">
              <label class="col-form-label col-lg-4" for="admin">
                  <?= Translation::get('ad_instance_admin') ?>:
              </label>
              <div class="col-lg-8">
                <input class="form-control mb-2" type="text" name="admin" id="admin" required>
              </div>
            </div>
            <div class="form-group row">
              <label class="col-form-label col-lg-4" for="password">
                  <?= Translation::get('ad_instance_password') ?>:
              </label>
              <div class="col-lg-8">
                <input class="form-control mb-2" type="password" autocomplete="off" name="password" id="password" required>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <p class="text-sm-start"><?= Translation::get('ad_instance_hint') ?></p>
          <button class="btn btn-primary pmf-instance-add" type="submit">
              <?= Translation::get('ad_instance_button') ?>
          </button>
        </div>
      </div>
    </div>
  </div>
  </div>
  </div>
    <?php
} else {
    print Translation::get('err_NotAuth');
}
