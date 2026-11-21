<?php

/**
 * The scope a user holds a right in, as the set of grants it reaches them through.
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

/**
 * Answers whether a right covers a category in a language.
 *
 * The right is held for a category and a language only if a single grant covers both. Asking
 * for the category and for the language separately, each across all grants, would let a user
 * holding "German only" and "category 1 only" act on any category in any language.
 */
final readonly class RightScope
{
    /** @var list<RightGrant> */
    private array $grants;

    private function __construct(RightGrant ...$grants)
    {
        $this->grants = array_values($grants);
    }

    /**
     * The scope of a user who does not hold the right at all.
     */
    public static function none(): self
    {
        return new self();
    }

    /**
     * The scope of a user who holds the right in every category and language.
     */
    public static function unrestricted(): self
    {
        return new self(new RightGrant());
    }

    public static function ofGrants(RightGrant ...$grants): self
    {
        return new self(...$grants);
    }

    /**
     * @return list<RightGrant>
     */
    public function grants(): array
    {
        return $this->grants;
    }

    public function isEmpty(): bool
    {
        return $this->grants === [];
    }

    public function isUnrestricted(): bool
    {
        return array_any($this->grants, static fn(RightGrant $grant): bool => $grant->isUnrestricted());
    }

    /**
     * Whether the right covers the category in the language.
     */
    public function allowsCategory(int $categoryId, string $language): bool
    {
        return array_any(
            $this->grants,
            static fn(RightGrant $grant): bool => (
                $grant->coversCategory($categoryId) && $grant->coversLanguage($language)
            ),
        );
    }

    /**
     * Whether the right covers content without any category in the language. There is no
     * category membership to verify, so only a grant without category restriction qualifies.
     */
    public function allowsUncategorized(string $language): bool
    {
        return array_any(
            $this->grants,
            static fn(RightGrant $grant): bool => $grant->categoryIds === null && $grant->coversLanguage($language),
        );
    }

    /**
     * Whether the right covers content in the language that belongs to all the given
     * categories. Content without a category is covered only by a grant that is not
     * category-restricted.
     *
     * @param array<int> $categoryIds
     */
    public function allows(array $categoryIds, string $language): bool
    {
        if ($categoryIds === []) {
            return $this->allowsUncategorized($language);
        }

        return array_all($categoryIds, fn(int $categoryId): bool => $this->allowsCategory($categoryId, $language));
    }

    /**
     * Whether the right covers the language in at least one category. Use this only where no
     * category is known; it does not say which categories the language is covered in, so its
     * answer must never be combined with allowsCategoryInAnyLanguage().
     */
    public function allowsLanguageInAnyCategory(string $language): bool
    {
        return array_any($this->grants, static fn(RightGrant $grant): bool => $grant->coversLanguage($language));
    }

    /**
     * Whether the right covers the category in at least one language. Use this only where no
     * language is known; it does not say which languages the category is covered in, so its
     * answer must never be combined with allowsLanguageInAnyCategory().
     */
    public function allowsCategoryInAnyLanguage(int $categoryId): bool
    {
        return array_any($this->grants, static fn(RightGrant $grant): bool => $grant->coversCategory($categoryId));
    }

    /**
     * The categories the right covers in at least one language; null means every category.
     * Meant for narrowing what a page offers, not for authorizing an action in a language.
     *
     * @return list<int>|null
     */
    public function categoryIdsInAnyLanguage(): ?array
    {
        $categoryIds = [];
        foreach ($this->grants as $grant) {
            if ($grant->categoryIds === null) {
                return null;
            }

            $categoryIds = [...$categoryIds, ...$grant->categoryIds];
        }

        return array_values(array_unique($categoryIds));
    }

    /**
     * The languages the right covers in at least one category; null means every language.
     * Meant for narrowing what a page offers, not for authorizing an action in a category.
     *
     * @return list<string>|null
     */
    public function languagesInAnyCategory(): ?array
    {
        $languages = [];
        foreach ($this->grants as $grant) {
            if ($grant->languages === null) {
                return null;
            }

            $languages = [...$languages, ...$grant->languages];
        }

        return array_values(array_unique($languages));
    }

    /**
     * The categories the right covers in the language; null means every category.
     *
     * @return list<int>|null
     */
    public function categoryIdsIn(string $language): ?array
    {
        $categoryIds = [];
        foreach ($this->grants as $grant) {
            if (!$grant->coversLanguage($language)) {
                continue;
            }

            if ($grant->categoryIds === null) {
                return null;
            }

            $categoryIds = [...$categoryIds, ...$grant->categoryIds];
        }

        return array_values(array_unique($categoryIds));
    }

    /**
     * The languages the right covers in every category; null means every language. A direct
     * user right is never category-restricted, so this is the widest language scope the right
     * may be passed on in as a direct grant.
     *
     * @return list<string>|null
     */
    public function languagesInEveryCategory(): ?array
    {
        $languages = [];
        foreach ($this->grants as $grant) {
            if ($grant->categoryIds !== null) {
                continue;
            }

            if ($grant->languages === null) {
                return null;
            }

            $languages = [...$languages, ...$grant->languages];
        }

        return array_values(array_unique($languages));
    }

    /**
     * Whether the right is held everywhere a grant with the given restrictions would apply, so
     * that handing out such a grant does not exceed this scope. Null means unrestricted, and
     * only a grant that is itself unrestricted in that dimension covers it.
     *
     * @param array<int>|null    $categoryIds
     * @param array<string>|null $languages
     */
    public function covers(?array $categoryIds, ?array $languages): bool
    {
        // A null entry stands for "every category" or "every language".
        $categoryIds ??= [null];
        $languages ??= [null];

        if ($categoryIds === [] || $languages === []) {
            return false;
        }

        foreach ($categoryIds as $categoryId) {
            foreach ($languages as $language) {
                if (!$this->coversCell($categoryId === null ? null : (int) $categoryId, $language)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Whether this scope includes everything the other scope includes.
     */
    public function coversScope(self $other): bool
    {
        return array_all($other->grants, fn(RightGrant $grant): bool => $this->covers(
            $grant->categoryIds,
            $grant->languages,
        ));
    }

    private function coversCell(?int $categoryId, ?string $language): bool
    {
        return array_any(
            $this->grants,
            static fn(RightGrant $grant): bool => (
                ($categoryId === null ? $grant->categoryIds === null : $grant->coversCategory($categoryId))
                && ($language === null ? $grant->languages === null : $grant->coversLanguage($language))
            ),
        );
    }
}
