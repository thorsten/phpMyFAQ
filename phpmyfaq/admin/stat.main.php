<?php
/**
 * The main statistics page.
 *
 *
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2003-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2003-02-24
 */

use phpMyFAQ\Date;
use phpMyFAQ\Filter;
use phpMyFAQ\Session;
use phpMyFAQ\Visits;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}
?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header">
                    <i aria-hidden="true" class="fas fa-tasks"></i> <?= $PMF_LANG['ad_stat_sess'] ?>
                    <div class="float-right">
                        <a class="btn btn-danger"
                           href="?action=clear-visits&csrf=<?= $user->getCsrfTokenFromSession() ?>">
                            <i aria-hidden="true" class="fas fa-trash"></i> <?= $PMF_LANG['ad_clear_all_visits'] ?>
                        </a>
                    </div>
                </h2>
            </div>
        </header>

        <div class="row">
            <div class="col-lg-12">
<?php
if ($user->perm->checkRight($user->getUserId(), 'viewlog')) {
    $session = new Session($faqConfig);
    $date = new Date($faqConfig);
    $visits = new Visits($faqConfig);
    $statdelete = Filter::filterInput(INPUT_POST, 'statdelete', FILTER_SANITIZE_STRING);
    $month = Filter::filterInput(INPUT_POST, 'month', FILTER_SANITIZE_STRING);
    $csrfTokenFromPost = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
    $csrfTokenFromGet = Filter::filterInput(INPUT_GET, 'csrf', FILTER_SANITIZE_STRING);

    if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfTokenFromPost) {
        $statdelete = null;
    }

    if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfTokenFromGet) {
        $clearVisits = false;
    } else {
        $clearVisits = true;
    }

    // Delete sessions and session files
    if (!is_null($statdelete) && !is_null($month)) {
        $dir = opendir(PMF_ROOT_DIR.'/data');
        $first = 9999999999999999999999999;
        $last = 0;
        while ($trackingFile = readdir($dir)) {
            // The filename format is: trackingDDMMYYYY
            // e.g.: tracking02042006
            if (($trackingFile != '.') && ($trackingFile != '..') && (10 == strpos($trackingFile, $month))) {
                $candidateFirst = Date::getTrackingFileDate($trackingFile);
                $candidateLast = Date::getTrackingFileDate($trackingFile, true);
                if (($candidateLast > 0) && ($candidateLast > $last)) {
                    $last = $candidateLast;
                }
                if (($candidateFirst > 0) && ($candidateFirst < $first)) {
                    $first = $candidateFirst;
                }
                unlink(PMF_ROOT_DIR.'/data/'.$trackingFile);
            }
        }
        closedir($dir);
        $session->deleteSessions($first, $last);

        printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_adminlog_delete_success']);
    }

    // Reset all visits and sessions
    if ('clear-visits' === $action && $clearVisits) {

        // Clear visits
        $visits->resetAll();

        // Delete logifles
        $files = glob(PMF_ROOT_DIR.'/data/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        // Delete sessions
        $session->deleteAllSessions();

        printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_reset_visits_success']);
    }
    ?>
                <form action="?action=sessionbrowse" method="post" accept-charset="utf-8">

                <table class="table table-striped">
                    <tr>
                        <td><?= $PMF_LANG['ad_stat_days'];
    ?>:</td>
                        <td>
<?php
    $danz = 0;
    $first = 9999999999999999999999999;
    $last = 0;
    $dir = opendir(PMF_ROOT_DIR.'/data');
    while ($dat = readdir($dir)) {
        if ($dat != '.' && $dat != '..') {
            ++$danz;
        }
        if (Date::getTrackingFileDate($dat) > $last) {
            $last = Date::getTrackingFileDate($dat);
        }
        if (Date::getTrackingFileDate($dat) < $first && Date::getTrackingFileDate($dat) > 0) {
            $first = Date::getTrackingFileDate($dat);
        }
    }
    closedir($dir);

    echo $danz;
    ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?= $PMF_LANG['ad_stat_vis']; ?>:</td>
                        <td><?= $vanz = $session->getNumberOfSessions() ?></td>
                    </tr>
                    <tr>
                        <td><?= $PMF_LANG['ad_stat_vpd'] ?>:</td>
                        <td><?= ($danz != 0) ? round(($vanz/$danz), 2) : 0 ?></td>
                    </tr>
                    <tr>
                        <td><?= $PMF_LANG['ad_stat_fien'] ?>:</td>
                        <td>
<?php
    if (is_file(PMF_ROOT_DIR.'/data/tracking'.date('dmY', $first))) {
        $fp = @fopen(PMF_ROOT_DIR.'/data/tracking'.date('dmY', $first), 'r');
        while (($data = fgetcsv($fp, 1024, ';')) !== false) {
            $qstamp = isset($data[7]) && 10 === strlen($data[7]) ? $data[7] : $_SERVER['REQUEST_TIME'];
        }
        fclose($fp);
        echo $date->format(date('Y-m-d H:i', $qstamp));
    } else {
        echo $PMF_LANG['ad_sess_noentry'];
    }
    ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?= $PMF_LANG['ad_stat_laen'] ?>:</td>
                        <td>
<?php
    if (is_file(PMF_ROOT_DIR.'/data/tracking'.date('dmY', $last))) {
        $fp = fopen(PMF_ROOT_DIR.'/data/tracking'.date('dmY', $last), 'r');

        while (($data = fgetcsv($fp, 1024, ';')) !== false) {
            $stamp = isset($data[7]) && 10 === strlen($data[7]) ? $data[7] : $_SERVER['REQUEST_TIME'];
        }
        fclose($fp);

        if (empty($stamp)) {
            $stamp = $_SERVER['REQUEST_TIME'];
        }

        echo $date->format(date('Y-m-d H:i', $stamp)).'<br />';
    } else {
        echo $PMF_LANG['ad_sess_noentry'].'<br />';
    }

    $dir = opendir(PMF_ROOT_DIR.'/data');
    $trackingDates = [];
    while (false !== ($dat = readdir($dir))) {
        if ($dat != '.' && $dat != '..' && strlen($dat) == 16 && !is_dir($dat)) {
            $trackingDates[] = Date::getTrackingFileDate($dat);
        }
    }
    closedir($dir);
    sort($trackingDates);
    ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?= $PMF_LANG['ad_stat_browse'];
    ?>:</td>
                        <td class="col-lg-2"><select name="day" class="form-control">
<?php
    foreach ($trackingDates as $trackingDate) {
        printf('<option value="%d"', $trackingDate);
        if (date('Y-m-d', $trackingDate) == strftime('%Y-%m-%d', $_SERVER['REQUEST_TIME'])) {
            echo ' selected="selected"';
        }
        echo '>';
        echo $date->format(date('Y-m-d H:i', $trackingDate));
        echo "</option>\n";
    }
    ?>
                        </select>
                            <button class="btn btn-primary" type="submit" name="statbrowse">
                                <?= $PMF_LANG['ad_stat_ok'];
    ?>
                            </button>
                        </td>
                    </tr>
                </table>
                </form>

                <form action="?action=viewsessions" method="post" >
                <fieldset>
                    <input type="hidden" name="csrf" value="<?= $user->getCsrfTokenFromSession();
    ?>">
                    <legend><?= $PMF_LANG['ad_stat_management'];
    ?></legend>

                    <div class="control-group">
                        <label class="col-form-label" for="month"><?= $PMF_LANG['ad_stat_choose'];
    ?>:</label>
                        <div class="controls">
                            <select name="month" id="month" class="form-control">
<?php
    $oldValue = mktime(0, 0, 0, 1, 1, 1970);
    $isFirstDate = true;
    foreach ($trackingDates as $trackingDate) {
        if (date('Y-m', $oldValue) != date('Y-m', $trackingDate)) {
            // The filename format is: trackingDDMMYYYY
            // e.g.: tracking02042006
            printf('<option value="%s"', date('mY', $trackingDate));
            // Select the oldest month
            if ($isFirstDate) {
                echo ' selected="selected"';
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
                    </div>

                    <div class="form-group row">
                        <button class="btn btn-primary" type="submit" name="statdelete">
                            <?= $PMF_LANG['ad_stat_delete'];
    ?>
                        </button>
                    </div>
                </fieldset>
                </form>
<?php

} else {
    print $PMF_LANG['err_NotAuth'];
}
?>
            </div>
        </div>
