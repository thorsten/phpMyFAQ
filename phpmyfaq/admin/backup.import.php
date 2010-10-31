<?php
/**
 * The import function to import the phpMyFAQ backups
 * 
 * PHP Version 5.2
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
 *
 * @category  phpMyFAQ 
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-24
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$csrfToken = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
    $permission['restore'] = false; 
}

if ($permission['restore']) {
?>
    <h2><?php print $PMF_LANG["ad_csv_rest"]; ?></h2>
<?php
    if (isset($_FILES["userfile"]["type"]) && (
        $_FILES["userfile"]["type"] == "application/octet-stream" || 
        $_FILES["userfile"]["type"] == "text/plain" || 
        $_FILES["userfile"]["type"] == "text/x-sql")) {
        
        $ok  = 1;
        // @todo: Add check if file is utf-8 encoded
        $handle       = fopen($_FILES['userfile']['tmp_name'], 'r');
        $dat          = fgets($handle, 65536);
        $majorVersion = substr($faqconfig->get('main.currentVersion'), 0, 3);

        if (PMF_String::substr($dat, 0, 9) != '-- pmf' . $majorVersion) {
            print $PMF_LANG["ad_csv_no"] . ' (Version mismatch)';
            $ok = 0;
        } else {
            $dat = trim(PMF_String::substr($dat, 11));
            $tbl = explode(' ', $dat);
            $num = count($tbl);
            for ($h = 0; $h < $num; $h++) {
                $mquery[] = 'DELETE FROM '.$tbl[$h];
            }
            $ok = 1;
        }

        if ($ok == 1) {
            $table_prefix = '';
            printf("<p>%s</p>\n", $PMF_LANG['ad_csv_prepare']);
            while ($dat = fgets($handle, 65536)) {
                $dat                       = trim($dat);
                $backup_prefix_pattern     = "-- pmftableprefix:";
                $backup_prefix_pattern_len = PMF_String::strlen($backup_prefix_pattern);
                if (PMF_String::substr($dat, 0, $backup_prefix_pattern_len) == $backup_prefix_pattern) {
                    $table_prefix = trim(PMF_String::substr($dat, $backup_prefix_pattern_len));
                }
                if ( (PMF_String::substr($dat, 0, 2) != '--') && ($dat != '') ) {
                    $mquery[] = trim(PMF_String::substr($dat, 0, -1));
                }
            }

            $k = 0;
            $g = 0;
            printf("<p>%s</p>\n", $PMF_LANG['ad_csv_process']);
            $anz = count($mquery);
            $kg  = "";
            for ($i = 0; $i < $anz; $i++) {
                $mquery[$i] = alignTablePrefix($mquery[$i], $table_prefix, SQLPREFIX);
                $kg         = $db->query($mquery[$i]);
                if (!$kg) {
                    printf('<div style="font-size: 9px;"><strong>Query</strong>: "%s" <span style="color: red;">failed (Reason: %s)</span></div>%s',
                        PMF_String::htmlspecialchars($mquery[$i], ENT_QUOTES, 'utf-8'),
                        $db->error(),
                        "\n");
                    $k++;
                } else {
                    printf('<!-- <div style="font-size: 9px;"><strong>Query</strong>: "%s" <span style="color: green;">okay</span></div> -->%s',
                        PMF_String::htmlspecialchars($mquery[$i], ENT_QUOTES, 'utf-8'),
                        "\n");
                    $g++;
                }
            }
            print "<p>".$g." ".$PMF_LANG["ad_csv_of"]." ".$anz." ".$PMF_LANG["ad_csv_suc"]."</p>\n";
        }
    } else {
        printf("<p>%s (Wrong filetype: %s)</p>",
            $PMF_LANG['ad_csv_no'],
            $_FILES['userfile"]["type']);
    }
} else {
    print $PMF_LANG["err_NotAuth"];
}
