<?php

/**
 * Elasticsearch configuration backend
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2015-12-26
 */

use Elasticsearch\Common\Exceptions\Missing404Exception;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Instance\Elasticsearch;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$ajaxAction = Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);

$esInstance = new Elasticsearch($faqConfig);

$http = new HttpHelper();
$http->setContentType('application/json');
$http->addHeader();

$result = [];

switch ($ajaxAction) {
    case 'create':
        if ($esInstance->createIndex()) {
            $result = ['success' => $PMF_LANG['ad_es_create_index_success']];
        }
        break;

    case 'drop':
        if ($esInstance->dropIndex()) {
            $result = ['success' => $PMF_LANG['ad_es_drop_index_success']];
        }
        break;

    case 'import':
        $faq = new Faq($faqConfig);
        $faq->getAllRecords();
        $bulkIndexResult = $esInstance->bulkIndex($faq->faqRecords);
        if ($bulkIndexResult['success']) {
            $result = ['success' => $PMF_LANG['ad_es_create_import_success']];
        }
        break;

    case 'stats':
        try {
            $result = $faqConfig->getElasticsearch()->indices()->stats(['index' => 'phpmyfaq']);
        } catch (Missing404Exception $e) {
            $result = $e->getMessage();
        }
        break;
}

$http->sendJsonWithHeaders($result);
