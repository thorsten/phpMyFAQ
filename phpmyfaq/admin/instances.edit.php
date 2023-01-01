<?php

/**
 * Frontend to edit an instance.
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
 * @since     2012-04-16
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Instance;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}
?>
    <div
        class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i aria-hidden="true" class="fa fa-wrench fa-fw"></i>
            <?= Translation::get('ad_menu_instances'); ?>
        </h1>
    </div>
<?php
if ($user->perm->hasPermission($user->getUserId(), 'editinstances')) {
    $instanceId = Filter::filterInput(INPUT_GET, 'instance_id', FILTER_VALIDATE_INT);

    $instance = new Instance($faqConfig);
    $instanceData = $instance->getInstanceById($instanceId);

    ?>
  <form action="?action=update-instance" method="post" accept-charset="utf-8">
    <input type="hidden" name="instance_id" value="<?= $instanceData->id ?>"/>
    <div class="row mb-2">
      <label for="url" class="col-lg-2 col-form-label"><?= Translation::get('ad_instance_url') ?>:</label>
      <div class="col-lg-8">
        <input type="url" name="url" id="url" class="form-control"
               value="<?= Strings::htmlentities($instanceData->url, ENT_QUOTES) ?>" required>
      </div>
    </div>
    <div class="row mb-2">
      <label for="instance" class="col-lg-2 col-form-label"><?= Translation::get('ad_instance_path') ?>:</label>
      <div class="col-lg-8">
        <input type="text" name="instance" id="instance" class="form-control" required
               value="<?= Strings::htmlentities($instanceData->instance, ENT_QUOTES) ?>">
      </div>
    </div>
    <div class="row mb-2">
      <label for="comment" class="col-lg-2 col-form-label"><?= Translation::get('ad_instance_name') ?>:</label>
      <div class="col-lg-8">
        <input type="text" name="comment" id="comment" class="form-control" required
               value="<?= Strings::htmlentities($instanceData->comment, ENT_QUOTES) ?>">
      </div>
    </div>
    <div class="row mb-2">
      <label class="col-lg-2 col-form-label" for="config"><?= Translation::get('ad_instance_config') ?>:</label>
      <div class="col-lg-8">
            <?php
            foreach ($instance->getInstanceConfig($instanceData->id) as $key => $config) {
                printf(
                    '<input type="text" readonly class="form-control-plaintext" id="config" value="%s">',
                    $key . ': ' . $config
                );
            }
            ?>
      </div>
    </div>
    <div class="row mb-2">
      <div class="offset-lg-2 col-lg-4">
          <a class="btn btn-secondary" href="?action=instances">
              <?= Translation::get('ad_entry_back') ?>
          </a>
        <button class="btn btn-primary" type="submit">
            <?= Translation::get('ad_instance_button') ?>
        </button>
      </div>
    </div>
  </form>
    <?php
} else {
    echo Translation::get('err_NotAuth');
}
