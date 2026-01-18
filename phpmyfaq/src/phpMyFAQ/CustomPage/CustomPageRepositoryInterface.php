<?php

/**
 * Custom Page repository interface
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

use phpMyFAQ\Entity\CustomPageEntity;
use stdClass;

/**
 * Contract for CustomPage repositories.
 */
interface CustomPageRepositoryInterface
{
    /**
     * @return iterable<stdClass>
     */
    public function getAll(string $language, bool $activeOnly = false): iterable;

    /**
     * @return iterable<stdClass>
     */
    public function getAllPaginated(
        string $language,
        bool $activeOnly,
        int $limit,
        int $offset,
        string $sortField,
        string $sortOrder,
    ): iterable;

    /**
     * Get all pages across all languages with pagination
     * @return iterable<stdClass>
     */
    public function getAllLanguagesPaginated(
        bool $activeOnly,
        int $limit,
        int $offset,
        string $sortField,
        string $sortOrder,
    ): iterable;

    public function countAll(string $language, bool $activeOnly = false): int;

    public function countAllLanguages(bool $activeOnly = false): int;

    public function getById(int $pageId, string $language): ?stdClass;

    public function getBySlug(string $slug, string $language): ?stdClass;

    /**
     * @return array<string>
     */
    public function getExistingLanguages(int $pageId): array;

    public function insert(CustomPageEntity $page): int;

    public function insertTranslation(CustomPageEntity $page, int $pageId): bool;

    public function update(CustomPageEntity $page): bool;

    public function delete(int $pageId, string $language): bool;

    public function activate(int $pageId, bool $status): bool;

    public function slugExists(string $slug, string $language, ?int $excludeId = null): bool;
}
