<?php

/**
 * Elasticsearch configuration backend
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2015-12-26
 */

use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Instance\Elasticsearch;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$ajaxAction = Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);
$esInstance = new Elasticsearch($faqConfig);
$result = [];

switch ($ajaxAction) {

    case 'create':

        try {
            if ($esInstance->createIndex()) {
                $result = ['success' => $PMF_LANG['ad_es_create_index_success']];
            }
        } catch (BadRequest400Exception $e) {
            $result = ['error' => $e->getMessage()];
        }

        break;

    case 'drop':

        try {
            if ($esInstance->dropIndex()) {
                $result = ['success' => $PMF_LANG['ad_es_drop_index_success']];
            }
        } catch (Missing404Exception $e) {
            $result = ['error' => $e->getMessage()];
        }
        break;

    case 'import':

        $faq = new Faq($faqConfig);
        $faq->getAllRecords();
        $result = $esInstance->bulkIndex($faq->faqRecords);

        break;
}

echo json_encode($result);
