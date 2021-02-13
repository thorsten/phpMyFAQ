<?php

/**
 * phpMyFAQ Elasticsearch information.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2015-12-25
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

if ($user->perm->hasPermission($user->getUserId(), 'editconfig') && $faqConfig->get('search.enableElasticsearch')) {
    ?>

  <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
      <i aria-hidden="true" class="fa fa-wrench"></i>
        <?= $PMF_LANG['ad_menu_elasticsearch'] ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
      <div class="btn-group mr-2">
        <button class="btn btn-sm btn-primary pmf-elasticsearch" data-action="create">
          <i aria-hidden="true" class="fa fa-searchengine"></i> <?= $PMF_LANG['ad_es_create_index'] ?>
        </button>

        <button class="btn btn-sm btn-primary pmf-elasticsearch" data-action="import">
          <i aria-hidden="true" class="fa fa-search-plus"></i> <?= $PMF_LANG['ad_es_bulk_index'] ?>
        </button>

        <button class="btn btn-sm btn-danger pmf-elasticsearch" data-action="drop">
          <i aria-hidden="true" class="fa fa-trash"></i> <?= $PMF_LANG['ad_es_drop_index'] ?>
        </button>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-12">
      <div class="result"></div>
      <h5><?= $PMF_LANG['ad_menu_searchstats'] ?></h5>


      <div id="pmf-elasticsearch-stats">
      </div>

    </div>
  </div>

  <script src="assets/js/search.js"></script>

    <?php
} else {
    echo $PMF_LANG['err_NotAuth'];
}
