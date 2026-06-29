<?php

/**
 * FAQ repository interface for phpMyFAQ.
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
 * @since     2026-06-29
 */

declare(strict_types=1);

namespace phpMyFAQ\Faq;

interface FaqRepositoryInterface
{
    /**
     * Returns the next available solution id.
     */
    public function getNextSolutionId(): int;

    /**
     * Returns the solution id for a given FAQ id and language, or the next available
     * solution id when the FAQ does not exist yet.
     */
    public function getSolutionIdFromId(int $faqId, string $faqLang): int;

    /**
     * Checks whether a FAQ already exists in the given language.
     */
    public function hasTranslation(int $faqId, string $faqLang): bool;

    /**
     * Checks whether a FAQ (or news) record is active.
     */
    public function isActive(int $faqId, string $faqLang, string $commentType = 'faq'): bool;

    /**
     * Resolves a solution id to the FAQ id, language, question, content and category id,
     * honouring the given user and group permissions. Returns an empty array when nothing
     * matches.
     *
     * @param int[] $groups
     * @return array<string, mixed>
     */
    public function getIdFromSolutionId(int $solutionId, int $userId, array $groups, bool $groupSupport): array;

    /**
     * Fetches the raw question (thema) of a FAQ in the given language, or null when the
     * FAQ does not exist.
     */
    public function fetchQuestion(int $faqId, string $language): ?string;

    /**
     * Fetches the raw, unescaped keywords of a FAQ in the given language, or null when the
     * FAQ does not exist.
     */
    public function fetchKeywords(int $faqId, string $language): ?string;

    /**
     * Runs the permission-filtered query for a single FAQ (or FAQ revision) and returns the
     * raw database result handle. Admin callers bypass the permission filter.
     *
     * @param int[] $groups
     */
    public function getFaqResult(
        int $faqId,
        string $faqLanguage,
        ?int $faqRevisionId,
        bool $isAdmin,
        int $userId,
        array $groups,
        bool $groupSupport,
    ): mixed;

    /**
     * Fetches a single FAQ scoped to a category, honouring user and group permissions, in the
     * configuration's current language. Returns the raw row, or null when nothing matches.
     *
     * @param int[] $groups
     */
    public function fetchFaqByIdAndCategoryId(
        int $faqId,
        int $categoryId,
        bool $onlyActive,
        int $userId,
        array $groups,
        bool $groupSupport,
    ): ?object;

    /**
     * Resolves a solution id to its FAQ row, honouring user and group permissions and falling
     * back to an unrestricted record when the FAQ has no access restrictions. Returns the raw
     * row, or null when nothing matches.
     *
     * @param int[] $groups
     */
    public function fetchRowBySolutionId(int $solutionId, int $userId, array $groups, bool $groupSupport): ?object;
}
