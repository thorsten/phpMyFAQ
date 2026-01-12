<?php

/**
 * Custom Page repository class
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

namespace phpMyFAQ\CustomPage;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Entity\CustomPageEntity;
use stdClass;

/**
 * Repository for faqcustompages table access.
 */
final readonly class CustomPageRepository implements CustomPageRepositoryInterface
{
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    /**
     * Fetch all custom pages for a language, optionally filtered by active status.
     *
     * @return iterable<stdClass>
     */
    public function getAll(string $language, bool $activeOnly = false): iterable
    {
        $whereActive = $activeOnly ? "AND active = 'y'" : '';
        $query = sprintf(
            "SELECT * FROM %sfaqcustompages WHERE lang = '%s' %s ORDER BY created DESC",
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($language),
            $whereActive,
        );

        $result = $this->configuration->getDb()->query($query);
        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            yield $row;
        }
    }

    /**
     * Fetch paginated custom pages for a language with sorting support.
     *
     * @param string $language Language code
     * @param bool $activeOnly Filter by active status
     * @param int $limit Number of items per page
     * @param int $offset Starting offset
     * @param string $sortField Field to sort by (id, page_title, slug, created, updated)
     * @param string $sortOrder Sort direction (ASC, DESC)
     * @return iterable<stdClass>
     */
    public function getAllPaginated(
        string $language,
        bool $activeOnly,
        int $limit,
        int $offset,
        string $sortField,
        string $sortOrder,
    ): iterable {
        // Whitelist validation for the sort field
        $allowedSortFields = ['id', 'page_title', 'slug', 'created', 'updated', 'active'];
        if (!in_array($sortField, $allowedSortFields, strict: true)) {
            $sortField = 'created';
        }

        // Validate sort order
        $sortOrder = strtoupper($sortOrder);
        if (!in_array($sortOrder, ['ASC', 'DESC'], strict: true)) {
            $sortOrder = 'DESC';
        }

        $whereActive = $activeOnly ? "AND active = 'y'" : '';
        $query = sprintf(
            "SELECT * FROM %sfaqcustompages WHERE lang = '%s' %s ORDER BY %s %s LIMIT %d OFFSET %d",
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($language),
            $whereActive,
            $sortField,
            $sortOrder,
            $limit,
            $offset,
        );

        $result = $this->configuration->getDb()->query($query);
        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            yield $row;
        }
    }

    /**
     * Count total custom pages for a language.
     *
     * @param string $language Language code
     * @param bool $activeOnly Filter by active status
     * @return int Total count
     */
    public function countAll(string $language, bool $activeOnly = false): int
    {
        $whereActive = $activeOnly ? "AND active = 'y'" : '';
        $query = sprintf(
            "SELECT COUNT(*) as total FROM %sfaqcustompages WHERE lang = '%s' %s",
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($language),
            $whereActive,
        );

        $result = $this->configuration->getDb()->query($query);
        $row = $this->configuration->getDb()->fetchObject($result);

        return (int) ($row->total ?? 0);
    }

    /**
     * Fetch a custom page by ID and language.
     *
     * @param int $pageId Page ID
     * @param string $language Language code
     * @return stdClass|null Page data or null if not found
     */
    public function getById(int $pageId, string $language): ?stdClass
    {
        $query = sprintf(
            "SELECT * FROM %sfaqcustompages WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $pageId,
            $this->configuration->getDb()->escape($language),
        );
        $result = $this->configuration->getDb()->query($query);
        $row = $this->configuration->getDb()->fetchObject($result);
        return $row ?: null;
    }

    /**
     * Fetch a custom page by slug and language.
     *
     * @param string $slug URL slug
     * @param string $language Language code
     * @return stdClass|null Page data or null if not found
     */
    public function getBySlug(string $slug, string $language): ?stdClass
    {
        $query = sprintf(
            "SELECT * FROM %sfaqcustompages WHERE slug = '%s' AND lang = '%s'",
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($slug),
            $this->configuration->getDb()->escape($language),
        );
        $result = $this->configuration->getDb()->query($query);
        $row = $this->configuration->getDb()->fetchObject($result);
        return $row ?: null;
    }

    /**
     * Insert a new custom page.
     *
     * @param CustomPageEntity $page Custom page entity
     * @return int The new page ID
     */
    public function insert(CustomPageEntity $page): int
    {
        $id = $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqcustompages', column: 'id');
        $query = sprintf(
            "
            INSERT INTO %sfaqcustompages
            (id, lang, page_title, slug, content, author_name, author_email, active, created, updated)
            VALUES
            (%d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %s)",
            Database::getTablePrefix(),
            $id,
            $this->configuration->getDb()->escape($page->getLanguage()),
            $this->configuration->getDb()->escape($page->getPageTitle()),
            $this->configuration->getDb()->escape($page->getSlug()),
            $this->configuration->getDb()->escape($page->getContent()),
            $this->configuration->getDb()->escape($page->getAuthorName()),
            $this->configuration->getDb()->escape($page->getAuthorEmail()),
            $page->isActive() ? 'y' : 'n',
            $page->getCreated()->format(format: 'Y-m-d H:i:s'),
            'NULL',
        );
        $this->configuration->getDb()->query($query);
        $page->setId($id);
        return $id;
    }

    /**
     * Update an existing custom page.
     *
     * @param CustomPageEntity $page Custom page entity
     * @return bool Success status
     */
    public function update(CustomPageEntity $page): bool
    {
        $query = sprintf(
            "
            UPDATE %sfaqcustompages SET
                page_title = '%s',
                slug = '%s',
                content = '%s',
                author_name = '%s',
                author_email = '%s',
                active = '%s',
                updated = '%s'
            WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($page->getPageTitle()),
            $this->configuration->getDb()->escape($page->getSlug()),
            $this->configuration->getDb()->escape($page->getContent()),
            $this->configuration->getDb()->escape($page->getAuthorName()),
            $this->configuration->getDb()->escape($page->getAuthorEmail()),
            $page->isActive() ? 'y' : 'n',
            $page->getUpdated()?->format(format: 'Y-m-d H:i:s') ?? date('Y-m-d H:i:s'),
            $page->getId(),
            $this->configuration->getDb()->escape($page->getLanguage()),
        );
        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Delete a custom page.
     *
     * @param int $pageId Page ID
     * @param string $language Language code
     * @return bool Success status
     */
    public function delete(int $pageId, string $language): bool
    {
        $query = sprintf(
            "DELETE FROM %sfaqcustompages WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $pageId,
            $this->configuration->getDb()->escape($language),
        );
        return (bool) $this->configuration->getDb()->query($query);
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
        $query = sprintf(
            "UPDATE %sfaqcustompages SET active = '%s' WHERE id = %d",
            Database::getTablePrefix(),
            $status ? 'y' : 'n',
            $pageId,
        );
        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Check if a slug already exists for a language.
     *
     * @param string $slug URL slug
     * @param string $language Language code
     * @param int|null $excludeId Optional page ID to exclude from check (for updates)
     * @return bool True if slug exists
     */
    public function slugExists(string $slug, string $language, ?int $excludeId = null): bool
    {
        $excludeCondition = $excludeId !== null ? sprintf('AND id != %d', $excludeId) : '';
        $query = sprintf(
            "SELECT COUNT(*) as total FROM %sfaqcustompages WHERE slug = '%s' AND lang = '%s' %s",
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($slug),
            $this->configuration->getDb()->escape($language),
            $excludeCondition,
        );

        $result = $this->configuration->getDb()->query($query);
        $row = $this->configuration->getDb()->fetchObject($result);

        return (int) ($row->total ?? 0) > 0;
    }
}
