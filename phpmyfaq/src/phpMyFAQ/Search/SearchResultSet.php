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
use phpMyFAQ\Faq\Permission;
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
