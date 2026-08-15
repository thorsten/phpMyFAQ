<?php

/**
 * The FAQ read scope of the requesting user.
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
 * @since     2026-08-12
 */

declare(strict_types=1);

namespace phpMyFAQ\Faq;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\User\CurrentUser;

/**
 * Answers which FAQs a requester may read at all, as a reusable SQL fragment.
 *
 * This is the single place where PermissionType::FAQS_VIEW is evaluated, so read access stays
 * one decision instead of a check repeated per query. It is deliberately orthogonal to the
 * faqdata_user / faqdata_group grants that QueryHelper::queryPermission() renders: those answer
 * "does this record's ACL admit me", this one answers "may I read FAQs, and which ones".
 *
 * Anonymous requesters are never gated. They hold no faqright rows at all, so enforcing the
 * right against them would empty the public FAQ on every installation; guest access stays
 * governed by security.enableLoginOnly and the per-record "-1 = everyone" grants.
 */
final readonly class ReadScope
{
    /**
     * @param array<int>|null    $categoryIds null means every category
     * @param array<string>|null $languages   null means every language
     */
    private function __construct(
        private bool $enforced,
        private bool $granted,
        private ?array $categoryIds,
        private ?array $languages,
    ) {
    }

    public static function unrestricted(): self
    {
        return new self(false, true, null, null);
    }

    /**
     * The single evaluation point for the FAQ read right.
     */
    public static function forUser(PermissionInterface $permission, int $userId): self
    {
        $right = PermissionType::FAQS_VIEW->value;

        if (!$permission->hasPermission($userId, $right)) {
            return new self(true, false, [], []);
        }

        return new self(
            true,
            true,
            $permission->getAllowedCategoriesForRight($userId, $right),
            $permission->getAllowedLanguagesForRight($userId, $right),
        );
    }

    /**
     * Resolve the scope for a user id.
     *
     * Call this once, where the requester becomes known — the setUser() of the class about to
     * run the query — and hand the result to QueryHelper. Resolving it lazily while a query is
     * being built would make every FAQ query issue its own user and permission lookups.
     *
     * @throws \Exception
     */
    public static function forUserId(Configuration $configuration, int $userId): self
    {
        // -1 is the anonymous account, 0 means no user was ever resolved.
        if ($userId <= 0) {
            return self::unrestricted();
        }

        $currentUser = new CurrentUser($configuration);
        $currentUser->getUserById($userId, allowBlockedUsers: true);

        return self::forUser($currentUser->perm, $userId);
    }

    public function isUnrestricted(): bool
    {
        return !$this->enforced || $this->granted && $this->categoryIds === null && $this->languages === null;
    }

    public function allowsCategory(int $categoryId): bool
    {
        if (!$this->enforced) {
            return true;
        }

        if (!$this->granted) {
            return false;
        }

        return $this->categoryIds === null || in_array($categoryId, $this->categoryIds, strict: true);
    }

    public function allowsLanguage(string $language): bool
    {
        if (!$this->enforced) {
            return true;
        }

        if (!$this->granted) {
            return false;
        }

        return $this->languages === null || in_array($language, $this->languages, strict: true);
    }

    /**
     * Renders the scope as a WHERE fragment to append to an FAQ query.
     *
     * Returns an empty string whenever the requester is unrestricted, which is the case for
     * anonymous visitors, superadmins and every user holding an unscoped grant — so the common
     * query stays exactly as it was.
     *
     * @param string      $faqAlias              the alias the query gives the faqdata table
     * @param string|null $categoryRelationAlias the alias of a faqcategoryrelations join in the
     *                                           outer query, when it has one
     */
    public function toSqlFragment(string $faqAlias = 'fd', ?string $categoryRelationAlias = null): string
    {
        if (!$this->enforced) {
            return '';
        }

        // No right at all, or a scope that resolves to nothing: match no rows rather than
        // silently falling through to an unfiltered result.
        if (!$this->granted || $this->categoryIds === [] || $this->languages === []) {
            return ' AND 1 = 0';
        }

        $fragment = '';

        if ($this->languages !== null) {
            $fragment .= sprintf(' AND %s.lang IN (%s)', $faqAlias, $this->quotedLanguageList());
        }

        if ($this->categoryIds === null) {
            return $fragment;
        }

        // A query that joins faqcategoryrelations itself must constrain that alias directly:
        // the EXISTS predicate admits the FAQ as soon as one permitted category exists, so the
        // join could still project a denied category id next to it.
        if ($categoryRelationAlias !== null) {
            return $fragment . $this->toCategoryRelationConstraint($categoryRelationAlias);
        }

        // An EXISTS sub-query rather than a join condition: not every FAQ query carries a
        // faqcategoryrelations alias in its outer query, and a join would fan one FAQ out
        // into several rows and break LIMIT and COUNT.
        return sprintf(
            '%s AND EXISTS (SELECT 1 FROM %sfaqcategoryrelations pmfrs'
            . ' WHERE pmfrs.record_id = %s.id AND pmfrs.record_lang = %s.lang'
            . ' AND pmfrs.category_id IN (%s))',
            $fragment,
            Database::getTablePrefix(),
            $faqAlias,
            $faqAlias,
            $this->categoryIdList(),
        );
    }

    /**
     * Constrains a faqcategoryrelations alias to the scoped categories, for queries that
     * project or aggregate a category id themselves — an outer join as well as a sub-query
     * computing something like MIN(category_id) must not surface a denied category.
     */
    public function toCategoryRelationConstraint(string $categoryRelationAlias): string
    {
        if (!$this->enforced || $this->categoryIds === null) {
            return '';
        }

        if (!$this->granted || $this->categoryIds === []) {
            return ' AND 1 = 0';
        }

        return sprintf(' AND %s.category_id IN (%s)', $categoryRelationAlias, $this->categoryIdList());
    }

    private function categoryIdList(): string
    {
        return implode(', ', array_map(intval(...), $this->categoryIds ?? []));
    }

    private function quotedLanguageList(): string
    {
        $database = Configuration::getConfigurationInstance()->getDb();

        return implode(', ', array_map(static fn(string $language): string => sprintf(
            "'%s'",
            $database->escape($language),
        ), $this->languages ?? []));
    }
}
