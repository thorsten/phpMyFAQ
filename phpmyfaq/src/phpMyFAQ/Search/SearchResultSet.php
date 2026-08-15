<?php

/**
 * Implements result sets for phpMyFAQ search classes.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-06-06
 */

declare(strict_types=1);

namespace phpMyFAQ\Search;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Faq\Permission;
use phpMyFAQ\Faq\ReadScope;
use phpMyFAQ\User\CurrentUser;
use stdClass;

/**
 * Class SearchResultSet
 *
 * @package phpMyFAQ\Search
 */
class SearchResultSet
{
    /**
     * "Raw" search result set without permission checks and with possible
     * duplicates.
     *
     * @var stdClass[]
     */
    protected array $rawResultSet = [];

    /**
     * "Reviewed" search result set with checked permissions and without
     * duplicates.
     *
     * @var stdClass[]
     */
    protected array $reviewedResultSet = [];

    /**
     * Ordering of result set.
     */
    protected string $ordering;

    /**
     * Number of search results.
     */
    protected int $numberOfResults = 0;

    /**
     * Constructor.
     *
     * @param CurrentUser $currentUser User object
     * @param Configuration $configuration Configuration object
     */
    public function __construct(
        protected CurrentUser $currentUser,
        private readonly Permission $faqPermission,
        protected Configuration $configuration,
    ) {
    }

    /**
     * Check on user and group permissions and on duplicate FAQs.
     *
     * @param stdClass[] $resultSet Array with search results
     */
    public function reviewResultSet(array $resultSet): void
    {
        $this->setResultSet($resultSet);

        $duplicateResults = [];
        $currentGroupIds = [-1];

        // Not every caller routes its hits through Faq::getFaqResult() first, so the read scope
        // is applied here too — a hit a user may not open must not leak its question text.
        $readScope = $this->resolveReadScope();

        $storedRowsByFaq = $readScope->isUnrestricted()
            ? []
            : $this->loadStoredRowsForIncompleteHits($this->rawResultSet);

        if (
            'basic' !== $this->configuration->get(item: 'security.permLevel')
            && isset($this->currentUser->perm) // @mago-expect lint:no-isset - typed property may be uninitialized
        ) {
            $permission = $this->currentUser->perm;
            if (method_exists($permission, 'getUserGroups')) {
                $currentGroupIds = $permission->getUserGroups($this->currentUser->getUserId());
            }
        }

        foreach ($this->rawResultSet as $result) {
            $permission = false;

            if (!$this->isWithinReadScope($readScope, $result, $storedRowsByFaq)) {
                continue;
            }

            // check permissions for groups
            if ('medium' === $this->configuration->get(item: 'security.permLevel')) {
                $groupPermissions = $this->faqPermission->get(Permission::GROUP, (int) $result->id);
                $groupIds = $currentGroupIds;
                foreach ($groupPermissions as $groupPermission) {
                    if (!in_array($groupPermission, $groupIds, strict: true)) {
                        continue;
                    }

                    $permission = true;
                }
            }

            // check permission for a user
            if ('basic' === $this->configuration->get(item: 'security.permLevel')) {
                $userPermission = $this->faqPermission->get(Permission::USER, (int) $result->id);
                $permission =
                    in_array(-1, $userPermission, strict: true)
                    || in_array($this->currentUser->getUserId(), $userPermission, strict: true);
            }

            // check on duplicates
            $resultId = (int) $result->id;
            if (array_key_exists($resultId, $duplicateResults)) {
                continue;
            }

            $duplicateResults[$resultId] = true;

            if (!property_exists($result, 'score') || $result->score === null) {
                $result->score = $this->getScore($result);
            }

            if ($permission) {
                $this->reviewedResultSet[] = $result;
            }
        }

        $this->setNumberOfResults($this->reviewedResultSet);
    }

    /**
     * The single place the requester's read scope is resolved, overridable so tests can review
     * a result set under a chosen scope without a database-backed permission lookup.
     */
    protected function resolveReadScope(): ReadScope
    {
        return ReadScope::forUserId($this->configuration, $this->currentUser->getUserId());
    }

