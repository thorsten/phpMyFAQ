<?php

/**
 * Private phpMyFAQ Admin API: Elasticsearch configuration backend
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2022 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-12-26
 */

use Elasticsearch\Common\Exceptions\Missing404Exception;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Instance\Elasticsearch;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$ajaxAction = Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_UNSAFE_RAW);

$elasticsearch = new Elasticsearch($faqConfig);

$esConfigData = $faqConfig->getElasticsearchConfig();

$http = new HttpHelper();
$http->setContentType('application/json');
$http->addHeader();

$result = [];

switch ($ajaxAction) {
    case 'create':
        if ($elasticsearch->createIndex()) {
            $result = ['success' => Translation::get('ad_es_create_index_success')];
        }
        break;

    case 'drop':
        if ($elasticsearch->dropIndex()) {
            $result = ['success' => Translation::get('ad_es_drop_index_success')];
        }
        break;

    case 'import':
        $faq = new Faq($faqConfig);
        $faq->getAllRecords();
        $bulkIndexResult = $elasticsearch->bulkIndex($faq->faqRecords);
        if ($bulkIndexResult['success']) {
            $result = ['success' => Translation::get('ad_es_create_import_success')];
        }
        break;

    case 'stats':
        $result = $faqConfig->getElasticsearch()->indices()->stats(['index' => $esConfigData['index']]);
        break;
}

$http->setStatus(200);
$http->sendJsonWithHeaders($result);
