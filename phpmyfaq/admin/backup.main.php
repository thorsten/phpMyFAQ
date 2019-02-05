<?php
/**
 * Frontend for Backup and Restore.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2003-02-24
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($user->perm->checkRight($user->getUserId(), 'backup')) {
?>
  <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
      <i aria-hidden="true" class="fas fa-download"></i>
        <?= $PMF_LANG['ad_csv_backup'] ?>
    </h1>
  </div>

  <div class="card-deck">
    <div class="card">
      <div class="card-header">
          <?= $PMF_LANG['ad_csv_head'] ?>
      </div>
      <div class="card-body">
        <p><?= $PMF_LANG['ad_csv_make'] ?></p>
        <p>
          <a class="btn btn-primary" href="backup.export.php?action=backup_content">
            <i aria-hidden="true" class="fas fa-download"></i> <?= $PMF_LANG['ad_csv_linkdat'] ?>
          </a>
        </p>
        <p>
          <a class="btn btn-primary" href="backup.export.php?action=backup_logs">
            <i aria-hidden="true" class="fas fa-download"></i> <?= $PMF_LANG['ad_csv_linklog'] ?>
          </a>
        </p>
      </div>
    </div>
      <div class="card text-white bg-danger mb-3">
        <form method="post" action="?action=restore&csrf=<?= $user->getCsrfTokenFromSession() ?>"
              enctype="multipart/form-data">
          <div class="card-header">
              <?= $PMF_LANG['ad_csv_head2'] ?>
          </div>
          <div class="card-body">
            <p><?= $PMF_LANG['ad_csv_restore'] ?></p>
            <div class="form-group row">
              <label class="col-lg-4 col-form-label"><?= $PMF_LANG['ad_csv_file'] ?>:</label>
              <div class="col-lg-8">
                <input type="file" name="userfile">
              </div>
            </div>
          </div>
          <div class="card-footer text-right">
            <button class="btn btn-primary" type="submit">
              <i aria-hidden="true" class="fas fa-download"></i> <?= $PMF_LANG['ad_csv_ok'] ?>
            </button>
          </div>
        </form>
      </div>

  </div>

<?php

} else {
    echo $PMF_LANG['err_NotAuth'];
}
