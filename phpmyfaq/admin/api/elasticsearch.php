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
 * @copyright 2015-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-12-26
 */

use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Instance\Elasticsearch;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

//
// Create Request & Response
//
$response = new JsonResponse();
$request = Request::createFromGlobals();

$ajaxAction = Filter::filterVar($request->query->get('ajaxaction'), FILTER_SANITIZE_SPECIAL_CHARS);

$elasticsearch = new Elasticsearch($faqConfig);

$esConfigData = $faqConfig->getElasticsearchConfig();

$result = [];

switch ($ajaxAction) {
    case 'create':
        try {
            $esResult = $elasticsearch->createIndex();
            $response->setStatusCode(Response::HTTP_OK);
            $result = ['success' => Translation::get('ad_es_create_index_success')];
        } catch (Exception $e) {
            $response->setStatusCode(Response::HTTP_CONFLICT);
            $result = ['error' => $e->getMessage()];
        }
        break;

    case 'drop':
        try {
            $esResult = $elasticsearch->dropIndex();
            $response->setStatusCode(Response::HTTP_OK);
            $result = ['success' => Translation::get('ad_es_drop_index_success')];
        } catch (Exception $e) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $result = ['error' => $e->getMessage()];
        }
        break;

    case 'import':
        $faq = new Faq($faqConfig);
        $faq->getAllRecords();
        $bulkIndexResult = $elasticsearch->bulkIndex($faq->faqRecords);
        if (isset($bulkIndexResult['success'])) {
            $response->setStatusCode(Response::HTTP_OK);
            $result = ['success' => Translation::get('ad_es_create_import_success')];
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $result = ['error' => $bulkIndexResult];
        }
        break;

    case 'stats':
        $indexName = $esConfigData->getIndex();
        try {
            $response->setStatusCode(Response::HTTP_OK);
            $result = [
                'index' => $indexName,
                'stats' => $faqConfig->getElasticsearch()->indices()->stats(['index' => $indexName])->asArray()
            ];
        } catch (ClientResponseException | ServerResponseException $e) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $result = ['error' => $e->getMessage()];
        }
        break;
}

$response->setData($result);
$response->send();
