<?php

/**
 * The main multi-site instances frontend.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2022 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-03-16
 */

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Instance;
use phpMyFAQ\Instance\Client;
use phpMyFAQ\Strings;
use phpMyFAQ\System;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

?>
  <header class="row">
    <div class="col-lg-12">
      <h2 class="page-header">
        <i aria-hidden="true" class="fa fa-wrench fa-fw"></i> <?= $PMF_LANG['ad_menu_instances']; ?>
          <?php if (
            $user->perm->hasPermission($user->getUserId(), 'addinstances') &&
              is_writable(PMF_ROOT_DIR . DIRECTORY_SEPARATOR . 'multisite')
) : ?>
            <div class="float-right">
              <a class="btn btn-sm btn-success" data-toggle="modal" href="#pmf-modal-add-instance">
                <i aria-hidden="true" class="fa fa-plus"></i> <?= $PMF_LANG['ad_instance_add'] ?>
              </a>
            </div>
          <?php endif; ?>
      </h2>
    </div>
  </header>

  <div class="row">
  <div class="col-lg-12">
<?php
if ($user->perm->hasPermission($user->getUserId(), 'editinstances')) {
    $instance = new Instance($faqConfig);
    $instanceId = Filter::filterInput(INPUT_POST, 'instance_id', FILTER_VALIDATE_INT);

    // Check, if /multisite is writeable
    if (!is_writable(PMF_ROOT_DIR . DIRECTORY_SEPARATOR . 'multisite')) {
        printf(
            '<p class="alert alert-danger">%s</p>',
            $PMF_LANG['ad_instance_error_notwritable']
        );
    }

    // Update client instance
    if ('updateinstance' === $action && is_integer($instanceId)) {
        $system = new System();
        $originalClient = new Client($faqConfig);
        $updatedClient = new Client($faqConfig);
        $moveInstance = false;

        // Collect updated data for database
        $updatedData = [];
        $updatedData['url'] = Filter::filterInput(INPUT_POST, 'url', FILTER_UNSAFE_RAW);
        $updatedData['instance'] = Filter::filterInput(INPUT_POST, 'instance', FILTER_UNSAFE_RAW);
        $updatedData['comment'] = Filter::filterInput(INPUT_POST, 'comment', FILTER_UNSAFE_RAW);

        // Original data
        $originalData = $originalClient->getInstanceById($instanceId);

        if ($originalData->url !== $updatedData['url']) {
            $moveInstance = true;
        }

        if ($updatedClient->updateInstance($instanceId, $updatedData)) {
            if ($moveInstance) {
                try {
                    $updatedClient->moveClientFolder($originalData->url, $updatedData['url']);
                    $updatedClient->deleteClientFolder($originalData->url);
                } catch (Exception $e) {
                    // handle exception
                }
            }
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
    ?>
  <table class="table">
    <thead>
    <tr>
      <th>#</th>
      <th><?= $PMF_LANG['ad_instance_url'] ?></th>
      <th><?= $PMF_LANG['ad_instance_path'] ?></th>
      <th colspan="3"><?= $PMF_LANG['ad_instance_name'] ?></th>
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
          <a href="?action=editinstance&instance_id=<?= $site->id ?>" class="btn btn-info">
            <i aria-hidden="true" class="fa fa-pencil"></i>
          </a>
        </td>
        <td>
            <?php if ($currentInstance->getConfig('isMaster') !== true) : ?>
              <a href="javascript:;" id="delete-instance-<?= $site->id ?>"
                 class="btn btn-danger pmf-instance-delete"
                 data-csrf-token="<?= $user->getCsrfTokenFromSession() ?>">
                <i aria-hidden="true" class="fa fa-trash"></i>
              </a>
            <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <div class="modal fade" id="pmf-modal-add-instance">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h4><?= $PMF_LANG['ad_instance_add'] ?></h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form action="#" method="post" accept-charset="utf-8">
            <input type="hidden" name="csrf" id="csrf" value="<?= $user->getCsrfTokenFromSession() ?>">
            <div class="form-group row">
              <label class="col-form-label col-lg-4" for="url">
                  <?= $PMF_LANG['ad_instance_url'] ?>:
              </label>
              <div class="col-lg-8">
                <div class="input-group">
                  <div class="input-group-prepend">
                    <div class="input-group-text">https://</div>
                  </div>
                  <input class="form-control" type="text" name="url" id="url" required>
                  <div class="input-group-append">
                    <div class="input-group-text">.<?= $_SERVER['SERVER_NAME'] ?></div>
                  </div>
                </div>
              </div>
            </div>
            <div class="form-group row">
              <label class="col-form-label col-lg-4" for="instance">
                  <?= $PMF_LANG['ad_instance_path'] ?>:
              </label>
              <div class="col-lg-8">
                <input class="form-control" type="text" name="instance" id="instance" required>
              </div>
            </div>
            <div class="form-group row">
              <label class="col-form-label col-lg-4" for="comment">
                  <?= $PMF_LANG['ad_instance_name'] ?>:
              </label>
              <div class="col-lg-8">
                <input class="form-control" type="text" name="comment" id="comment" required>
              </div>
            </div>
            <div class="form-group row">
              <label class="col-form-label col-lg-4" for="email">
                  <?= $PMF_LANG['ad_instance_email'] ?>:
              </label>
              <div class="col-lg-8">
                <input class="form-control" type="email" name="email" id="email" required>
              </div>
            </div>
            <div class="form-group row">
              <label class="col-form-label col-lg-4" for="admin">
                  <?= $PMF_LANG['ad_instance_admin'] ?>:
              </label>
              <div class="col-lg-8">
                <input class="form-control" type="text" name="admin" id="admin" required>
              </div>
            </div>
            <div class="form-group row">
              <label class="col-form-label col-lg-4" for="password">
                  <?= $PMF_LANG['ad_instance_password'] ?>:
              </label>
              <div class="col-lg-8">
                <input class="form-control" type="password" autocomplete="off" name="password" id="password" required>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <p><?= $PMF_LANG['ad_instance_hint'] ?></p>
          <button class="btn btn-primary pmf-instance-add">
              <?= $PMF_LANG['ad_instance_button'] ?>
          </button>
        </div>
      </div>
    </div>
  </div>

  <script>
    (function() {

      // Add instance
      $('.pmf-instance-add').on('click', function(event) {
        event.preventDefault();
        const csrf = $('#csrf').val();
        const url = $('#url').val();
        const instance = $('#instance').val();
        const comment = $('#comment').val();
        const email = $('#email').val();
        const admin = $('#admin').val();
        const password = $('#password').val();

        const escape = (unsafe) => {
          return unsafe.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
        }

        $.ajax({
          url: 'index.php',
          type: 'GET',
          data: {
            action: 'ajax', ajax: 'config', ajaxaction: 'add_instance', csrf: csrf, url: url,
            instance: instance, comment: comment, email: email, admin: admin, password: password,
          },
          success: (data) => {
            $('.modal').modal('hide');
            $('.table tbody').append(
              '<tr id="row-instance-' + data.added + '">' +
              '<td>' + data.added + '</td>' +
              '<td><a href="' + data.url + '">' + data.url + '</a></td>' +
              '<td>' + escape(instance) + '</td>' +
              '<td>' + escape(comment) + '</td>' +
              '<td>' +
              '<a href="?action=editinstance&instance_id=' + data.added +
              '" class="btn btn-info"><i aria-hidden="true" class="fa fa-pencil"></i></a>' +
              '</td>' +
              '<td>' +
              '<a href="javascript:;" id="delete-instance-' + data.added +
              '" class="btn btn-danger pmf-instance-delete"><i aria-hidden="true" class="fa fa-trash"></i></a>' +
              '</td>' +
              '</tr>',
            );
          },
          error: (data) => {
            $('.table').after(
              '<div class="alert alert-danger">Could not add instance</div>',
            );
          }
        });
      });

      // Delete instance
      $('.pmf-instance-delete').click(function(event) {
        event.preventDefault();
        const targetId = event.target.id.split('-');
        const id = targetId[2];
        const csrf = this.getAttribute('data-csrf-token');

        if (confirm('Are you sure?')) {
          $.get('index.php',
            { action: 'ajax', ajax: 'config', ajaxaction: 'delete_instance', instanceId: id, csrf: csrf },
            function(data) {
              if (typeof (data.deleted) === 'undefined') {
                $('.table').after(
                  '<div class="alert alert-danger">' +
                  '<?= $PMF_LANG['ad_instance_error_cannotdelete'] ?> ' + data.error +
                  '</div>',
                );
              } else {
                $('#row-instance-' + id).fadeOut('slow');
              }
            },
            'json',
          );
        }
      });

    })();
  </script>

  </div>
  </div>
    <?php
} else {
    print $PMF_LANG['err_NotAuth'];
}
