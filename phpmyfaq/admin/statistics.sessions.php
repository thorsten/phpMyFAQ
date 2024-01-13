<?php

/**
 * The main statistics page.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2003-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-24
 */

use phpMyFAQ\Component\Alert;
use phpMyFAQ\Date;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\StatisticsHelper;
use phpMyFAQ\Session;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\Visits;
use Symfony\Component\HttpFoundation\Request;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$request = Request::createFromGlobals();

?>

    <div
        class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i aria-hidden="true" class="bi bi-list-ol"></i> <?= Translation::get('ad_stat_sess') ?>
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group mr-2">
                <a class="btn btn-outline-danger"
                   href="?action=clear-visits&csrf=<?= Token::getInstance()->getTokenString('clear-visits') ?>">
                    <i aria-hidden="true" class="bi bi-trash"></i> <?= Translation::get('ad_clear_all_visits') ?>
                </a>
            </div>
        </div>
    </div>

<?php
if ($user->perm->hasPermission($user->getUserId(), 'viewlog')) {
    $session = new Session($faqConfig);
    $date = new Date($faqConfig);
    $visits = new Visits($faqConfig);
    $statisticsHelper = new StatisticsHelper($session, $visits, $date);

    $stats = $statisticsHelper->getTrackingFilesStatistics();

    $statdelete = Filter::filterVar($request->request->get('statdelete'), FILTER_SANITIZE_SPECIAL_CHARS);
    $month = Filter::filterVar($request->request->get('month'), FILTER_SANITIZE_SPECIAL_CHARS);
    $csrfTokenFromPost = Filter::filterVar($request->request->get('csrf'), FILTER_SANITIZE_SPECIAL_CHARS);
    $csrfTokenFromGet = Filter::filterVar($request->query->get('csrf'), FILTER_SANITIZE_SPECIAL_CHARS);

    if ($csrfTokenFromPost && !Token::getInstance()->verifyToken('sessions', $csrfTokenFromPost)) {
        $statdelete = null;
    }

    if ($csrfTokenFromGet && !Token::getInstance()->verifyToken('clear-visits', $csrfTokenFromGet)) {
        $clearVisits = false;
    } else {
        $clearVisits = true;
    }

    // Delete sessions and session files
    if ($statdelete == 'delete' && $month !== '') {
        $statisticsHelper->deleteTrackingFiles($month);

        echo Alert::success('ad_adminlog_delete_success');
    }

    // Reset all visits and sessions
    if ('clear-visits' === $action && $clearVisits) {
        $statisticsHelper->clearAllVisits();

        echo Alert::success('ad_reset_visits_success');
    }
    ?>
    <div class="row">
        <div class="col-6">
            <div class="card shadow">
                <div class="card-body">
                    <table class="table table-striped align-middle">
                        <tr>
                            <td><?= Translation::get('ad_stat_days') ?>:</td>
                            <td><?= $stats->numberOfDays ?></td>
                        </tr>
                        <tr>
                            <td><?= Translation::get('ad_stat_vis'); ?>:</td>
                            <td><?= $numberOfSessions = $session->getNumberOfSessions() ?></td>
                        </tr>
                        <tr>
                            <td><?= Translation::get('ad_stat_vpd') ?>:</td>
                            <td><?= ($stats->numberOfDays != 0) ? round(
                                ($numberOfSessions / $stats->numberOfDays),
                                2
                            ) : 0 ?></td>
                        </tr>
                        <tr>
                            <td><?= Translation::get('ad_stat_fien') ?>:</td>
                            <td>
                                <?= $statisticsHelper->getFirstTrackingDate($stats->firstDate) ?>
                            </td>
                        </tr>
                        <tr>
                            <td><?= Translation::get('ad_stat_laen') ?>:</td>
                            <td><?= $statisticsHelper->getLastTrackingDate($stats->lastDate) ?></td>
                        </tr>
                        <tr>
                            <td><?= Translation::get('ad_stat_browse') ?>:</td>
                            <td class="col-lg-10">
                                <form action="?action=sessionbrowse" method="post" accept-charset="utf-8"
                                      class="row row-cols-lg-auto g-3 align-items-center">
                                    <div class="mr-2">
                                        <label for="day" class="d-none"><?= Translation::get(
                                            'ad_stat_browse'
                                                                        ) ?></label>
                                        <select name="day" id="day" class="form-select">
                                            <?php
                                            foreach ($statisticsHelper->getAllTrackingDates() as $trackingDate) {
                                                printf('<option value="%d"', $trackingDate);
                                                if (
                                                    date('Y-m-d', $trackingDate) == date(
                                                        'Y-m-d',
                                                        $request->server->get('REQUEST_TIME')
                                                    )
                                                ) {
                                                    echo ' selected="selected"';
                                                }
                                                echo '>';
                                                echo $date->format(date('Y-m-d H:i', $trackingDate));
                                                echo "</option>\n";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <button class="btn btn-primary" type="submit" name="statbrowse">
                                        <?= Translation::get('ad_stat_ok') ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-6">
            <div class="card shadow">
                <h5 class="card-header py-3">
                    <?= Translation::get('ad_stat_management') ?>
                </h5>
                <div class="card-body">
                    <form action="?action=viewsessions" method="post"
                          class="row row-cols-lg-auto g-3 align-items-center">
                        <input type="hidden" name="statdelete" value="delete">
                        <div class="col-12">
                            <?= Token::getInstance()->getTokenInput('sessions') ?>

                            <label class="form-label" for="month"><?= Translation::get('ad_stat_choose') ?>:</label>
                            <select name="month" id="month" class="form-select">
                                <?php
                                $oldValue = mktime(0, 0, 0, 1, 1, 1970);
                                $isFirstDate = true;
                                foreach ($statisticsHelper->getAllTrackingDates() as $trackingDate) {
                                    if (date('Y-m', $oldValue) != date('Y-m', $trackingDate)) {
                                        // The filename format is: trackingDDMMYYYY
                                        // e.g.: tracking02042006
                                        printf('<option value="%s"', date('mY', $trackingDate));
                                        // Select the oldest month
                                        if ($isFirstDate) {
                                            echo ' selected';
                                            $isFirstDate = false;
                                        }
                                        echo '>';
                                        echo date('Y-m', $trackingDate);
                                        echo "</option>\n";
                                        $oldValue = $trackingDate;
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary" type="submit">
                                <?= Translation::get('ad_stat_delete') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php
} else {
    echo Alert::danger('err_NotAuth');
}
