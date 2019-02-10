<?php

use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;

/**
 * phpMyFAQ system information.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2013-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2013-01-02
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($user->perm->checkRight($user->getUserId(), 'editconfig')) {
    $faqSystem = new PMF_System();

    $esConfig = $faqConfig->getElasticsearchConfig();

    if ($faqConfig->get('search.enableElasticsearch')) {
        try {
            $esInformation = $faqConfig->getElasticsearch()->cat()->master([$esConfig['index']]);
        } catch (BadRequest400Exception $e) {
            $esInformation = $e->getMessage();
        } catch (NoNodesAvailableException $e) {
            $esInformation = $e->getMessage();
        }
    } else {
        $esInformation = 'n/a';
    }

    ?>
    <header class="row">
        <div class="col-lg-12">
            <h2 class="page-header"><i aria-hidden="true" class="fa fa-wrench fa-fw"></i> <?php echo $PMF_LANG['ad_system_info'] ?></h2>
        </div>
    </header>

    <div class="row">
        <div class="col-lg-12">
            <table class="table table-striped">
                <tbody>
                <?php
                $systemInformation = [
                    'phpMyFAQ Version' => $faqSystem->getVersion(),
                    'phpMyFAQ API Version' => $faqSystem->getApiVersion(),
                    'Server Software' => $_SERVER['SERVER_SOFTWARE'],
                    'Server Document Root' => $_SERVER['DOCUMENT_ROOT'],
                    'phpMyFAQ Installation Path' => dirname(dirname($_SERVER['SCRIPT_FILENAME'])),
                    'PHP Version' => PHP_VERSION,
                    'Webserver Interface' => strtoupper(PHP_SAPI),
                    'PHP Extensions' => implode(', ', get_loaded_extensions()),
                    'PHP Session path' => session_save_path(),
                    'Database Server' => PMF_Db::getType(),
                    'Database Server Version' => $faqConfig->getDb()->serverVersion(),
                    'Database Client Version' => $faqConfig->getDb()->clientVersion(),
                    'Elasticsearch' => $esInformation
                ];
                foreach ($systemInformation as $name => $info): ?>
                    <tr>
                        <td class="col-lg-2"><strong><?php echo $name ?></strong></td>
                        <td><?php echo $info ?></td>
                    </tr>
                <?php endforeach;
                ?>
                </tbody>
            </table>
        </div>
    </div>
<?php

} else {
    echo $PMF_LANG['err_NotAuth'];
}
