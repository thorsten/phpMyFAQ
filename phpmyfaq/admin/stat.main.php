<?php
/**
 * The main statistics page
 *
 * PHP Version 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2003-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-24
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

printf('<header><h2>%s</h2></header>', $PMF_LANG['ad_stat_sess']);

if ($permission['viewlog']) {
    
    $session    = new PMF_Session($db, $Language);
    $statdelete = PMF_Filter::filterInput(INPUT_POST, 'statdelete', FILTER_SANITIZE_STRING);
    $month      = PMF_Filter::filterInput(INPUT_POST, 'month', FILTER_SANITIZE_STRING);

    if (!is_null($statdelete) && !is_null($month)) {
        // Search for related tracking data files and
        // delete them including the sid records in the faqsessions table
        $dir   = opendir(PMF_ROOT_DIR."/data");
        $first = 9999999999999999999999999;
        $last  = 0;
        while($trackingFile = readdir($dir)) {
            // The filename format is: trackingDDMMYYYY
            // e.g.: tracking02042006
            if (($trackingFile != '.') && ($trackingFile != '..') && (10 == strpos($trackingFile, $month))) {
                $candidateFirst = PMF_Date::getTrackingFileDate($trackingFile);
                $candidateLast  = PMF_Date::getTrackingFileDate($trackingFile, true);
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

        printf('<p class="success">%s</p>', $PMF_LANG['ad_adminlog_delete_success']);
    }
?>
        <form action="?action=sessionbrowse" method="post" style="display: inline;">
        <fieldset>
            <legend><?php print $PMF_LANG["ad_stat_sess"]; ?></legend>
            
            <p>
                <label><?php print $PMF_LANG["ad_stat_days"]; ?>:</label>
<?php
    $danz  = 0;
    $first = 9999999999999999999999999;
    $last  = 0;
    $dir   = opendir(PMF_ROOT_DIR."/data");
    while ($dat = readdir($dir)) {
        if ($dat != "." && $dat != "..") {
            $danz++;
        }
        if (PMF_Date::getTrackingFileDate($dat) > $last) {
            $last = PMF_Date::getTrackingFileDate($dat);
        }
        if (PMF_Date::getTrackingFileDate($dat) < $first && PMF_Date::getTrackingFileDate($dat) > 0) {
            $first = PMF_Date::getTrackingFileDate($dat);
        }
    }
    closedir($dir);

    print $danz;
?>
            </p>

            <p>
                <label><?php print $PMF_LANG["ad_stat_vis"]; ?>:</label>
                <?php print $vanz = $session->getNumberOfSessions(); ?>
            </p>

            <p>
                <label><?php print $PMF_LANG["ad_stat_vpd"]; ?>:</label>
                <?php print (($danz != 0) ? round(($vanz / $danz),2) : 0); ?>
            </p>

            <p>
                <label><?php print $PMF_LANG["ad_stat_fien"]; ?>:</label>
<?php
    if (is_file(PMF_ROOT_DIR."/data/tracking".date("dmY", $first))) {
        $fp = @fopen(PMF_ROOT_DIR."/data/tracking".date("dmY", $first), "r");
        list($dummy, $dummy, $dummy, $dummy, $dummy, $dummy, $dummy, $qstamp) = fgetcsv($fp, 1024, ";");
        fclose($fp);
        print PMF_Date::format(date('Y-m-d H:i', $qstamp));
    } else {
        print $PMF_LANG["ad_sess_noentry"];
    }
?>
            </p>

            <p>
                <label><?php print $PMF_LANG["ad_stat_laen"]; ?>:</label>
<?php
    if (is_file(PMF_ROOT_DIR."/data/tracking".date("dmY", $last))) {
        $fp = fopen(PMF_ROOT_DIR."/data/tracking".date("dmY", $last), "r");
        while (list($dummy, $dummy, $dummy, $dummy, $dummy, $dummy, $dummy, $tstamp) = fgetcsv($fp, 1024, ";")) {
            $stamp = $tstamp;
        }
        fclose($fp);

        if (empty($stamp)) {
            $stamp = $_SERVER['REQUEST_TIME'];
        }
        print PMF_Date::format(date('Y-m-d H:i', $stamp)).'<br />';
    } else {
        print $PMF_LANG["ad_sess_noentry"].'<br />';
    }

    $dir = opendir(PMF_ROOT_DIR."/data");
    $trackingDates = array();
    while (false !== ($dat = readdir($dir))) {
        if ($dat != "." && $dat != ".." && strlen($dat) == 16 && !is_dir($dat)) {
            $trackingDates[] = PMF_Date::getTrackingFileDate($dat);
        }
    }
    closedir($dir);
    sort($trackingDates);
?>
            </p>

            <p>
                <label><?php print $PMF_LANG["ad_stat_browse"]; ?>:</label>
                <select name="day" size="1">
<?php
    foreach ($trackingDates as $trackingDate) {
        printf('<option value="%d"', $trackingDate);
        if (date("Y-m-d", $trackingDate) == strftime('%Y-%m-%d', $_SERVER['REQUEST_TIME'])) {
            print ' selected="selected"';
        }
        print '>';
        print PMF_Date::format(date('Y-m-d H:i', $trackingDate));
        print "</option>\n";
    }
?>
                </select>
            </p>

            <p>
                <input class="btn-primary" type="submit" name="statbrowse" value="<?php print $PMF_LANG["ad_stat_ok"]; ?>" />
            </p>

        </fieldset>
        </form>

        <form action="?action=viewsessions" method="post" style="display: inline;">
        <fieldset>
            <legend><?php print $PMF_LANG['ad_stat_management']; ?></legend>

            <p>
                <label><?php print $PMF_LANG['ad_stat_choose']; ?>:</label>
                <select name="month" size="1">
<?php
    $oldValue = mktime(0, 0, 0, 1, 1, 1970);
    $isFirstDate = true;
    foreach ($trackingDates as $trackingDate) {
        if (date("Y-m", $oldValue) != date("Y-m", $trackingDate)) {
            // The filename format is: trackingDDMMYYYY
            // e.g.: tracking02042006
            printf('<option value="%s"', date('mY', $trackingDate));
            // Select the oldest month
            if ($isFirstDate) {
                print ' selected="selected"';
                $isFirstDate = false;
            }
            print '>';
            print date('Y-m', $trackingDate);
            print "</option>\n";
            $oldValue = $trackingDate;
        }
    }
?>
                </select>
            </p>

            <p>
            <input class="btn-primary" type="submit" name="statdelete" value="<?php print $PMF_LANG['ad_stat_delete']; ?>" />
            </p>
        </fieldset>
        </form>
<?php
} else {
    print $PMF_LANG["err_NotAuth"];
}