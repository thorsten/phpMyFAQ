<?php

/**
 * The CustomPage class for phpMyFAQ custom pages.
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
 * @since     2026-01-12
 */

declare(strict_types=1);

namespace phpMyFAQ;

use DateTime;
use phpMyFAQ\CustomPage\CustomPageRepository;
use phpMyFAQ\CustomPage\CustomPageRepositoryInterface;
use phpMyFAQ\Entity\CustomPageEntity;
use phpMyFAQ\Seo\SeoRepository;
use phpMyFAQ\Seo\SeoRepositoryInterface;
use stdClass;

/**
 * Class CustomPage
 *
 * @package phpMyFAQ
 */
readonly class CustomPage
{
    private CustomPageRepositoryInterface $repository;

    private SeoRepositoryInterface $seoRepository;

    /**
     * Constructor.
     */
    public function __construct(
        private Configuration $configuration,
        ?CustomPageRepositoryInterface $repository = null,
        ?SeoRepositoryInterface $seoRepository = null,
    ) {
        $this->repository = $repository ?? new CustomPageRepository($this->configuration);
        $this->seoRepository = $seoRepository ?? new SeoRepository($this->configuration);
    }

    /**
     * Get all custom pages for the current language.
     *
     * @param bool $activeOnly Filter by active status
     * @return array<int, array>
     */
    public function getAllPages(bool $activeOnly = false): array
    {
        $pages = [];
        $language = $this->configuration->getLanguage()->getLanguage();

        foreach ($this->repository->getAll($language, $activeOnly) as $row) {
            $pages[] = $this->mapRowToArray($row);
        }

        return $pages;
    }

    /**
     * Get paginated custom pages with sorting support.
     *
     * @param bool $activeOnly Filter by active status
     * @param int $limit Number of items per page
     * @param int $offset Starting offset
     * @param string $sortField Field to sort by
     * @param string $sortOrder Sort direction (ASC, DESC)
     * @return array
     */
    public function getPagesPaginated(
        bool $activeOnly = false,
        int $limit = 25,
        int $offset = 0,
        string $sortField = 'created',
        string $sortOrder = 'DESC',
    ): array {
        $pages = [];
        $language = $this->configuration->getLanguage()->getLanguage();

        foreach ($this->repository->getAllPaginated(
            $language,
            $activeOnly,
            $limit,
            $offset,
            $sortField,
            $sortOrder,
        ) as $row) {
            $pages[] = $this->mapRowToArray($row);
        }

        return $pages;
    }

    /**
     * Count total pages for the current language.
     *
     * @param bool $activeOnly Filter by active status
     * @return int Total count
     */
    public function countPages(bool $activeOnly = false): int
    {
        $language = $this->configuration->getLanguage()->getLanguage();
        return $this->repository->countAll($language, $activeOnly);
    }

    /**
     * Get a custom page by ID.
     *
     * @param int $pageId Page ID
     * @param string|null $language Language code (optional, uses current if not provided)
     * @return array|null Page data or null if not found
     */
    public function getById(int $pageId, ?string $language = null): ?array
    {
        $language = $language ?? $this->configuration->getLanguage()->getLanguage();
        $row = $this->repository->getById($pageId, $language);

        return $row ? $this->mapRowToArray($row) : null;
    }

    /**
     * Get a custom page by slug.
     *
     * @param string $slug URL slug
     * @param string|null $language Language code (optional, uses current if not provided)
     * @return array|null Page data or null if not found
     */
    public function getBySlug(string $slug, ?string $language = null): ?array
    {
        $language = $language ?? $this->configuration->getLanguage()->getLanguage();
        $row = $this->repository->getBySlug($slug, $language);

        return $row ? $this->mapRowToArray($row) : null;
    }

    /**
     * Create a new custom page.
     *
     * @param CustomPageEntity $page Custom page entity
     * @return int The new page ID
     */
    public function create(CustomPageEntity $page): int
    {
        return $this->repository->insert($page);
    }

    /**
     * Update an existing custom page.
     *
     * @param CustomPageEntity $page Custom page entity
     * @return bool Success status
     */
    public function update(CustomPageEntity $page): bool
    {
        if (!$page->getUpdated()) {
            $page->setUpdated(new DateTime());
        }
        return $this->repository->update($page);
    }

    /**
     * Delete a custom page.
     *
     * @param int $pageId Page ID
     * @param string|null $language Language code (optional, uses current if not provided)
     * @return bool Success status
     */
    public function delete(int $pageId, ?string $language = null): bool
    {
        $language = $language ?? $this->configuration->getLanguage()->getLanguage();
        return $this->repository->delete($pageId, $language);
    }

    /**
     * Activate or deactivate a custom page.
     *
     * @param int $pageId Page ID
     * @param bool $status Active status
     * @return bool Success status
     */
    public function activate(int $pageId, bool $status): bool
    {
        return $this->repository->activate($pageId, $status);
    }

    /**
     * Check if a slug exists.
     *
     * @param string $slug URL slug
     * @param string|null $language Language code (optional, uses current if not provided)
     * @param int|null $excludeId Optional page ID to exclude from check
     * @return bool True if slug exists
     */
    public function slugExists(string $slug, ?string $language = null, ?int $excludeId = null): bool
    {
        $language = $language ?? $this->configuration->getLanguage()->getLanguage();
        return $this->repository->slugExists($slug, $language, $excludeId);
    }

    /**
     * Generate a unique slug from a base string.
     *
     * @param string $baseSlug Base slug string
     * @param string|null $language Language code (optional, uses current if not provided)
     * @param int|null $excludeId Optional page ID to exclude from check
     * @return string Unique slug
     */
    public function generateUniqueSlug(string $baseSlug, ?string $language = null, ?int $excludeId = null): string
    {
        $language = $language ?? $this->configuration->getLanguage()->getLanguage();
        $slug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($slug, $language, $excludeId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Map database row to array.
     *
     * @param stdClass $row Database row
     * @return array Mapped data
     */
    private function mapRowToArray(stdClass $row): array
    {
        return [
            'id' => (int) $row->id,
            'lang' => $row->lang,
            'page_title' => $row->page_title,
            'slug' => $row->slug,
            'content' => $row->content,
            'author_name' => $row->author_name,
            'author_email' => $row->author_email,
            'active' => $row->active,
            'created' => $row->created,
            'updated' => $row->updated ?? null,
            'seo_title' => $row->seo_title ?? null,
            'seo_description' => $row->seo_description ?? null,
            'seo_robots' => $row->seo_robots ?? 'index,follow',
        ];
    }
}
