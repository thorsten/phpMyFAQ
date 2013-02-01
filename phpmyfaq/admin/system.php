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

$faqSystem = new PMF_System();
?>
<header>
    <h3><?php echo $PMF_LANG['ad_system_info']; ?></h3>
</header>

<table class="table table-striped">
    <tbody>
    <?php
    $systemInformation = array(
        'phpMyFAQ Version'    => $faqSystem->getVersion(),
        'Server Software'     => $_SERVER['SERVER_SOFTWARE'],
        'PHP Version'         => PHP_VERSION,
        'Server path'         => $_SERVER['DOCUMENT_ROOT'],
        'DB Server'           => PMF_Db::getType(),
        'DB Client Version'   => $faqConfig->getDb()->clientVersion(),
        'DB Server Version'   => $faqConfig->getDb()->serverVersion(),
        'Webserver Interface' => strtoupper(@php_sapi_name()),
        'PHP Extensions'      => implode(', ', get_loaded_extensions())
    );
    foreach ($systemInformation as $name => $info): ?>
    <tr>
        <td class="span3"><strong><?php echo $name ?></strong></td>
        <td><?php echo $info ?></td>
    </tr>
        <?php endforeach; ?>
    </tbody>
</table>