<?php
/**
 * phpMyFAQ system informations
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2013-01-02
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission['editconfig']) {
    $faqSystem = new PMF_System();
?>
<header>
    <h2><i class="icon-wrench"></i> <?php echo $PMF_LANG['ad_system_info']; ?></h2>
</header>

<table class="table table-striped">
    <tbody>
    <?php
    $systemInformation = array(
        'phpMyFAQ Version'           => $faqSystem->getVersion(),
        'Server Software'            => $_SERVER['SERVER_SOFTWARE'],
        'Server Document root'       => $_SERVER['DOCUMENT_ROOT'],
        'phpMyFAQ installation path' => dirname(dirname($_SERVER['SCRIPT_FILENAME'])),
        'PHP Version'                => PHP_VERSION,
        'Webserver Interface'        => strtoupper(PHP_SAPI),
        'PHP Extensions'             => implode(', ', get_loaded_extensions()),
        'PHP Session path'           => session_save_path(),
        'Database Server'            => PMF_Db::getType(),
        'Database Server Version'    => $faqConfig->getDb()->serverVersion(),
        'Database Client Version'    => $faqConfig->getDb()->clientVersion(),
    );
    foreach ($systemInformation as $name => $info): ?>
    <tr>
        <td class="span3"><strong><?php echo $name ?></strong></td>
        <td><?php echo $info ?></td>
    </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php
} else {
    echo $PMF_LANG['err_NotAuth'];
}