    /**
     * Search hits vary by backend, so a restricted scope must not take a hit at its word: a hit
     * that does not carry a usable language or category is authorized against the stored FAQ
     * data instead of being waved through — an absent field would otherwise become a way to
     * leak the question text of an FAQ the requester may not open.
     *
     * @param array<int, list<stdClass>> $storedRowsByFaq
     */
    private function isWithinReadScope(ReadScope $readScope, stdClass $result, array $storedRowsByFaq): bool
    {
        if ($readScope->isUnrestricted()) {
            return true;
        }

        $language = $this->hitLanguage($result);
        $categoryId = $this->hitCategoryId($result);

        if ($language === null || $categoryId === null) {
            return $this->isStoredFaqWithinReadScope(
                $readScope,
                $storedRowsByFaq[(int) ($result->id ?? 0)] ?? [],
                $language,
            );
        }

        return $readScope->allowsLanguage($language) && $readScope->allowsCategory($categoryId);
    }

    /**
     * Loads the stored language and category rows of every hit that will need the storage
     * fallback, in one query — resolving them per hit would issue one query for each
     * incomplete hit in the raw result set.
     *
     * @param stdClass[] $results
     * @return array<int, list<stdClass>>
     */
    private function loadStoredRowsForIncompleteHits(array $results): array
    {
        $faqIds = [];

        foreach ($results as $result) {
            if ($this->hitLanguage($result) !== null && $this->hitCategoryId($result) !== null) {
                continue;
            }

            $faqId = (int) ($result->id ?? 0);
            if ($faqId > 0) {
                $faqIds[$faqId] = true;
            }
        }

        if ($faqIds === []) {
            return [];
        }

        $database = $this->configuration->getDb();
        $query = sprintf(
            'SELECT fd.id AS id, fd.lang AS lang, fcr.category_id AS category_id FROM %sfaqdata fd '
            . 'LEFT JOIN %sfaqcategoryrelations fcr ON fcr.record_id = fd.id AND fcr.record_lang = fd.lang '
            . 'WHERE fd.id IN (%s)',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            implode(', ', array_keys($faqIds)),
        );

        $storedRowsByFaq = [];
        foreach ($database->fetchAll($database->query($query)) ?? [] as $row) {
            $storedRowsByFaq[(int) $row->id][] = $row;
        }

        return $storedRowsByFaq;
    }

    /**
     * Authorizes an incomplete hit from its stored rows: the hit passes when at least one
     * stored language and category combination lies within the scope, constrained to the
     * hit's language when it carried one. A hit whose record resolved to no rows fails closed.
     *
     * @param list<stdClass> $storedRows
     */
    private function isStoredFaqWithinReadScope(ReadScope $readScope, array $storedRows, ?string $language): bool
    {
        foreach ($storedRows as $row) {
            if ($language !== null && (string) $row->lang !== $language) {
                continue;
            }

            if (
                $readScope->allowsLanguage((string) $row->lang) && $readScope->allowsCategory((int) $row->category_id)
            ) {
                return true;
            }
        }

        return false;
    }

    private function hitLanguage(stdClass $result): ?string
    {
        return property_exists($result, 'lang') && (string) $result->lang !== '' ? (string) $result->lang : null;
    }

    private function hitCategoryId(stdClass $result): ?int
    {
        return property_exists($result, 'category_id') && (int) $result->category_id > 0
            ? (int) $result->category_id
            : null;
    }

    /**
     * Sets the "raw" search results.
     *
     * @param stdClass[] $resultSet Array with search results
     */
    public function setResultSet(array $resultSet): void
    {
        $this->rawResultSet = $resultSet;
    }

    public function getScore(stdClass $object): float
    {
        $score = 0.0;

        if (property_exists($object, 'relevance_thema') && is_numeric($object->relevance_thema)) {
            $score += (float) $object->relevance_thema;
        }

        if (property_exists($object, 'relevance_content') && is_numeric($object->relevance_content)) {
            $score += (float) $object->relevance_content;
        }

        if (property_exists($object, 'relevance_keywords') && is_numeric($object->relevance_keywords)) {
            $score += (float) $object->relevance_keywords;
        }

        return round(($score / 3) * 100);
    }

    /**
     * Returns the "reviewed" search results.
     *
     * @return stdClass[]
     */
    public function getResultSet(): array
    {
        return $this->reviewedResultSet;
    }

    /**
     * Returns the number search results.
     */
    public function getNumberOfResults(): int
    {
        return $this->numberOfResults;
    }

    /**
     * Sets the number of search results.
     *
     * @param stdClass[] $resultSet
     */
    public function setNumberOfResults(array $resultSet): void
    {
        $this->numberOfResults = count($resultSet);
    }
}
