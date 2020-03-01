<?php
/**
 * Show the session.
 *
 *
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2020 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2003-02-24
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Session;
use phpMyFAQ\Strings;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

if ($user->perm->checkRight($user->getUserId(), 'viewlog')) {
    $sid = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    printf(
        '<header><h2 class="page-header"><i aria-hidden="true" class="fa fa-tasks"></i> %s "<span style="color: Red;">%d</span>"</h2></header>',
        $PMF_LANG['ad_sess_session'],
        $sid
    );

    $session = new Session($faqConfig);
    $time = $session->getTimeFromSessionId($sid);

    $trackingData = explode("\n", file_get_contents(PMF_ROOT_DIR . '/data/tracking' . date('dmY', $time)));
    ?>
  <table class="table table-striped">
    <tfoot>
    <tr>
      <td colspan="2"><a href="?action=viewsessions"><?= $PMF_LANG['ad_sess_back'] ?></a></td>
    </tr>
    </tfoot>
    <tbody>
    <?php
    $num = 0;
    foreach ($trackingData as $line) {
        $data = explode(';', $line);
        if ($data[0] == $sid) {
            ++$num;
            ?>
          <tr>
            <td><?= date('Y-m-d H:i:s', $data[7]);
                ?></td>
            <td><?= $data[1];
                ?> (<?= $data[2];
                ?>)
            </td>
          </tr>
            <?php
            if ($num == 1) {
                ?>
              <tr>
                <td><?= $PMF_LANG['ad_sess_referer'];
                    ?></td>
                <td>
                    <?= Strings::htmlentities(str_replace('?', '? ', $data[5]));
                    ?>
                </td>
              </tr>
              <tr>
                <td><?= $PMF_LANG['ad_sess_browser'];
                    ?></td>
                <td><?= Strings::htmlentities($data[6]);
                    ?></td>
              </tr>
              <tr>
                <td><?= $PMF_LANG['ad_sess_ip'];
                    ?>:
                </td>
                <td><?= Strings::htmlentities($data[3]);
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
