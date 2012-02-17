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
 * @copyright 2003-2011 phpMyFAQ Team
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

    printf("<header><h2>%s</h2></header>\n", $PMF_LANG['ad_csv_rest']);

    if (isset($_FILES['userfile']) && 0 == $_FILES['userfile']['error']) {
        
        $ok  = 1;
        // @todo: Add check if file is utf-8 encoded
        $handle          = fopen($_FILES['userfile']['tmp_name'], 'r');
        $dat             = fgets($handle, 65536);
        $versionFound    = PMF_String::substr($dat, 0, 9);
        $versionExpected = '-- pmf' . substr($faqconfig->get('main.currentVersion'), 0, 3);

        if ($versionFound != $versionExpected) {
            printf('%s (Version check failure: "%s" found, "%s" expected)',
                $PMF_LANG['ad_csv_no'],
                $versionFound,
                $versionExpected
            );
            $ok = 0;
        } else {
            // @todo: Start transaction for better recovery if something really bad happens
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
            $num = count($mquery);
            $kg  = '';
            for ($i = 0; $i < $num; $i++) {
                $mquery[$i] = alignTablePrefix($mquery[$i], $table_prefix, SQLPREFIX);
                $kg         = $db->query($mquery[$i]);
                if (!$kg) {
                    printf('<div style="alert alert-error"><strong>Query</strong>: "%s" failed (Reason: %s)</div>%s',
                        PMF_String::htmlspecialchars($mquery[$i], ENT_QUOTES, 'utf-8'),
                        $db->error(),
                        "\n");
                    $k++;
                } else {
                    printf('<!-- <div style="alert alert-success"><strong>Query</strong>: "%s" okay</div> -->%s',
                        PMF_String::htmlspecialchars($mquery[$i], ENT_QUOTES, 'utf-8'),
                        "\n");
                    $g++;
                }
            }
            printf('<p class="alert alert-success">%d %s %d %s</p>',
                $g,
                $PMF_LANG['ad_csv_of'],
                $num,
                $PMF_LANG['ad_csv_suc']
            );
        }
    } else {
        switch ($_FILES['userfile']['error']) {
            case 1:
                $errorMessage = 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
                break;
            case 2:
                $errorMessage = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
                break;
            case 3:
                $errorMessage = 'The uploaded file was only partially uploaded.';
                break;
            case 4:
                $errorMessage = 'No file was uploaded.';
                break;
            case 6:
                $errorMessage = 'Missing a temporary folder.';
                break;
            case 7:
                $errorMessage = 'Failed to write file to disk.';
                break;
            case 8:
                $errorMessage = 'A PHP extension stopped the file upload.';
                break;
            default:
                $errorMessage = 'Undefined error.';
                break;
        }
        printf('<p class="alert alert-error">%s (%s)</p>', $PMF_LANG['ad_csv_no'], $errorMessage);
    }
} else {
    print $PMF_LANG['err_NotAuth'];
}
