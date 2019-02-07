<?php

/**
 * phpMyFAQ Elasticsearch information.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2015-12-25
 */

use Elasticsearch\Common\Exceptions\Forbidden403Exception;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use phpMyFAQ\Search\Elasticsearch;
use phpMyFAQ\Instance\Elasticsearch as ElasticsearchInstance;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($user->perm->checkRight($user->getUserId(), 'editconfig') && $faqConfig->get('search.enableElasticsearch')) {

    $esSearch = new Elasticsearch($faqConfig);
    $esInstance = new ElasticsearchInstance($faqConfig);

    try {
        $esConfig = $faqConfig->getElasticsearchConfig();
        $esInformation = $faqConfig->getElasticsearch()->indices()->stats(['index' => 'phpmyfaq']);
    } catch (NoNodesAvailableException $e) {
        $esInformation = $e->getMessage();
    } catch (Forbidden403Exception $e) {
        $esInformation = $e->getMessage();
    } catch (Missing404Exception $e) {
        $esInformation = $e->getMessage();
    }
    ?>

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
      <h1 class="h2">
        <i aria-hidden="true" class="fas fa-wrench"></i>
          <?= $PMF_LANG['ad_menu_elasticsearch'] ?>
      </h1>
      <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group mr-2">
          <button class="btn btn-sm btn-outline-primary pmf-elasticsearch" data-action="create">
            <i aria-hidden="true" class="fas fa-searchengine"></i> <?= $PMF_LANG['ad_es_create_index'] ?>
          </button>

          <button class="btn btn-sm btn-outline-primary pmf-elasticsearch" data-action="import">
            <i aria-hidden="true" class="fas fa-search-plus"></i> <?= $PMF_LANG['ad_es_bulk_index'] ?>
          </button>

          <button class="btn btn-sm btn-outline-danger pmf-elasticsearch" data-action="drop">
            <i aria-hidden="true" class="fas fa-trash"></i> <?= $PMF_LANG['ad_es_drop_index'] ?>
          </button>
        </div>
      </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="result"></div>
            <h5>
                <?= $PMF_LANG['ad_menu_searchstats'] ?>
            </h5>

            <?php if (is_array($esInformation)) { ?>
            <dl class="dl-horizontal">

                <dt>Documents</dt>
                <dd><?= $esInformation['indices']['phpmyfaq']['total']['docs']['count'] ?></dd>

                <dt>Storage size</dt>
                <dd><?= $esInformation['indices']['phpmyfaq']['total']['store']['size_in_bytes'] ?> Bytes</dd>

            </dl>
            <?php
            } else {
                $error = json_decode($esInformation);
            ?>
            <p class="alert alert-warning">
                Elasticsearch: <?= ucfirst($error->error->reason) ?>
            </p>
            <?php } ?>

        </div>
    </div>

    <script src="assets/js/search.js"></script>

    <?php
} else {
    echo $PMF_LANG['err_NotAuth'];
}
