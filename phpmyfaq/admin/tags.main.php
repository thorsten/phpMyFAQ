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

?>
    <header class="row">
        <div class="col-lg-12">
            <h2 class="page-header">
                <i class="fa fa-tags"></i> <?php echo $PMF_LANG['ad_entry_tags'] ?>
            </h2>
        </div>
    </header>

    <div class="row">
        <div class="col-lg-12">

<?php
if ($user->perm->checkRight($user->getUserId(), 'editbt')) {

} else {
    echo $PMF_LANG["err_NotAuth"];
}
?>
        </div>
    </div>