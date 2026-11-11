<?php

/**
 * The FAQ edit scope of the requesting user.
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
 * @since     2026-11-11
 */

declare(strict_types=1);

namespace phpMyFAQ\Faq;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Permission\PermissionInterface;

/**
 * Answers which FAQs a requester may edit, for the admin listings that show FAQs across
 * categories and languages.
 *
 * PermissionType::FAQ_EDIT can be limited to categories and languages. A listing that only
 * asks "does the user hold the right" would show a scoped editor the titles of drafts they
 * may not open, so every such listing narrows its rows with this scope.
 *
 * The scope follows the rules the edit page enforces through
 * AbstractController::userHasPermissionForCategories(): a category-restricted editor may edit
 * an FAQ only if every one of its categories is permitted, and never an FAQ without a category.
 * That is stricter than ReadScope, which admits an FAQ as soon as one category is permitted.
 */
final readonly class EditScope
{
    /**
     * @param array<int>|null    $categoryIds null means every category
     * @param array<string>|null $languages   null means every language
     */
    private function __construct(
        private ?array $categoryIds,
        private ?array $languages,
    ) {
    }

    public static function forUser(PermissionInterface $permission, int $userId): self
    {
        $right = PermissionType::FAQ_EDIT->value;

        if (!$permission->hasPermission($userId, $right)) {
            return new self([], []);
        }

        return new self(
            $permission->getAllowedCategoriesForRight($userId, $right),
            $permission->getAllowedLanguagesForRight($userId, $right),
        );
    }

    /**
     * Decides for an FAQ that is already loaded, where toSqlFragment() cannot be applied.
     *
     * @param array<int> $categoryIds every category the FAQ is assigned to in $language
     */
    public function allowsFaq(array $categoryIds, string $language): bool
    {
        if ($this->languages !== null && !in_array($language, $this->languages, strict: true)) {
            return false;
        }

        if ($this->categoryIds === null) {
            return true;
        }

        return $categoryIds !== [] && array_diff($categoryIds, $this->categoryIds) === [];
    }

    /**
     * Renders the scope as a WHERE fragment to append to an FAQ query.
     *
     * Returns an empty string for superadmins and every user holding an unscoped grant, so the
     * common query stays exactly as it was.
     *
     * @param string $faqAlias the alias the query gives the faqdata table
     */
    public function toSqlFragment(string $faqAlias = 'fd'): string
    {
        // No right at all, or a scope that resolves to nothing: match no rows rather than
        // silently falling through to an unfiltered result.
        if ($this->categoryIds === [] || $this->languages === []) {
            return ' AND 1 = 0';
        }

        $fragment = '';

        if ($this->languages !== null) {
            $fragment .= sprintf(' AND %s.lang IN (%s)', $faqAlias, $this->quotedLanguageList());
        }

        if ($this->categoryIds === null) {
            return $fragment;
        }

        // Two sub-queries rather than a join condition: the FAQ needs at least one category,
        // and none of its categories may lie outside the scope. A join would also fan one FAQ
        // out into several rows.
        $categoryRelation = sprintf(
            'SELECT 1 FROM %sfaqcategoryrelations pmfes WHERE pmfes.record_id = %s.id AND pmfes.record_lang = %s.lang',
            Database::getTablePrefix(),
            $faqAlias,
            $faqAlias,
        );

        return sprintf(
            '%s AND EXISTS (%s) AND NOT EXISTS (%s AND pmfes.category_id NOT IN (%s))',
            $fragment,
            $categoryRelation,
            $categoryRelation,
            implode(', ', array_map(intval(...), $this->categoryIds)),
        );
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
