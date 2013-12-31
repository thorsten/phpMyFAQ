<?php
/**
 * Frontend for Backup and Restore
 *
 * PHP Version 5.4
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-24
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON'){
        $protocol = 'https';
    }
    header('Location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

printf('<header><h2><i class="icon-download-alt"></i> %s</h2></header>', $PMF_LANG['ad_csv_backup']);

if ($permission['backup']) {
?>
        <form method="post" action="?action=restore" enctype="multipart/form-data" accept-charset="utf-8">
        <fieldset>
            <legend><?php print $PMF_LANG["ad_csv_head"]; ?></legend>
            <p><?php print $PMF_LANG["ad_csv_make"]; ?></p>
            <p>
                <a class="btn btn-primary" href="backup.export.php?action=backup_content">
                    <i class="icon-download icon-white"></i> <?php print $PMF_LANG["ad_csv_linkdat"]; ?>
                </a>
                <a class="btn btn-primary" href="backup.export.php?action=backup_logs">
                    <i class="icon-download icon-white"></i> <?php print $PMF_LANG["ad_csv_linklog"]; ?>
                </a>
            </p>
        </fieldset>

        <fieldset>
            <legend><?php print $PMF_LANG["ad_csv_head2"]; ?></legend>
            <p><?php print $PMF_LANG["ad_csv_restore"]; ?></p>
            <p>
                <label><?php print $PMF_LANG["ad_csv_file"]; ?>:</label>
                <input type="hidden" name="csrf" value="<?php print $user->getCsrfTokenFromSession(); ?>" />
                <input type="file" name="userfile" size="30" />
                <button class="btn btn-primary" type="submit">
                    <i class="icon-upload icon-white"></i> <?php print $PMF_LANG["ad_csv_ok"]; ?>
                </button>
            </p>
        </fieldset>
        </form>
<?php
} else {
	print $PMF_LANG["err_NotAuth"];
}
