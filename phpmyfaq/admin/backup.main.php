<?php
/**
 * Frontend for Backup and Restore
 *
 * PHP Version 5.2
 *

 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-24
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

printf('<header><h2>%s</h2></header>', $PMF_LANG['ad_csv_backup']);

if ($permission['backup']) {
?>
        <form method="post" action="?action=restore" enctype="multipart/form-data">
        <fieldset>
            <legend><?php print $PMF_LANG["ad_csv_head"]; ?></legend>
            <p><?php print $PMF_LANG["ad_csv_make"]; ?></p>
            <p align="center">
                <a href="backup.export.php?action=backup_content"><?php print $PMF_LANG["ad_csv_linkdat"]; ?></a> |
                <a href="backup.export.php?action=backup_logs"><?php print $PMF_LANG["ad_csv_linklog"]; ?></a>
            </p>
        </fieldset>

        <fieldset>
            <legend><?php print $PMF_LANG["ad_csv_head2"]; ?></legend>
            <p><?php print $PMF_LANG["ad_csv_restore"]; ?></p>
            <p>
                <label><?php print $PMF_LANG["ad_csv_file"]; ?>:</label>
                <input type="hidden" name="csrf" value="<?php print $user->getCsrfTokenFromSession(); ?>" />
                <input type="file" name="userfile" size="30" />
            </p>
            <p>
                <input class="btn-primary btn-large" type="submit" value="<?php print $PMF_LANG["ad_csv_ok"]; ?>" />
            </p>
        </fieldset>
        </form>
<?php
} else {
	print $PMF_LANG["err_NotAuth"];
}
