<?php

/**
 * Visits repository interface
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
 * @since     2025-11-23
 */

declare(strict_types=1);

namespace phpMyFAQ\Visits;

/**
 * Contract for Visits repositories.
 */
interface VisitsRepositoryInterface
{
    /**
     * Get the visit count for a FAQ record.
     */
    public function getVisitCount(int $faqId, string $language): int;

    /**
     * Check if a visit record exists for a FAQ record.
     */
    public function exists(int $faqId, string $language): bool;

    /**
     * Insert a new visit record.
     */
    public function insert(int $faqId, string $language, int $timestamp): bool;

    /**
     * Update an existing visit record.
     */
    public function update(int $faqId, string $language, int $timestamp): bool;

    /**
     * Get all visit data ordered by visits DESC.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAll(): array;

    /**
     * Reset all visits to 1 and update last_visit to the current timestamp.
     */
    public function resetAll(int $timestamp): bool;
}
