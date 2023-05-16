<?php

/**
 * Overview of actions in the admin section.
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
 * @since     2003-02-23
 */

use phpMyFAQ\Component\Alert;
use phpMyFAQ\Date;
use phpMyFAQ\Filter;
use phpMyFAQ\AdminLog;
use phpMyFAQ\Pagination;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$logging = new AdminLog($faqConfig);
$csrfToken = Filter::filterInput(INPUT_GET, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);

if ($csrfToken && !Token::getInstance()->verifyToken('delete-adminlog', $csrfToken)) {
    $deleteLog = false;
} else {
    $deleteLog = true;
}

if ($user->perm->hasPermission($user->getUserId(), 'adminlog') && 'adminlog' === $action) {
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
    $pagination = new Pagination($options);

    $loggingData = $logging->getAll();
?>

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i aria-hidden="true" class="fa fa-tasks"></i> <?= Translation::get('ad_menu_adminlog') ?>
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group mr-2">
                <a class="btn btn-sm btn-danger"
                   href="?action=deleteadminlog&csrf=<?= Token::getInstance()->getTokenString('delete-adminlog') ?>">
                    <i aria-hidden="true" class="fa fa-trash"></i> <?= Translation::get('ad_adminlog_del_older_30d') ?>
                </a>
            </div>
        </div>
    </div>

    <table class="table table-striped align-middle">
    <thead>
        <tr>
            <th><?= Translation::get('ad_categ_id') ?></th>
            <th><?= Translation::get('ad_adminlog_date') ?></th>
            <th><?= Translation::get('ad_adminlog_user') ?></th>
            <th colspan="2"><?= Translation::get('ad_adminlog_ip') ?></th>
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
            $text = Strings::htmlentities($loggingValue['text']);
            $text = str_replace('Loginerror', Translation::get('ad_log_lger'), $text);
            $text = str_replace('Session expired', Translation::get('ad_log_sess'), $text);
            $text = str_replace('Useredit', Translation::get('ad_log_edit'), $text);
            $text = str_replace('admin-save-new-faq', Translation::get('ad_log_crsa'), $text);
            $text = str_replace('admin-add-faq', Translation::get('ad_log_crea'), $text);
            $text = str_replace('Usersave', Translation::get('ad_log_ussa'), $text);
            $text = str_replace('Userdel', Translation::get('ad_log_usde'), $text);
            $text = str_replace('admin-edit-faq', Translation::get('ad_log_beed'), $text);
            $text = str_replace('Beitragdel', Translation::get('ad_log_bede'), $text);
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
} elseif ($user->perm->hasPermission($user->getUserId(), 'adminlog') && 'deleteadminlog' === $action && $deleteLog) {
    if ($logging->delete()) {
        echo Alert::success('ad_adminlog_delete_success');
    } else {
        echo Alert::danger('ad_adminlog_delete_failure');
    }
} else {
    echo Translation::get('err_NotAuth');
}
