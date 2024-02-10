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
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $jsonResponse = new JsonResponse();
        $elasticsearch = new Elasticsearch(Configuration::getConfigurationInstance());

        try {
            $elasticsearch->createIndex();
            $jsonResponse->setStatusCode(Response::HTTP_OK);
            $jsonResponse->setData(['success' => Translation::get('ad_es_create_index_success')]);
        } catch (Exception $exception) {
            $jsonResponse->setStatusCode(Response::HTTP_CONFLICT);
            $jsonResponse->setData(['error' => $exception->getMessage()]);
        }

        return $jsonResponse;
    }

    #[Route('./admin/api/elasticsearch/drop')]
    public function drop(): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $jsonResponse = new JsonResponse();
        $elasticsearch = new Elasticsearch(Configuration::getConfigurationInstance());

        try {
            $elasticsearch->dropIndex();
            $jsonResponse->setStatusCode(Response::HTTP_OK);
            $jsonResponse->setData(['success' => Translation::get('ad_es_drop_index_success')]);
        } catch (Exception $exception) {
            $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
            $jsonResponse->setData(['error' => $exception->getMessage()]);
        }

        return $jsonResponse;
    }

    #[Route('./admin/api/elasticsearch/import')]
    public function import(): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $jsonResponse = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();

        $elasticsearch = new Elasticsearch($configuration);
        $faq = new Faq($configuration);
        $faq->getAllRecords();

        $bulkIndexResult = $elasticsearch->bulkIndex($faq->faqRecords);
        if (isset($bulkIndexResult['success'])) {
            $jsonResponse->setStatusCode(Response::HTTP_OK);
            $jsonResponse->setData(['success' => Translation::get('ad_es_create_import_success')]);
        } else {
            $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
            $jsonResponse->setData(['error' => $bulkIndexResult]);
        }

        return $jsonResponse;
    }

    #[Route('./admin/api/elasticsearch/statistics')]
    public function statistics(): JsonResponse
    {
        $this->userIsAuthenticated();

        $jsonResponse = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();

        $elasticsearchConfiguration = $configuration->getElasticsearchConfig();

        $indexName = $elasticsearchConfiguration->getIndex();
        try {
            $jsonResponse->setStatusCode(Response::HTTP_OK);
            $jsonResponse->setData(
                [
                    'index' => $indexName,
                    'stats' => $configuration->getElasticsearch()->indices()->stats(['index' => $indexName])->asArray()
                ]
            );
        } catch (ClientResponseException | ServerResponseException $e) {
            $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
            $jsonResponse->setData(['error' => $e->getMessage()]);
        }

        return $jsonResponse;
    }
}
