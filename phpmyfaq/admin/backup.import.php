<?php
/**
 * The import function to import the phpMyFAQ backups.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-24
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$csrfToken = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);

if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
    $csrfCheck = false;
} else {
    $csrfCheck = true;
}
?>
    <header>
        <h2 class="page-header">
            <i aria-hidden="true" class="fa fa-download fa-fw"></i> <?php echo $PMF_LANG['ad_csv_rest'] ?>
        </h2>
    </header>
<?php
if ($user->perm->checkRight($user->getUserId(), 'restore') && $csrfCheck) {
    if (isset($_FILES['userfile']) && 0 == $_FILES['userfile']['error']) {
        $ok = 1;
        $finfo = new finfo(FILEINFO_MIME_ENCODING);
        if ('utf-8' == $finfo->file($_FILES['userfile']['tmp_name'])) {
            print 'This file is not UTF_8 encoded.';
            $ok = 0;
        }
        $handle = fopen($_FILES['userfile']['tmp_name'], 'r');
        $dat = fgets($handle, 65536);
        $versionFound = PMF_String::substr($dat, 0, 9);
        $versionExpected = '-- pmf'.substr($faqConfig->get('main.currentVersion'), 0, 3);

        if ($versionFound !== $versionExpected) {
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
            for ($h = 0; $h < $num; ++$h) {
                $mquery[] = 'DELETE FROM '.$tbl[$h];
            }
            $ok = 1;
        }

        if ($ok == 1) {
            $tablePrefix = '';
            printf("<p>%s</p>\n", $PMF_LANG['ad_csv_prepare']);
            while ($dat = fgets($handle, 65536)) {
                $dat = trim($dat);
                $backupPrefixPattern = '-- pmftableprefix:';
                $backupPrefixPatternLength = PMF_String::strlen($backupPrefixPattern);
                if (PMF_String::substr($dat, 0, $backupPrefixPatternLength) === $backupPrefixPattern) {
                    $tablePrefix = trim(PMF_String::substr($dat, $backupPrefixPatternLength));
                }
                if ((PMF_String::substr($dat, 0, 2) != '--') && ($dat != '')) {
                    $mquery[] = trim(PMF_String::substr($dat, 0, -1));
                }
            }

            $k = 0;
            $g = 0;
            printf("<p>%s</p>\n", $PMF_LANG['ad_csv_process']);
            $num = count($mquery);
            $kg = '';
            for ($i = 0; $i < $num; ++$i) {
                $mquery[$i] = PMF_DB_Helper::alignTablePrefix($mquery[$i], $tablePrefix, PMF_Db::getTablePrefix());
                $kg = $faqConfig->getDb()->query($mquery[$i]);
                if (!$kg) {
                    printf(
                    '<div style="alert alert-danger"><strong>Query</strong>: "%s" failed (Reason: %s)</div>%s',
                        PMF_String::htmlspecialchars($query, ENT_QUOTES, 'utf-8'),
                        $faqConfig->getDb()->error(),
                        "\n"
                    );
                    ++$k;
                } else {
                    printf(
                        '<!-- <div class="alert alert-success"><strong>Query</strong>: "%s" okay</div> -->%s',
                        PMF_String::htmlspecialchars($query, ENT_QUOTES, 'utf-8'),
                        "\n"
                    );
                    ++$g;
                }
            }
            printf(
                '<p class="alert alert-success">%d %s %d %s</p>',
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
                $errorMessage = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the ' .
                                'HTML form.';
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
        printf('<p class="alert alert-danger">%s (%s)</p>', $PMF_LANG['ad_csv_no'], $errorMessage);
    }
} else {
    print $PMF_LANG['err_NotAuth'];
}
