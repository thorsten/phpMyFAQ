<?php

use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Common\Exceptions\Forbidden403Exception;

/**
 * phpMyFAQ Elasticsearch information.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-12-25
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($user->perm->checkRight($user->getUserId(), 'editconfig') && $faqConfig->get('search.enableElasticsearch')) {

    $esSearch = new PMF_Search_Elasticsearch($faqConfig);
    $esInstance = new PMF_Instance_Elasticsearch($faqConfig);

    try {
        $esConfig = $faqConfig->getElasticsearchConfig();
        $esInformation = $faqConfig->getElasticsearch()->indices()->stats(['index' => 'phpmyfaq']);
    } catch (Forbidden403Exception $e) {
        $esInformation = $e->getMessage();
    } catch (Missing404Exception $e) {
        $esInformation = $e->getMessage();
    }

    ?>
    <header class="row">
        <div class="col-lg-12">
            <h2 class="page-header">
                <i aria-hidden="true" class="fa fa-wrench fa-fw"></i> <?php echo $PMF_LANG['ad_menu_elasticsearch'] ?>
                <div class="pull-right">
                    <button class="btn btn-default pmf-elasticsearch" data-action="create">
                        <i aria-hidden="true" class="fa fa-plus-square-o"></i> <?php echo $PMF_LANG['ad_es_create_index'] ?>
                    </button>

                    <button class="btn btn-default pmf-elasticsearch" data-action="import">
                        <i aria-hidden="true" class="fa fa-plus-square"></i> <?php echo $PMF_LANG['ad_es_bulk_index'] ?>
                    </button>

                    <button class="btn btn-danger pmf-elasticsearch" data-action="drop">
                        <i aria-hidden="true" class="fa fa-trash"></i> <?php echo $PMF_LANG['ad_es_drop_index'] ?>
                    </button>
                </div>
            </h2>

        </div>
    </header>

    <div class="row">
        <div class="col-lg-12">
            <div class="result">

            </div>
            <h3>
                <?php echo $PMF_LANG['ad_menu_searchstats'] ?>
            </h3>

            <?php if (is_array($esInformation)) { ?>
            <dl class="dl-horizontal">

                <dt>Documents</dt>
                <dd><?php echo $esInformation['indices']['phpmyfaq']['total']['docs']['count'] ?></dd>

                <dt>Storage size</dt>
                <dd><?php echo $esInformation['indices']['phpmyfaq']['total']['store']['size_in_bytes'] ?> Bytes</dd>

            </dl>
            <?php
            } else {
                $error = json_decode($esInformation);
            ?>
            <p class="alert alert-warning">
                Elasticsearch: <?php echo ucfirst($error->error->reason) ?>
            </p>
            <?php } ?>

        </div>
    </div>

    <script src="assets/js/search.js"></script>

    <?php
} else {
    echo $PMF_LANG['err_NotAuth'];
}
