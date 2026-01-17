<?php

/**
 * The Admin Page API Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-15
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use DateTime;
use phpMyFAQ\CustomPage;
use phpMyFAQ\Entity\CustomPageEntity;
use phpMyFAQ\Enums\AdminLogType;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Instance\Search\Elasticsearch;
use phpMyFAQ\Instance\Search\OpenSearch;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PageController extends AbstractAdministrationApiController
{
    /**
     * Index a custom page in Elasticsearch and OpenSearch
     *
     * @param array<string, mixed> $pageData
     */
    private function indexCustomPage(array $pageData): void
    {
        // Index in Elasticsearch if enabled
        if ($this->configuration->get(item: 'search.enableElasticsearch')) {
            try {
                /** @var Elasticsearch $elasticsearch */
                $elasticsearch = $this->container->get(id: 'phpmyfaq.instance.elasticsearch');
                $elasticsearch->indexCustomPage($pageData);
            } catch (\Exception $e) {
                $this->configuration->getLogger()->error('Failed to index custom page in Elasticsearch', [
                    'error' => $e->getMessage(),
                    'page_id' => $pageData['id'] ?? null,
                ]);
            }
        }

        // Index in OpenSearch if enabled
        if ($this->configuration->get(item: 'search.enableOpenSearch')) {
            try {
                /** @var OpenSearch $openSearch */
                $openSearch = $this->container->get(id: 'phpmyfaq.instance.opensearch');
                $openSearch->indexCustomPage($pageData);
            } catch (\Exception $e) {
                $this->configuration->getLogger()->error('Failed to index custom page in OpenSearch', [
                    'error' => $e->getMessage(),
                    'page_id' => $pageData['id'] ?? null,
                ]);
            }
        }
    }

    /**
     * Update a custom page in Elasticsearch and OpenSearch
     *
     * @param array<string, mixed> $pageData
     */
    private function updateCustomPageIndex(array $pageData): void
    {
        // Update in Elasticsearch if enabled
        if ($this->configuration->get(item: 'search.enableElasticsearch')) {
            try {
                /** @var Elasticsearch $elasticsearch */
                $elasticsearch = $this->container->get(id: 'phpmyfaq.instance.elasticsearch');
                $elasticsearch->updateCustomPage($pageData);
            } catch (\Exception $e) {
                $this->configuration->getLogger()->error('Failed to update custom page in Elasticsearch', [
                    'error' => $e->getMessage(),
                    'page_id' => $pageData['id'] ?? null,
                ]);
            }
        }

        // Update in OpenSearch if enabled
        if ($this->configuration->get(item: 'search.enableOpenSearch')) {
            try {
                /** @var OpenSearch $openSearch */
                $openSearch = $this->container->get(id: 'phpmyfaq.instance.opensearch');
                $openSearch->updateCustomPage($pageData);
            } catch (\Exception $e) {
                $this->configuration->getLogger()->error('Failed to update custom page in OpenSearch', [
                    'error' => $e->getMessage(),
                    'page_id' => $pageData['id'] ?? null,
                ]);
            }
        }
    }

    /**
     * Delete a custom page from Elasticsearch and OpenSearch
     */
    private function deleteCustomPageFromIndex(int $pageId, string $lang): void
    {
        // Delete from Elasticsearch if enabled
        if ($this->configuration->get(item: 'search.enableElasticsearch')) {
            try {
                /** @var Elasticsearch $elasticsearch */
                $elasticsearch = $this->container->get(id: 'phpmyfaq.instance.elasticsearch');
                $elasticsearch->deleteCustomPage($pageId, $lang);
            } catch (\Exception $e) {
                $this->configuration->getLogger()->error('Failed to delete custom page from Elasticsearch', [
                    'error' => $e->getMessage(),
                    'page_id' => $pageId,
                ]);
            }
        }

        // Delete from OpenSearch if enabled
        if ($this->configuration->get(item: 'search.enableOpenSearch')) {
            try {
                /** @var OpenSearch $openSearch */
                $openSearch = $this->container->get(id: 'phpmyfaq.instance.opensearch');
                $openSearch->deleteCustomPage($pageId, $lang);
            } catch (\Exception $e) {
                $this->configuration->getLogger()->error('Failed to delete custom page from OpenSearch', [
                    'error' => $e->getMessage(),
                    'page_id' => $pageId,
                ]);
            }
        }
    }

    /**
     * @throws \Exception
     */
    #[Route(path: 'admin/api/page/create')]
    public function create(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::PAGE_ADD);

        $data = json_decode($request->getContent());

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON: ' . json_last_error_msg()], Response::HTTP_BAD_REQUEST);
        }

        $customPage = new CustomPage($this->configuration);

        if (!Token::getInstance($this->session)->verifyToken(page: 'save-page', requestToken: $data->csrfToken ?? '')) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        // Validate required fields
        $requiredFields = ['pageTitle', 'slug', 'authorName', 'authorEmail', 'lang'];
        foreach ($requiredFields as $field) {
            if (!isset($data->$field) || $data->$field === '') {
                return $this->json(['error' => "Missing required field: $field"], Response::HTTP_BAD_REQUEST);
            }
        }

        $pageTitle = Filter::filterVar($data->pageTitle, FILTER_SANITIZE_SPECIAL_CHARS);
        $slug = Filter::filterVar($data->slug, FILTER_SANITIZE_SPECIAL_CHARS);
        $content = Filter::filterVar($data->content ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        $authorName = Filter::filterVar($data->authorName, FILTER_SANITIZE_SPECIAL_CHARS);
        $authorEmail = Filter::filterVar($data->authorEmail, FILTER_VALIDATE_EMAIL);
        $active = Filter::filterVar($data->active ?? false, FILTER_SANITIZE_SPECIAL_CHARS);
        $language = Filter::filterVar($data->lang, FILTER_SANITIZE_SPECIAL_CHARS);
        $seoTitle = Filter::filterVar($data->seoTitle ?? null, FILTER_SANITIZE_SPECIAL_CHARS);
        $seoDescription = Filter::filterVar($data->seoDescription ?? null, FILTER_SANITIZE_SPECIAL_CHARS);
        $seoRobots = Filter::filterVar($data->seoRobots ?? 'index,follow', FILTER_SANITIZE_SPECIAL_CHARS);

        // Check if this is a translation (pageId provided)
        $isTranslation = isset($data->pageId) && $data->pageId > 0;
        $translationPageId = $isTranslation ? Filter::filterVar($data->pageId, FILTER_VALIDATE_INT) : null;

        // For translations, check if language already exists for this page ID
        if ($isTranslation) {
            $existingLanguages = $customPage->getExistingLanguages($translationPageId);
            if (in_array($language, $existingLanguages)) {
                return $this->json([
                    'error' => 'Translation for this language already exists',
                ], Response::HTTP_CONFLICT);
            }
        }

        // Check if slug exists
        if ($customPage->slugExists($slug, $language)) {
            return $this->json(['error' => Translation::get(key: 'ad_page_slug_exists')], Response::HTTP_CONFLICT);
        }

        $pageEntity = new CustomPageEntity();
        $pageEntity
            ->setLanguage($language)
            ->setPageTitle($pageTitle)
            ->setSlug($slug)
            ->setContent(Filter::removeAttributes(html_entity_decode(
                (string) $content,
                ENT_QUOTES | ENT_HTML5,
                encoding: 'UTF-8',
            )))
            ->setAuthorName($authorName)
            ->setAuthorEmail($authorEmail)
            ->setActive((bool) $active)
            ->setSeoTitle($seoTitle ?: null)
            ->setSeoDescription($seoDescription ?: null)
            ->setSeoRobots($seoRobots)
            ->setCreated(new DateTime());

        // Create translation or new page
        if ($isTranslation) {
            $success = $customPage->createTranslation($pageEntity, $translationPageId);
            $pageId = $success ? $translationPageId : 0;
        } else {
            $pageId = $customPage->create($pageEntity);
            $success = $pageId > 0;
        }

        if ($success) {
            $this->adminLog->log($this->currentUser, AdminLogType::PAGE_ADD->value);

            // Index in Elasticsearch/OpenSearch
            $this->indexCustomPage([
                'id' => $pageId,
                'lang' => $language,
                'page_title' => $pageTitle,
                'content' => $pageEntity->getContent(),
                'slug' => $slug,
                'active' => $active ? 'y' : 'n',
            ]);

            return $this->json([
                'success' => Translation::get(key: 'ad_page_updatesuc'),
                'id' => $pageId,
            ], Response::HTTP_OK);
        }

        return $this->json(['error' => Translation::get(key: 'ad_page_insertfail')], Response::HTTP_BAD_GATEWAY);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: 'admin/api/page/delete')]
    public function delete(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::PAGE_DELETE);

        $data = json_decode($request->getContent());

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON: ' . json_last_error_msg()], Response::HTTP_BAD_REQUEST);
        }

        $customPage = new CustomPage($this->configuration);

        if (!Token::getInstance($this->session)->verifyToken(
            page: 'delete-page',
            requestToken: $data->csrfToken ?? '',
        )) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        // Validate required fields
        if (!isset($data->id) || !isset($data->lang)) {
            return $this->json(['error' => 'Missing required fields: id, lang'], Response::HTTP_BAD_REQUEST);
        }

        $deleteId = Filter::filterVar($data->id, FILTER_VALIDATE_INT);
        $language = Filter::filterVar($data->lang, FILTER_SANITIZE_SPECIAL_CHARS);

        if ($customPage->delete((int) $deleteId, $language)) {
            $this->adminLog->log($this->currentUser, AdminLogType::PAGE_DELETE->value . ':' . $deleteId);

            // Delete from Elasticsearch/OpenSearch
            $this->deleteCustomPageFromIndex((int) $deleteId, $language);

            return $this->json(['success' => Translation::get(key: 'ad_page_delsuc')], Response::HTTP_OK);
        }

        return $this->json(['error' => Translation::get(key: 'ad_page_updatefail')], Response::HTTP_BAD_GATEWAY);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: 'admin/api/page/update')]
    public function update(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::PAGE_EDIT);

        $data = json_decode($request->getContent());

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON: ' . json_last_error_msg()], Response::HTTP_BAD_REQUEST);
        }

        $customPage = new CustomPage($this->configuration);

        if (!Token::getInstance($this->session)->verifyToken(
            page: 'update-page',
            requestToken: $data->csrfToken ?? '',
        )) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        // Validate required fields
        $requiredFields = ['id', 'pageTitle', 'slug', 'authorName', 'authorEmail', 'lang'];
        foreach ($requiredFields as $field) {
            if (!isset($data->$field) || $data->$field === '') {
                return $this->json(['error' => "Missing required field: $field"], Response::HTTP_BAD_REQUEST);
            }
        }

        $pageId = Filter::filterVar($data->id, FILTER_VALIDATE_INT);
        $pageTitle = Filter::filterVar($data->pageTitle, FILTER_SANITIZE_SPECIAL_CHARS);
        $slug = Filter::filterVar($data->slug, FILTER_SANITIZE_SPECIAL_CHARS);
        $content = Filter::filterVar($data->content ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        $authorName = Filter::filterVar($data->authorName, FILTER_SANITIZE_SPECIAL_CHARS);
        $authorEmail = Filter::filterVar($data->authorEmail, FILTER_VALIDATE_EMAIL);
        $active = Filter::filterVar($data->active ?? false, FILTER_SANITIZE_SPECIAL_CHARS);
        $language = Filter::filterVar($data->lang, FILTER_SANITIZE_SPECIAL_CHARS);
        $seoTitle = Filter::filterVar($data->seoTitle ?? null, FILTER_SANITIZE_SPECIAL_CHARS);
        $seoDescription = Filter::filterVar($data->seoDescription ?? null, FILTER_SANITIZE_SPECIAL_CHARS);
        $seoRobots = Filter::filterVar($data->seoRobots ?? 'index,follow', FILTER_SANITIZE_SPECIAL_CHARS);

        // Check if slug exists (excluding current page)
        if ($customPage->slugExists($slug, $language, $pageId)) {
            return $this->json(['error' => Translation::get(key: 'ad_page_slug_exists')], Response::HTTP_CONFLICT);
        }

        $pageEntity = new CustomPageEntity();
        $pageEntity
            ->setId($pageId)
            ->setLanguage($language)
            ->setPageTitle($pageTitle)
            ->setSlug($slug)
            ->setContent(Filter::removeAttributes(html_entity_decode(
                (string) $content,
                ENT_QUOTES | ENT_HTML5,
                encoding: 'UTF-8',
            )))
            ->setAuthorName($authorName)
            ->setAuthorEmail($authorEmail)
            ->setActive((bool) $active)
            ->setSeoTitle($seoTitle ?: null)
            ->setSeoDescription($seoDescription ?: null)
            ->setSeoRobots($seoRobots)
            ->setCreated(new DateTime())
            ->setUpdated(new DateTime());

        if ($customPage->update($pageEntity)) {
            $this->adminLog->log($this->currentUser, AdminLogType::PAGE_EDIT->value . ':' . $pageId);

            // Update in Elasticsearch/OpenSearch
            $this->updateCustomPageIndex([
                'id' => $pageId,
                'lang' => $language,
                'page_title' => $pageTitle,
                'content' => $pageEntity->getContent(),
                'slug' => $slug,
                'active' => $active ? 'y' : 'n',
            ]);

            return $this->json(['success' => Translation::get(key: 'ad_page_updatesuc')], Response::HTTP_OK);
        }

        return $this->json(['error' => Translation::get(key: 'ad_page_updatefail')], Response::HTTP_BAD_GATEWAY);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: 'admin/api/page/activate')]
    public function activate(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::PAGE_EDIT);
        $data = json_decode($request->getContent());

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON: ' . json_last_error_msg()], Response::HTTP_BAD_REQUEST);
        }

        $customPage = new CustomPage($this->configuration);

        if (!Token::getInstance($this->session)->verifyToken(
            page: 'activate-page',
            requestToken: $data->csrfToken ?? '',
        )) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        // Validate required fields
        if (!isset($data->id) || !isset($data->status)) {
            return $this->json(['error' => 'Missing required fields: id, status'], Response::HTTP_BAD_REQUEST);
        }

        $pageId = Filter::filterVar($data->id, FILTER_VALIDATE_INT);
        $status = (bool) Filter::filterVar($data->status, FILTER_SANITIZE_SPECIAL_CHARS);

        if ($customPage->activate($pageId, $status)) {
            $this->adminLog->log($this->currentUser, AdminLogType::PAGE_EDIT->value . ':' . $pageId);

            // Get page data for indexing
            $pageEntity = $customPage->getById($pageId);
            if ($pageEntity) {
                // Update in Elasticsearch/OpenSearch with new active status
                $this->updateCustomPageIndex([
                    'id' => $pageId,
                    'lang' => $pageEntity->getLanguage(),
                    'page_title' => $pageEntity->getPageTitle(),
                    'content' => $pageEntity->getContent(),
                    'slug' => $pageEntity->getSlug(),
                    'active' => $status ? 'y' : 'n',
                ]);
            }

            return $this->json(['success' => Translation::get(key: 'ad_page_updatesuc')], Response::HTTP_OK);
        }

        return $this->json(['error' => Translation::get(key: 'ad_page_updatefail')], Response::HTTP_BAD_GATEWAY);
    }

    /**
     * Check if a slug is available
     *
     * @throws \Exception
     */
    #[Route(path: 'admin/api/page/check-slug')]
    public function checkSlug(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::PAGE_ADD);

        $data = json_decode($request->getContent());

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON: ' . json_last_error_msg()], Response::HTTP_BAD_REQUEST);
        }

        $customPage = new CustomPage($this->configuration);

        if (!Token::getInstance($this->session)->verifyToken(page: 'save-page', requestToken: $data->csrfToken ?? '')) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        // Validate required fields
        if (!isset($data->slug) || !isset($data->lang)) {
            return $this->json(['error' => 'Missing required fields: slug, lang'], Response::HTTP_BAD_REQUEST);
        }

        $slug = Filter::filterVar($data->slug, FILTER_SANITIZE_SPECIAL_CHARS);
        $language = Filter::filterVar($data->lang, FILTER_SANITIZE_SPECIAL_CHARS);
        $excludeId = isset($data->excludeId) ? Filter::filterVar($data->excludeId, FILTER_VALIDATE_INT) : null;

        $exists = $customPage->slugExists($slug, $language, $excludeId);

        return $this->json([
            'available' => !$exists,
            'slug' => $slug,
        ], Response::HTTP_OK);
    }

    /**
     * Get paginated list of pages
     *
     * @throws \Exception
     */
    #[Route(path: 'admin/api/page/list')]
    public function list(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::PAGE_EDIT);

        $customPage = new CustomPage($this->configuration);

        $limit = Filter::filterVar($request->query->get('limit'), FILTER_VALIDATE_INT, 25);
        $offset = Filter::filterVar($request->query->get('offset'), FILTER_VALIDATE_INT, 0);
        $sortField = Filter::filterVar($request->query->get('sortField'), FILTER_SANITIZE_SPECIAL_CHARS, 'created');
        $sortOrder = Filter::filterVar($request->query->get('sortOrder'), FILTER_SANITIZE_SPECIAL_CHARS, 'DESC');
        $activeOnly = (bool) Filter::filterVar($request->query->get('activeOnly'), FILTER_VALIDATE_BOOLEAN, false);

        $pages = $customPage->getPagesPaginated(
            activeOnly: $activeOnly,
            limit: $limit,
            offset: $offset,
            sortField: $sortField,
            sortOrder: $sortOrder,
        );

        $total = $customPage->countPages(activeOnly: $activeOnly);

        return $this->json([
            'data' => $pages,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ], Response::HTTP_OK);
    }
}
