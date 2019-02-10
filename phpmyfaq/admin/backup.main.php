<?php
/**
 * Frontend for Backup and Restore.
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

if ($user->perm->checkRight($user->getUserId(), 'backup')) {
    ?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header">
                    <i aria-hidden="true" class="fa fa-download fa-fw"></i> <?php echo $PMF_LANG['ad_csv_backup'] ?>
                </h2>
            </div>
        </header>

        <div class="row">
            <div class="col-lg-6">

                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <?php echo $PMF_LANG['ad_csv_head'] ?>
                    </div>
                    <div class="panel-body">
                        <p><?php echo $PMF_LANG['ad_csv_make'] ?></p>
                        <p class="text-right">
                            <a class="btn btn-primary" href="backup.export.php?action=backup_content">
                                <i aria-hidden="true" class="fa fa-download fa fa-white"></i> <?php echo $PMF_LANG['ad_csv_linkdat'] ?>
                            </a>
                        </p>
                        <p class="text-right">
                            <a class="btn btn-primary" href="backup.export.php?action=backup_logs">
                                <i aria-hidden="true" class="fa fa-download fa fa-white"></i> <?php echo $PMF_LANG['ad_csv_linklog'] ?>
                            </a>
                        </p>
                    </div>
                </div>

            </div>

            <div class="col-lg-6">
                <form method="post" action="?action=restore" enctype="multipart/form-data" accept-charset="utf-8"
                    class="form-horizontal">
                    <input type="hidden" name="csrf" value="<?php echo $user->getCsrfTokenFromSession() ?>">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <?php echo $PMF_LANG['ad_csv_head2'] ?>
                        </div>
                        <div class="panel-body">
                            <p><?php echo $PMF_LANG['ad_csv_restore'] ?></p>
                            <div class="form-group">
                                <label class="col-lg-4 control-label"><?php echo $PMF_LANG['ad_csv_file'] ?>:</label>
                                <div class="col-lg-8">
                                    <input type="file" name="userfile">
                                </div>
                            </div>
                            <div class="form-group">
                                <p class="text-right">
                                    <button class="btn btn-primary" type="submit">
                                        <i aria-hidden="true" class="fa fa-upload fa fa-white"></i> <?php echo $PMF_LANG['ad_csv_ok'] ?>
                                    </button>
                                </p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
<?php

} else {
    echo $PMF_LANG['err_NotAuth'];
}
