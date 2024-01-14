<?php

/**
 * The Admin Elasticsearch Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-26
 */

namespace phpMyFAQ\Controller\Administration;

use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Faq;
use phpMyFAQ\Instance\Elasticsearch;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ElasticsearchController extends AbstractController
{
    #[Route('./admin/api/elasticsearch/create')]
    public function create(): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT->value);

        $response = new JsonResponse();
        $elasticsearch = new Elasticsearch(Configuration::getConfigurationInstance());

        try {
            $elasticsearch->createIndex();
            $response->setStatusCode(Response::HTTP_OK);
            $response->setData(['success' => Translation::get('ad_es_create_index_success')]);
        } catch (Exception $e) {
            $response->setStatusCode(Response::HTTP_CONFLICT);
            $response->setData(['error' => $e->getMessage()]);
        }

        return $response;
    }

    #[Route('./admin/api/elasticsearch/drop')]
    public function drop(): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT->value);

        $response = new JsonResponse();
        $elasticsearch = new Elasticsearch(Configuration::getConfigurationInstance());

        try {
            $elasticsearch->dropIndex();
            $response->setStatusCode(Response::HTTP_OK);
            $response->setData(['success' => Translation::get('ad_es_drop_index_success')]);
        } catch (Exception $e) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => $e->getMessage()]);
        }

        return $response;
    }

    #[Route('./admin/api/elasticsearch/import')]
    public function import(): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT->value);

        $response = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();

        $elasticsearch = new Elasticsearch($configuration);
        $faq = new Faq($configuration);
        $faq->getAllRecords();

        $bulkIndexResult = $elasticsearch->bulkIndex($faq->faqRecords);
        if (isset($bulkIndexResult['success'])) {
            $response->setStatusCode(Response::HTTP_OK);
            $response->setData(['success' => Translation::get('ad_es_create_import_success')]);
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => $bulkIndexResult]);
        }

        return $response;
    }

    #[Route('./admin/api/elasticsearch/statistics')]
    public function statistics(): JsonResponse
    {
        $this->userIsAuthenticated();

        $response = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();

        $elasticsearchConfiguration = $configuration->getElasticsearchConfig();

        $indexName = $elasticsearchConfiguration->getIndex();
        try {
            $response->setStatusCode(Response::HTTP_OK);
            $response->setData(
                [
                    'index' => $indexName,
                    'stats' => $configuration->getElasticsearch()->indices()->stats(['index' => $indexName])->asArray()
                ]
            );
        } catch (ClientResponseException | ServerResponseException $e) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => $e->getMessage()]);
        }

        return $response;
    }
}
