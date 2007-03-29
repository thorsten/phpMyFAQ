<?php
/**
* $Id: backup.import.php,v 1.16 2007-03-29 15:57:53 thorstenr Exp $
*
* The import function to import the phpMyFAQ backups
*
* @author       Thorsten Rinne <thorsten@rinne.info>
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
if ($permission["restore"]) {
?>
    <h2><?php print $PMF_LANG["ad_csv_rest"]; ?></h2>
<?php
    if (isset($_FILES["userfile"]["type"]) && ($_FILES["userfile"]["type"] == "application/octet-stream" || $_FILES["userfile"]["type"] == "text/plain" || $_FILES["userfile"]["type"] == "text/x-sql")) {
        $ok = 1;
        $fp = fopen($_FILES["userfile"]["tmp_name"], "r");
        $dat = fgets($fp, 65536);

        if (substr($dat, 0, 9) != '-- pmf2.0') {
            print $PMF_LANG["ad_csv_no"];
            $ok = 0;
        } else {
            $dat = substr($dat, 11);
            $tbl = explode(' ', $dat);
            $num = count($tbl);
            for ($h = 0; $h <= $num; $h++) {
                if (isset($tbl[$h])) {
                    $mquery[] = 'DELETE FROM '.trim($tbl[$h]);
                }
            }
            $ok = 1;
        }

        if ($ok == 1) {
            $table_prefix = '';
            print "<p>".$PMF_LANG['ad_csv_prepare']."</p>\n";
            while (($dat = fgets($fp, 65536))) {
                $dat = trim($dat);
                $backup_prefix_pattern = "-- pmftableprefix:";
                $backup_prefix_pattern_len = strlen($backup_prefix_pattern);
                if (substr($dat, 0, $backup_prefix_pattern_len) == $backup_prefix_pattern) {
                    $table_prefix = trim(substr($dat, $backup_prefix_pattern_len));
                }
                if ( (substr($dat, 0, 2) != '--') && ($dat != '') ) {
                    $mquery[] = trim(substr($dat, 0, -1));
                }
            }
            fclose($fp);

            $k = 0;
            $g = 0;
            print "<p>".$PMF_LANG["ad_csv_process"]."</p>\n";
            $anz = count($mquery);
            $kg = "";
            for ($i = 0; $i < $anz; $i++) {
                $mquery[$i] = alignTablePrefix($mquery[$i], $table_prefix, SQLPREFIX);
                $kg = $db->query($mquery[$i]);
                if (!$kg) {
                    printf('<div style="font-size: 9px;"><strong>Query</strong>: "%s" <span style="color: red;">failed (Reason: %s)</span></div>%s',
                        PMF_htmlentities($mquery[$i], ENT_QUOTES, $PMF_LANG['metaCharset']),
                        $db->error(),
                        "\n");
                    $k++;
                } else {
                    printf('<div style="font-size: 9px;"><strong>Query</strong>: "%s" <span style="color: green;">okay</span></div>%s',
                        PMF_htmlentities($mquery[$i], ENT_QUOTES, $PMF_LANG['metaCharset']),
                        "\n");
                    $g++;
                }
            }
            print "<p>".$g." ".$PMF_LANG["ad_csv_of"]." ".$anz." ".$PMF_LANG["ad_csv_suc"]."</p>\n";
        }
    } else {
        print "<p>".$PMF_LANG["ad_csv_no"]."</p>";
    }
} else {
    print $PMF_LANG["err_NotAuth"];
}
