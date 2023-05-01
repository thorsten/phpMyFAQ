<?php

/**
 * The main statistics page.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2003-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-24
 */

use phpMyFAQ\Component\Alert;
use phpMyFAQ\Date;
use phpMyFAQ\Filter;
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

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i aria-hidden="true" class="fa fa-tasks"></i> <?= Translation::get('ad_stat_sess') ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group mr-2">
            <a class="btn btn-sm btn-danger"
               href="?action=clear-visits&csrf=<?= Token::getInstance()->getTokenString('clear-visits') ?>">
                <i aria-hidden="true" class="fa fa-trash"></i> <?= Translation::get('ad_clear_all_visits') ?>
            </a>
        </div>
    </div>
</div>

<div class="row">
  <div class="col-lg-12">
      <?php
        if ($user->perm->hasPermission($user->getUserId(), 'viewlog')) {
            $session = new Session($faqConfig);
            $date = new Date($faqConfig);
            $visits = new Visits($faqConfig);

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
                $dir = opendir(PMF_ROOT_DIR . '/data');
                $first = 9999999999999999999;
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
                        unlink(PMF_ROOT_DIR . '/data/' . $trackingFile);
                    }
                }
                closedir($dir);

                $session->deleteSessions($first, $last);

                echo Alert::success('ad_adminlog_delete_success');
            }

            // Reset all visits and sessions
            if ('clear-visits' === $action && $clearVisits) {
                // Clear visits
                $visits->resetAll();

                // Delete logifles
                $files = glob(PMF_ROOT_DIR . '/data/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }

                // Delete sessions
                $session->deleteAllSessions();

                echo Alert::success('ad_reset_visits_success');
            }
            ?>

        <table class="table table-striped align-middle">
          <tr>
            <td><?= Translation::get('ad_stat_days') ?>:</td>
            <td>
                <?php
                $danz = 0;
                $first = 9999999999999999999999999;
                $last = 0;
                $dir = opendir(PMF_ROOT_DIR . '/data');
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
            <td><?= Translation::get('ad_stat_vis'); ?>:</td>
            <td><?= $vanz = $session->getNumberOfSessions() ?></td>
          </tr>
          <tr>
            <td><?= Translation::get('ad_stat_vpd') ?>:</td>
            <td><?= ($danz != 0) ? round(($vanz / $danz), 2) : 0 ?></td>
          </tr>
          <tr>
            <td><?= Translation::get('ad_stat_fien') ?>:</td>
            <td>
                <?php
                if (is_file(PMF_ROOT_DIR . '/data/tracking' . date('dmY', $first))) {
                    $fp = @fopen(PMF_ROOT_DIR . '/data/tracking' . date('dmY', $first), 'r');
                    while (($data = fgetcsv($fp, 1024, ';')) !== false) {
                        $qstamp = isset($data[7]) && 10 === strlen($data[7]) ? $data[7] : $request->server->get('REQUEST_TIME');
                    }
                    fclose($fp);
                    echo $date->format(date('Y-m-d H:i', $qstamp));
                } else {
                    echo Translation::get('ad_sess_noentry');
                }
                ?>
            </td>
          </tr>
          <tr>
            <td><?= Translation::get('ad_stat_laen') ?>:</td>
            <td>
                <?php
                if (is_file(PMF_ROOT_DIR . '/data/tracking' . date('dmY', $last))) {
                    $fp = fopen(PMF_ROOT_DIR . '/data/tracking' . date('dmY', $last), 'r');

                    while (($data = fgetcsv($fp, 1024, ';')) !== false) {
                        $stamp = isset($data[7]) && 10 === strlen($data[7]) ? $data[7] : $request->server->get('REQUEST_TIME');
                    }
                    fclose($fp);

                    if (empty($stamp)) {
                        $stamp = $request->server->get('REQUEST_TIME');
                    }

                    echo $date->format(date('Y-m-d H:i', $stamp)) . '<br>';
                } else {
                    echo Translation::get('ad_sess_noentry') . '<br>';
                }

                $dir = opendir(PMF_ROOT_DIR . '/data');
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
            <td><?= Translation::get('ad_stat_browse') ?>:</td>
            <td class="col-lg-10">
              <form action="?action=sessionbrowse" method="post" accept-charset="utf-8"
                    class="row row-cols-lg-auto g-3 align-items-center">
                <div class="mr-2">
                  <label for="day" class="d-none"><?= Translation::get('ad_stat_browse') ?></label>
                  <select name="day" id="day" class="form-select">
                      <?php
                        foreach ($trackingDates as $trackingDate) {
                            printf('<option value="%d"', $trackingDate);
                            if (date('Y-m-d', $trackingDate) == date('Y-m-d', $request->server->get('REQUEST_TIME'))) {
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

        <h4>
            <?= Translation::get('ad_stat_management') ?>
        </h4>

        <form action="?action=viewsessions" method="post" class="row row-cols-lg-auto g-3 align-items-center">
            <input type="hidden" name="statdelete" value="delete">
            <div class="col-12">
                <?= Token::getInstance()->getTokenInput('sessions') ?>

                  <label class="form-label" for="month"><?= Translation::get('ad_stat_choose') ?>:</label>
                  <select name="month" id="month" class="form-select">
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
            <?php
        } else {
            print Translation::get('err_NotAuth');
        }
        ?>
  </div>
</div>
