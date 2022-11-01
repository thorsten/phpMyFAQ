<?php

/**
 * Overview of actions in the admin section.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2022 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-23
 */

use phpMyFAQ\Date;
use phpMyFAQ\Filter;
use phpMyFAQ\Logging;
use phpMyFAQ\Pagination;
use phpMyFAQ\Strings;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$logging = new Logging($faqConfig);
$csrfToken = Filter::filterInput(INPUT_GET, 'csrf', FILTER_UNSAFE_RAW);

if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
    $deleteLog = false;
} else {
    $deleteLog = true;
}

if ($user->perm->hasPermission($user->getUserId(), 'adminlog') && 'adminlog' == $action) {
    $date = new Date($faqConfig);
    $perpage = 15;
    $pages = Filter::filterInput(INPUT_GET, 'pages', FILTER_VALIDATE_INT);
    $page = Filter::filterInput(INPUT_GET, 'page', FILTER_VALIDATE_INT, 1);

    if (is_null($pages)) {
        $pages = round(($logging->getNumberOfEntries() + ($perpage / 3)) / $perpage, 0);
    }

    $start = ($page - 1) * $perpage;
    $lastPage = $start + $perpage;

    $baseUrl = sprintf(
        '%sadmin/?action=adminlog&amp;page=%d',
        $faqConfig->getDefaultUrl(),
        $page
    );

    // Pagination options
    $options = [
        'baseUrl' => $baseUrl,
        'total' => $logging->getNumberOfEntries(),
        'perPage' => $perpage,
        'pageParamName' => 'page',
    ];
    $pagination = new Pagination($faqConfig, $options);

    $loggingData = $logging->getAll();
    ?>
    <header class="row">
        <div class="col-lg-12">
            <h2 class="page-header">
                <i aria-hidden="true" class="fa fa-tasks"></i> <?= $PMF_LANG['ad_menu_adminlog'] ?>
                <div class="float-right">
                    <a class="btn btn-danger"
                       href="?action=deleteadminlog&csrf=<?= $user->getCsrfTokenFromSession() ?>">
                        <i aria-hidden="true" class="fa fa-trash"></i> <?= $PMF_LANG['ad_adminlog_del_older_30d'] ?>
                    </a>
                </div>
            </h2>
        </div>
    </header>

    <table class="table table-striped">
    <thead>
        <tr>
            <th><?= $PMF_LANG['ad_categ_id'] ?></th>
            <th><?= $PMF_LANG['ad_adminlog_date'] ?></th>
            <th><?= $PMF_LANG['ad_adminlog_user'] ?></th>
            <th colspan="2"><?= $PMF_LANG['ad_adminlog_ip'] ?></th>
        </tr>
    </thead>
    <tfoot>
        <tr>
            <td colspan="5"><?= $pagination->render() ?></td>
        </tr>
    </tfoot>
    <tbody>
    <?php
    $counter = $displayedCounter = 0;

    foreach ($loggingData as $loggingId => $loggingValue) {
        if ($displayedCounter >= $perpage) {
            ++$displayedCounter;
            continue;
        }

        ++$counter;
        if ($counter <= $start) {
            continue;
        }
        ++$displayedCounter;

        $user->getUserById($loggingValue['usr'], true);
        ?>
        <tr>
            <td><?= $loggingId ?></td>
            <td><?= $date->format(date('Y-m-d H:i', $loggingValue['time'])) ?></td>
            <td><?= Strings::htmlentities($user->getLogin()) ?></td>
            <td><?= $loggingValue['ip'] ?></td>
            <td><small><?php
            $text = $loggingValue['text'];
            $text = str_replace('Loginerror', $PMF_LANG['ad_log_lger'], $text);
            $text = str_replace('Session expired', $PMF_LANG['ad_log_sess'], $text);
            $text = str_replace('Useredit, ', $PMF_LANG['ad_log_edit'], $text);
            $text = str_replace('admin-save-new-faq', $PMF_LANG['ad_log_crsa'], $text);
            $text = str_replace('admin-add-faq', $PMF_LANG['ad_log_crea'], $text);
            $text = str_replace('Usersave, ', $PMF_LANG['ad_log_ussa'], $text);
            $text = str_replace('Userdel, ', $PMF_LANG['ad_log_usde'], $text);
            $text = str_replace('admin-edit-faq, ', $PMF_LANG['ad_log_beed'], $text);
            $text = str_replace('Beitragdel, ', $PMF_LANG['ad_log_bede'], $text);
            echo $text;
            ?></small>
            </td>
        </tr>
        <?php
    }
    ?>
    </tbody>
    </table>

    <?php
} elseif ($user->perm->hasPermission($user->getUserId(), 'adminlog') && 'deleteadminlog' == $action && $deleteLog) {
    if ($logging->delete()) {
        printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_adminlog_delete_success']);
    } else {
        printf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_adminlog_delete_failure']);
    }
} else {
    echo $PMF_LANG['err_NotAuth'];
}
