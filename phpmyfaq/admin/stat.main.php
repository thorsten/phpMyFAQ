<?php
/**
* $Id: stat.main.php,v 1.11 2006-10-01 15:52:50 matteo Exp $
*
* The main statistics page
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Matteo Scaramuccia <matteo@scaramuccia.com>
* @since        2003-02-24
* @copyright    (c) 2001-2006 phpMyFAQ Team
*
* The contents of this file are subject to the Mozilla Public License
* Version 1.1 (the "License"); you may not use this file except in
* compliance with the License. You may obtain a copy of the License at
* http://www.mozilla.org/MPL/
*
* Software distributed under the License is distributed on an "AS IS"
* basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
* License for the specific language governing rights and limitations
* under the License.
*/

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission["viewlog"]) {
    if (isset($_POST['statdelete']) && isset($_POST['month']) && is_numeric($_POST['month'])) {
        // Search for related tracking data files and
        // delete them including the sid records in the faqsessions table
        $dir = opendir(PMF_ROOT_DIR."/data");
        $first = 9999999999999999999999999;
        $last  = 0;
        while($trackingFile = readdir($dir)) {
            // The filename format is: trackingDDMMYYYY
            // e.g.: tracking02042006
            if (($trackingFile != '.') && ($trackingFile != '..') && (10 == strpos($trackingFile, $_POST['month']))) {
                $candidateFirst = FileToDate($trackingFile);
                $candidateLast  = FileToDate($trackingFile, true);
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
        $result = $db->query('DELETE FROM '.SQLPREFIX.'faqsessions WHERE time >= '.$first.' AND time <= '.$last);
    }
?>
    <h2><?php print $PMF_LANG["ad_stat_sess"]; ?></h2>

    <form action="<?php print $_SERVER["PHP_SELF"].$linkext ?>" method="post" style="display: inline;">
    <input type="hidden" name="action" value="sessionbrowse" />

    <fieldset>
    <legend><?php print $PMF_LANG["ad_stat_sess"]; ?></legend>
        <label class="left"><?php print $PMF_LANG["ad_stat_days"]; ?>:</label>
<?php
    $danz = 0;
    $fir = 9999999999999999999999999;
    $las = 0;
    $dir = opendir(PMF_ROOT_DIR."/data");
    while($dat = readdir($dir)) {
        if ($dat != "." && $dat != "..") {
            $danz++;
        }
        if (FileToDate($dat) > $las) {
            $las = FileToDate($dat);
        }
        if (FileToDate($dat) < $fir && FileToDate($dat) > 0) {
            $fir = FileToDate($dat);
        }
    }
    closedir($dir);

    print $danz;
?>
        <br />
        <label class="left"><?php print $PMF_LANG["ad_stat_vis"]; ?>:</label>
        <?php print $vanz = $db->num_rows($db->query("SELECT sid FROM ".SQLPREFIX."faqsessions")); ?><br />

        <label class="left"><?php print $PMF_LANG["ad_stat_vpd"]; ?>:</label>
        <?php print (($danz != 0) ? round(($vanz / $danz),2) : 0); ?><br />

        <label class="left"><?php print $PMF_LANG["ad_stat_fien"]; ?>:</label>
<?php
    if (is_file(PMF_ROOT_DIR."/data/tracking".date("dmY", $fir))) {
        $fp = @fopen(PMF_ROOT_DIR."/data/tracking".date("dmY", $fir), "r");
        list($dummy, $dummy, $dummy, $dummy, $dummy, $dummy, $dummy, $qstamp) = fgetcsv($fp, 1024, ";");
        fclose($fp);
        print date("d.m.Y H:i:s", $qstamp);
    } else {
        print $PMF_LANG["ad_sess_noentry"];
    }
?>
        <br />
        <label class="left"><?php print $PMF_LANG["ad_stat_laen"]; ?>:</label>
<?php
    if (is_file(PMF_ROOT_DIR."/data/tracking".date("dmY", $las))) {
        $fp = fopen(PMF_ROOT_DIR."/data/tracking".date("dmY", $las), "r");
        while (list($dummy, $dummy, $dummy, $dummy, $dummy, $dummy, $dummy, $tstamp) = fgetcsv($fp, 1024, ";")) {
            $stamp = $tstamp;
        }
        fclose($fp);

        if (empty($stamp)) {
            $stamp = time();
        }
        print date("d.m.Y H:i:s", $stamp).'<br />';
    } else {
        print $PMF_LANG["ad_sess_noentry"].'<br />';
    }

    $dir = opendir(PMF_ROOT_DIR."/data");
    $trackingDates = array();
    while (false !== ($dat = readdir($dir))) {
        if ($dat != "." && $dat != ".." && strlen($dat) == 16 && !is_dir($dat)) {
            $trackingDates[] = FileToDate($dat);
        }
    }
    closedir($dir);
    sort($trackingDates);
?>
        <label class="left"><?php print $PMF_LANG["ad_stat_browse"]; ?>:</label>
        <select name="day" size="1">
<?php
    foreach ($trackingDates as $trackingDate) {
        printf('<option value="%d"', $trackingDate);
        if (date("Y-m-d", $trackingDate) == strftime('%Y-%m-%d', time())) {
            print ' selected="selected"';
        }
        print '>';
        print date('Y-m-d', $trackingDate);
        print "</option>\n";
    }
?>
        </select><br />

        <div align="center">
        <input class="submit" type="submit" name="statbrowse" value="<?php print $PMF_LANG["ad_stat_ok"]; ?>" />
        </div>

        <p align="center"><a href="<?php print $_SERVER["PHP_SELF"].$linkext; ?>&amp;action=sessionsuche&amp;statstart=<?php print $qstamp ?>&amp;statend=<?php print $stamp ?>"><?php print $PMF_LANG["ad_sess_search"]; ?></a></p>

    </fieldset>
    </form>

    <form action="<?php print $_SERVER["PHP_SELF"].$linkext ?>" method="post" style="display: inline;">
    <input type="hidden" name="action" value="viewsessions" />

    <fieldset>
    <legend><?php print $PMF_LANG['ad_stat_management']; ?></legend>
        <label class="left"><?php print $PMF_LANG['ad_stat_choose']; ?>:</label>
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
        </select><br />

        <div align="center">
        <input class="submit" type="submit" name="statdelete" value="<?php print $PMF_LANG['ad_stat_delete']; ?>" />
        </div>

    </fieldset>
    </form>
<?php
} else {
    print $PMF_LANG["err_NotAuth"];
}
?>
