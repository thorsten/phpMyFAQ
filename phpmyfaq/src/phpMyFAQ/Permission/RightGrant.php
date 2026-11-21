<?php

/**
 * One grant of a right: the categories and languages it is limited to.
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
 * @since     2026-11-21
 */

declare(strict_types=1);

namespace phpMyFAQ\Permission;

use InvalidArgumentException;

/**
 * A right reaches a user through a direct grant or through a group, and each of those grants
 * carries its own category and language restriction. The restrictions of one grant belong
 * together: a grant for "category 1" and another for "German" do not add up to "category 2 in
 * English". Keeping them paired in one object is what prevents that merge.
 */
final readonly class RightGrant
{
    /** @var list<int>|null null means every category */
    public ?array $categoryIds;

    /** @var list<string>|null null means every language */
    public ?array $languages;

    /**
     * @param array<int>|null    $categoryIds null means every category
     * @param array<string>|null $languages   null means every language
     * @throws InvalidArgumentException if a restriction is an empty list, which would be a
     *                                  grant that applies nowhere - use null for "unrestricted"
     */
    public function __construct(?array $categoryIds = null, ?array $languages = null)
    {
        if ($categoryIds === [] || $languages === []) {
            throw new InvalidArgumentException(
                'A grant restricted to no category or no language applies nowhere; pass null for "unrestricted".',
            );
        }

        $this->categoryIds = $categoryIds === null
            ? null
            : array_values(array_unique(array_map(intval(...), $categoryIds)));
        $this->languages = $languages === null ? null : array_values(array_unique($languages));
    }

    /**
     * Builds a grant from stored restriction rows, where an empty list means "unrestricted".
     *
     * @param array<int>    $categoryIds
     * @param array<string> $languages
     */
    public static function fromRestrictions(array $categoryIds, array $languages): self
    {
        return new self($categoryIds === [] ? null : $categoryIds, $languages === [] ? null : $languages);
    }

    public function isUnrestricted(): bool
    {
        return $this->categoryIds === null && $this->languages === null;
    }

    public function coversLanguage(string $language): bool
    {
        return $this->languages === null || in_array($language, $this->languages, strict: true);
    }

    public function coversCategory(int $categoryId): bool
    {
        return $this->categoryIds === null || in_array($categoryId, $this->categoryIds, strict: true);
    }
}
