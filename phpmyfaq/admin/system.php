<?php

/**
 * phpMyFAQ system information.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2013-2022 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2013-01-02
 */

use phpMyFAQ\Database;
use phpMyFAQ\System;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

if ($user->perm->hasPermission($user->getUserId(), 'editconfig')) {
    $faqSystem = new System();

    $esConfig = $faqConfig->getElasticsearchConfig();

    if ($faqConfig->get('search.enableElasticsearch')) {
        $esFullInformation = $faqConfig->getElasticsearch()->info();
        $esInformation = $esFullInformation['version']['number'];
    } else {
        $esInformation = 'n/a';
    }
    ?>
    <div
        class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i aria-hidden="true" class="fa fa-wrench"></i>
            <?= Translation::get('ad_system_info') ?>
        </h1>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <table class="table table-striped align-middle">
                <tbody>
                <?php
                $systemInformation = [
                    'phpMyFAQ Version' => $faqSystem->getVersion(),
                    'phpMyFAQ API Version' => $faqSystem->getApiVersion(),
                    'phpMyFAQ Installation Path' => dirname($_SERVER['SCRIPT_FILENAME'], 2),
                    'Web server software' => $_SERVER['SERVER_SOFTWARE'],
                    'Web server document root' => $_SERVER['DOCUMENT_ROOT'],
                    'Web server Interface' => strtoupper(PHP_SAPI),
                    'PHP version' => PHP_VERSION,
                    'PHP extensions' => implode(', ', get_loaded_extensions()),
                    'PHP session path' => session_save_path(),
                    'Database server' => Database::getType(),
                    'Database server version' => $faqConfig->getDb()->serverVersion(),
                    'Database client version' => $faqConfig->getDb()->clientVersion(),
                    'Elasticsearch version' => $esInformation
                ];
                foreach ($systemInformation as $name => $info) : ?>
                    <tr>
                        <td class="col-2"><strong><?= $name ?></strong></td>
                        <td><?= $info ?></td>
                    </tr>
                    <?php
                endforeach;
                ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
} else {
    echo Translation::get('err_NotAuth');
}
