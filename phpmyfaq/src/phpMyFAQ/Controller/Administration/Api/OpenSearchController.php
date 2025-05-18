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
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-05-12
 */

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Instance\OpenSearch;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OpenSearchController extends AbstractController
{
    /**
     * @throws \Exception
     */
    #[Route('./admin/api/opensearch/create', name: 'admin.api.opensearch.create', methods: ['POST'])]
    public function create(): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $openSearch = new OpenSearch($this->configuration);

        try {
            $openSearch->createIndex();
            return $this->json(
                ['success' => Translation::get('msgAdminOpenSearchCreateIndex_success')],
                Response::HTTP_OK
            );
        } catch (Exception | \Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_CONFLICT);
        }
    }

    /**
     * @throws \Exception
     */
    #[Route('./admin/api/opensearch/drop', name: 'admin.api.opensearch.drop', methods: ['DELETE'])]
    public function drop(): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $openSearch = new OpenSearch($this->configuration);

        try {
            $openSearch->dropIndex();
            return $this->json(
                ['success' => Translation::get('msgAdminOpenSearchDropIndex_success')],
                Response::HTTP_OK
            );
        } catch (Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_CONFLICT);
        }
    }

    /**
     * @throws \Exception
     */
    #[Route('./admin/api/opensearch/import', name: 'admin.api.opensearch.import', methods: ['POST'])]
    public function import(): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $openSearch = new OpenSearch($this->configuration);
        $faq = $this->container->get('phpmyfaq.faq');
        $faq->getAllFaqs();

        $bulkIndexResult = $openSearch->bulkIndex($faq->faqRecords);
        if (isset($bulkIndexResult['success'])) {
            return $this->json(['success' => Translation::get('ad_os_create_import_success')], Response::HTTP_OK);
        }

        return $this->json(['error' => $bulkIndexResult], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Route('./admin/api/opensearch/statistics', name: 'admin.api.opensearch.statistics', methods: ['GET'])]
    public function statistics(): JsonResponse
    {
        $this->userIsAuthenticated();

        $openSearchConfiguration = $this->configuration->getOpenSearchConfig();

        $indexName = $openSearchConfiguration->getIndex();

        return $this->json(
            [
                'index' => $indexName,
                'stats' => $this->configuration
                    ->getOpenSearch()
                    ->indices()
                    ->stats(['index' => $indexName])
            ],
            Response::HTTP_OK
        );
    }
}
