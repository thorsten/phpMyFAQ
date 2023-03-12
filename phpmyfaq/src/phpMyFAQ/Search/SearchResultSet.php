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
 * @copyright 2010-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-06-06
 */

namespace phpMyFAQ\Search;

use phpMyFAQ\Configuration;
use phpMyFAQ\Faq\FaqPermission;
use phpMyFAQ\User;
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
     * @param CurrentUser   $user User object
     * @param Configuration $config Configuration object
     */
    public function __construct(
        protected CurrentUser $user,
        private readonly FaqPermission $faqPermission,
        protected Configuration $config
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

        if ('basic' !== $this->config->get('security.permLevel')) {
            // @phpstan-ignore-next-line
            $currentGroupIds = $this->user->perm->getUserGroups($this->user->getUserId());
        } else {
            $currentGroupIds = [-1];
        }

        foreach ($this->rawResultSet as $result) {
            $permission = false;

            // check permissions for groups
            if ('medium' === $this->config->get('security.permLevel')) {
                $groupPermissions = $this->faqPermission->get(FaqPermission::GROUP, $result->id);
                foreach ($groupPermissions as $groupPermission) {
                    if (in_array($groupPermission, $currentGroupIds)) {
                        $permission = true;
                    }
                }
            }
            // check permission for user
            if ('basic' === $this->config->get('security.permLevel')) {
                $userPermission = $this->faqPermission->get(FaqPermission::USER, $result->id);
                if (in_array(-1, $userPermission) || in_array($this->user->getUserId(), $userPermission)) {
                    $permission = true;
                } else {
                    $permission = false;
                }
            }

            // check on duplicates
            if (!isset($duplicateResults[$result->id])) {
                $duplicateResults[$result->id] = 1;
            } else {
                ++$duplicateResults[$result->id];
                continue;
            }

            if (!isset($result->score)) {
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
        $score = 0;

        if (isset($object->relevance_thema)) {
            $score += $object->relevance_thema;
        }

        if (isset($object->relevance_content)) {
            $score += $object->relevance_thema;
        }

        if (isset($object->relevance_keywords)) {
            $score += $object->relevance_keywords;
        }

        return round($score / 3 * 100);
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
