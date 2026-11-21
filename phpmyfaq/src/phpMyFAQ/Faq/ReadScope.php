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
use phpMyFAQ\Permission\RightGrant;
use phpMyFAQ\Permission\RightScope;
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
     * @param RightScope|null $scope null means the right is not enforced for this requester
     */
    private function __construct(
        private ?RightScope $scope,
    ) {
    }

    public static function unrestricted(): self
    {
        return new self(null);
    }

    /**
     * The single evaluation point for the FAQ read right.
     */
    public static function forUser(PermissionInterface $permission, int $userId): self
    {
        $right = PermissionType::FAQS_VIEW->value;

        if (!$permission->hasPermission($userId, $right)) {
            return new self(RightScope::none());
        }

        return new self($permission->getScopeForRight($userId, $right));
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
        return $this->scope === null || $this->scope->isUnrestricted();
    }

    /**
     * Whether an FAQ in the given category and language may be read. Both are judged against
     * the same grant: a category permitted in one language is not permitted in another.
     */
    public function allows(int $categoryId, string $language): bool
    {
        return $this->scope === null || $this->scope->allowsCategory($categoryId, $language);
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
        if ($this->scope === null || $this->scope->isUnrestricted()) {
            return '';
        }

        // One alternative per grant, so a category is only ever paired with the languages of
        // the grant that permits it.
        $alternatives = [];
        foreach ($this->scope->grants() as $grant) {
            $conditions = [];

            if ($grant->languages !== null) {
                $conditions[] = sprintf('%s.lang IN (%s)', $faqAlias, $this->quotedLanguageList($grant->languages));
            }

            if ($grant->categoryIds !== null) {
                $conditions[] = $this->categoryCondition($grant->categoryIds, $faqAlias, $categoryRelationAlias);
            }

            $alternatives[] = sprintf('(%s)', implode(' AND ', $conditions));
        }

        // No right at all: match no rows rather than silently falling through to an
        // unfiltered result.
        if ($alternatives === []) {
            return ' AND 1 = 0';
        }

        return sprintf(' AND (%s)', implode(' OR ', $alternatives));
    }

    /**
     * Constrains a faqcategoryrelations alias to the scoped categories, for queries that
     * project or aggregate a category id themselves — an outer join as well as a sub-query
     * computing something like MIN(category_id) must not surface a denied category.
     *
     * The relation row carries the language of its FAQ, so each grant constrains the category
     * together with its own languages.
     */
    public function toCategoryRelationConstraint(string $categoryRelationAlias): string
    {
        if ($this->scope === null || $this->scope->isUnrestricted()) {
            return '';
        }

        $grants = $this->scope->grants();
        if ($grants === []) {
            return ' AND 1 = 0';
        }

        // Without any category restriction there is no category to hide; the languages are
        // constrained on the FAQ itself by toSqlFragment().
        if (array_all($grants, static fn(RightGrant $grant): bool => $grant->categoryIds === null)) {
            return '';
        }

        $alternatives = [];
        foreach ($grants as $grant) {
            $conditions = [];

            if ($grant->languages !== null) {
                $conditions[] = sprintf(
                    '%s.record_lang IN (%s)',
                    $categoryRelationAlias,
                    $this->quotedLanguageList($grant->languages),
                );
            }

            if ($grant->categoryIds !== null) {
                $conditions[] = sprintf(
                    '%s.category_id IN (%s)',
                    $categoryRelationAlias,
                    implode(', ', $grant->categoryIds),
                );
            }

            $alternatives[] = sprintf('(%s)', implode(' AND ', $conditions));
        }

        return sprintf(' AND (%s)', implode(' OR ', $alternatives));
    }

    /**
     * @param list<int> $categoryIds
     */
    private function categoryCondition(array $categoryIds, string $faqAlias, ?string $categoryRelationAlias): string
    {
        // A query that joins faqcategoryrelations itself must constrain that alias directly:
        // the EXISTS predicate admits the FAQ as soon as one permitted category exists, so the
        // join could still project a denied category id next to it.
        if ($categoryRelationAlias !== null) {
            return sprintf('%s.category_id IN (%s)', $categoryRelationAlias, implode(', ', $categoryIds));
        }

        // An EXISTS sub-query rather than a join condition: not every FAQ query carries a
        // faqcategoryrelations alias in its outer query, and a join would fan one FAQ out
        // into several rows and break LIMIT and COUNT.
        return sprintf(
            'EXISTS (SELECT 1 FROM %sfaqcategoryrelations pmfrs'
            . ' WHERE pmfrs.record_id = %s.id AND pmfrs.record_lang = %s.lang'
            . ' AND pmfrs.category_id IN (%s))',
            Database::getTablePrefix(),
            $faqAlias,
            $faqAlias,
            implode(', ', $categoryIds),
        );
    }

    /**
     * @param list<string> $languages
     */
    private function quotedLanguageList(array $languages): string
    {
        $database = Configuration::getConfigurationInstance()->getDb();

        return implode(', ', array_map(static fn(string $language): string => sprintf(
            "'%s'",
            $database->escape($language),
        ), $languages));
    }
}
