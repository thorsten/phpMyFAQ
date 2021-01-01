<?php

/**
 * phpMyFAQ system information.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2013-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2013-01-02
 */

use phpMyFAQ\Database;
use phpMyFAQ\System;

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
  <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
      <i aria-hidden="true" class="fa fa-wrench"></i>
        <?= $PMF_LANG['ad_system_info'] ?>
    </h1>
  </div>

  <div class="row">
    <div class="col-lg-12">
      <table class="table table-striped">
        <tbody>
        <?php
        $systemInformation = [
            'phpMyFAQ Version' => $faqSystem->getVersion(),
            'phpMyFAQ API Version' => $faqSystem->getApiVersion(),
            'Server Software' => $_SERVER['SERVER_SOFTWARE'],
            'Server Document Root' => $_SERVER['DOCUMENT_ROOT'],
            'phpMyFAQ Installation Path' => dirname(dirname($_SERVER['SCRIPT_FILENAME'])),
            'PHP Version' => PHP_VERSION,
            'Web server Interface' => strtoupper(PHP_SAPI),
            'PHP Extensions' => implode(', ', get_loaded_extensions()),
            'PHP Session path' => session_save_path(),
            'Database Server' => Database::getType(),
            'Database Server Version' => $faqConfig->getDb()->serverVersion(),
            'Database Client Version' => $faqConfig->getDb()->clientVersion(),
            'Elasticsearch Version' => $esInformation
        ];
        foreach ($systemInformation as $name => $info) : ?>
          <tr>
            <td style="width: 20%"><strong><?= $name ?></strong></td>
            <td><?= $info ?></td>
          </tr>
        <?php endforeach;
        ?>
        </tbody>
      </table>
    </div>
  </div>
    <?php
} else {
    echo $PMF_LANG['err_NotAuth'];
}
