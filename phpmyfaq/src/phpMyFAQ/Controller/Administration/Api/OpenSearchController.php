<?php

/**
 * The Admin OpenSearch Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-05-12
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\CustomPage;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Instance\Search\OpenSearch;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OpenSearchController extends AbstractController
{
    /**
     * @throws \Exception
     */
    #[Route(path: './admin/api/opensearch/create', name: 'admin.api.opensearch.create', methods: ['POST'])]
    public function create(): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        /* @var OpenSearch $openSearch */
        $openSearch = $this->container->get(id: 'phpmyfaq.instance.opensearch');

        try {
            $openSearch->createIndex();
            return $this->json(['success' => Translation::get(
                'msgAdminOpenSearchCreateIndex_success',
            )], Response::HTTP_OK);
        } catch (Exception|\Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_CONFLICT);
        }
    }

    /**
     * @throws \Exception
     */
    #[Route(path: './admin/api/opensearch/drop', name: 'admin.api.opensearch.drop', methods: ['DELETE'])]
    public function drop(): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        /* @var OpenSearch $openSearch */
        $openSearch = $this->container->get(id: 'phpmyfaq.instance.opensearch');

        try {
            $openSearch->dropIndex();
            return $this->json(['success' => Translation::get(
                'msgAdminOpenSearchDropIndex_success',
            )], Response::HTTP_OK);
        } catch (Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_CONFLICT);
        }
    }

    /**
     * @throws \Exception
     */
    #[Route(path: './admin/api/opensearch/import', name: 'admin.api.opensearch.import', methods: ['POST'])]
    public function import(): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        /* @var OpenSearch $openSearch */
        $openSearch = $this->container->get(id: 'phpmyfaq.instance.opensearch');

        $faq = $this->container->get(id: 'phpmyfaq.faq');
        $faq->getAllFaqs();

        // Index FAQs
        $bulkIndexResult = $openSearch->bulkIndex($faq->faqRecords);
        if (!isset($bulkIndexResult['success'])) {
            return $this->json(['error' => $bulkIndexResult], Response::HTTP_BAD_REQUEST);
        }

        // Index custom pages
        /** @var CustomPage $customPage */
        $customPage = $this->container->get(id: 'phpmyfaq.custom-page');
        $pages = $customPage->getAllPages();

        $bulkIndexPagesResult = $openSearch->bulkIndexCustomPages($pages);
        if (!isset($bulkIndexPagesResult['success'])) {
            return $this->json([
                'error' => 'FAQs indexed but custom pages failed: ' . json_encode($bulkIndexPagesResult),
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['success' => Translation::get(key: 'ad_os_create_import_success')], Response::HTTP_OK);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: './admin/api/opensearch/statistics', name: 'admin.api.opensearch.statistics', methods: ['GET'])]
    public function statistics(): JsonResponse
    {
        $this->userIsAuthenticated();

        $openSearchConfiguration = $this->configuration->getOpenSearchConfig();

        $indexName = $openSearchConfiguration->getIndex();

        return $this->json([
            'index' => $indexName,
            'stats' => $this->configuration
                ->getOpenSearch()
                ->indices()
                ->stats(['index' => $indexName]),
        ], Response::HTTP_OK);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: './admin/api/opensearch/healthcheck', name: 'admin.api.opensearch.healthcheck', methods: ['GET'])]
    public function healthcheck(): JsonResponse
    {
        $this->userIsAuthenticated();

        /* @var OpenSearch $openSearch */
        $openSearch = $this->container->get(id: 'phpmyfaq.instance.opensearch');

        $isAvailable = $openSearch->isAvailable();

        return $this->json(
            [
                'available' => $isAvailable,
                'status' => $isAvailable ? 'healthy' : 'unavailable',
            ],
            $isAvailable ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE,
        );
    }
}
