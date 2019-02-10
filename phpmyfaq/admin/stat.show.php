<?php
/**
 * Show the session.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
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

if ($user->perm->checkRight($user->getUserId(), 'viewlog')) {
    $sid = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    printf('<header><h2 class="page-header"><i aria-hidden="true" class="fa fa-tasks"></i> %s "<span style="color: Red;">%d</span>"</h2></header>',
        $PMF_LANG['ad_sess_session'],
        $sid);

    $session = new PMF_Session($faqConfig);
    $time = $session->getTimeFromSessionId($sid);

    $trackingdata = explode("\n", file_get_contents(PMF_ROOT_DIR.'/data/tracking'.date('dmY', $time)));
    ?>
        <table class="table table-striped">
        <tfoot>
            <tr>
                <td colspan="2"><a href="?action=viewsessions"><?php echo $PMF_LANG['ad_sess_back'];
    ?></a></td>
            </tr>
        </tfoot>
        <tbody>
<?php
        $num = 0;
    foreach ($trackingdata as $line) {
        $data = explode(';', $line);
        if ($data[0] == $sid) {
            ++$num;
            ?>
            <tr>
                <td><?php echo date('Y-m-d H:i:s', $data[7]);
            ?></td>
                <td><?php echo $data[1];
            ?> (<?php echo $data[2];
            ?>)</td>
            </tr>
<?php
                if ($num == 1) {
                    ?>
            <tr>
                <td><?php echo $PMF_LANG['ad_sess_referer'];
                    ?></td>
                <td>
                    <?php echo PMF_String::htmlentities(str_replace('?', '? ', $data[5]));
                    ?>
                </td>
            </tr>
            <tr>
                <td><?php echo $PMF_LANG['ad_sess_browser'];
                    ?></td>
                <td><?php echo PMF_String::htmlentities($data[6]);
                    ?></td>
            </tr>
            <tr>
                <td><?php echo $PMF_LANG['ad_sess_ip'];
                    ?>:</td>
                <td><?php echo PMF_String::htmlentities($data[3]);
                    ?></td>
            </tr>
<?php

                }
        }
    }
    ?>
        </tbody>
        </table>
<?php

} else {
    echo $PMF_LANG['err_NotAuth'];
}
