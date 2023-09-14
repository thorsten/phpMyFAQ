<?php

/**
 * phpMyFAQ Elasticsearch information.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-12-25
 */

use phpMyFAQ\Instance\Elasticsearch;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

if ($user->perm->hasPermission($user->getUserId(), 'editconfig') && $faqConfig->get('search.enableElasticsearch')) {

    $elasticsearch = new Elasticsearch($faqConfig);
    $esConfigData = $faqConfig->getElasticsearchConfig();
?>

  <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
      <i aria-hidden="true" class="fa fa-wrench"></i>
        <?= Translation::get('ad_menu_elasticsearch') ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
      <div class="btn-group mr-2">
        <button class="btn btn-sm btn-primary pmf-elasticsearch" data-action="create">
          <i aria-hidden="true" class="fa fa-searchengine"></i> <?= Translation::get('ad_es_create_index') ?>
        </button>

        <button class="btn btn-sm btn-secondary pmf-elasticsearch" data-action="import">
          <i aria-hidden="true" class="fa fa-search-plus"></i> <?= Translation::get('ad_es_bulk_index') ?>
        </button>

        <button class="btn btn-sm btn-danger pmf-elasticsearch" data-action="drop">
          <i aria-hidden="true" class="fa fa-trash"></i> <?= Translation::get('ad_es_drop_index') ?>
        </button>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-12">
      <div id="pmf-elasticsearch-result"></div>
      <h5><?= Translation::get('ad_menu_searchstats') ?></h5>
      <div id="pmf-elasticsearch-stats"></div>
    </div>
  </div>

<?php
} else {
    echo Translation::get('err_NotAuth');
}
