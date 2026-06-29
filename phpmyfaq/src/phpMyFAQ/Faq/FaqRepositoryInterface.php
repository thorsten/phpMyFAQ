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
}
