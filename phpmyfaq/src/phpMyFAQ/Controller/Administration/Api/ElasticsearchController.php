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

namespace phpMyFAQ\Controller\Administration\Api;

use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
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
    /**
     * @throws Exception
     */
    #[Route('./admin/api/elasticsearch/create')]
    public function create(): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $elasticsearch = new Elasticsearch($this->configuration);

        try {
            $elasticsearch->createIndex();
            return $this->json(
                ['success' => Translation::get('msgAdminElasticsearchCreateIndex_success')],
                Response::HTTP_OK
            );
        } catch (Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_CONFLICT);
        }
    }

    /**
     * @throws Exception
     */
    #[Route('./admin/api/elasticsearch/drop')]
    public function drop(): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $elasticsearch = new Elasticsearch($this->configuration);

        try {
            $elasticsearch->dropIndex();
            return $this->json(
                ['success' => Translation::get('msgAdminElasticsearchDropIndex_success')],
                Response::HTTP_OK
            );
        } catch (Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_CONFLICT);
        }
    }

    /**
     * @throws Exception
     */
    #[Route('./admin/api/elasticsearch/import')]
    public function import(): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $elasticsearch = new Elasticsearch($this->configuration);
        $faq = new Faq($this->configuration);
        $faq->getAllFaqs();

        $bulkIndexResult = $elasticsearch->bulkIndex($faq->faqRecords);
        if (isset($bulkIndexResult['success'])) {
            return $this->json(['success' => Translation::get('ad_es_create_import_success')], Response::HTTP_OK);
        } else {
            return $this->json(['error' => $bulkIndexResult], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws Exception
     */
    #[Route('./admin/api/elasticsearch/statistics')]
    public function statistics(): JsonResponse
    {
        $this->userIsAuthenticated();

        $elasticsearchConfiguration = $this->configuration->getElasticsearchConfig();

        $indexName = $elasticsearchConfiguration->getIndex();
        try {
            return $this->json(
                [
                    'index' => $indexName,
                    'stats' => $this->configuration
                        ->getElasticsearch()
                        ->indices()
                        ->stats(['index' => $indexName])
                        ->asArray()
                ],
                Response::HTTP_OK
            );
        } catch (ClientResponseException | ServerResponseException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
