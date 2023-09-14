<?php

/**
 * Sessionbrowser.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-24
 */

use phpMyFAQ\Date;
use phpMyFAQ\Filter;
use phpMyFAQ\Session;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

if ($user->perm->hasPermission($user->getUserId(), 'viewlog')) {
    $perpage = 50;
    $day = Filter::filterInput(INPUT_POST, 'day', FILTER_VALIDATE_INT);
    $firstHour = mktime(0, 0, 0, date('m', $day), date('d', $day), date('Y', $day));
    $lastHour = mktime(23, 59, 59, date('m', $day), date('d', $day), date('Y', $day));

    $session = new Session($faqConfig);
    $sessiondata = $session->getSessionsByDate($firstHour, $lastHour);
    $date = new Date($faqConfig);
    ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i aria-hidden="true" class="fa fa-tasks"></i>
        <?= Translation::get('ad_sess_session') . ' ' . date('Y-m-d', $day) ?>
    </h1>
</div>

<div class="row">
    <div class="col-lg-12">
        <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th><?= Translation::get('ad_sess_ip') ?></th>
                <th><?= Translation::get('ad_sess_s_date') ?></th>
                <th><?= Translation::get('ad_sess_session') ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($sessiondata as $sid => $data) { ?>
            <tr>
                <td><?= $data['ip'] ?></td>
                <td><?= $date->format(date('Y-m-d H:i', $data['time'])) ?></td>
                <td><a href="?action=viewsession&amp;id=<?= $sid ?>"><?= $sid ?></a></td>
            </tr>
        <?php } ?>
        </tbody>
        </table>
    </div>
</div>
    <?php
} else {
    echo Translation::get('err_NotAuth');
}
