<?php

/**
 * phpMyFAQ MySQL (ext/mysqli) search classes.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-06-06
 */

namespace phpMyFAQ\Search\Database;

use phpMyFAQ\Configuration;
use phpMyFAQ\Search\SearchDatabase;

/**
 * Class Mysqli
 *
 * @package phpMyFAQ\Search\Database
 */
class Mysqli extends SearchDatabase implements DatabaseInterface
{
    /**
     * Constructor.
     */
    public function __construct(Configuration $configuration)
    {
        parent::__construct($configuration);
        $this->relevanceSupport = true;
    }

    /**
     * Prepares the search and executes it.
     *
     * @param  string $searchTerm Search term
     * @throws \Exception
     */
    public function search(string $searchTerm): mixed
    {
        if (is_numeric($searchTerm) && $this->configuration->get('search.searchForSolutionId')) {
            return parent::search($searchTerm);
        }
        $relevance = $this->configuration->get('search.enableRelevance');
        $columns = $this->getResultColumns();
        if ($this->relevanceSupport && $relevance) {
            $columns .= ', ' . $this->setRelevanceRanking($searchTerm);
            $orderBy = 'ORDER BY score DESC';
        } else {
            $orderBy = '';
        }
        $chars = [
            "\xe2\x80\x98",
            "\xe2\x80\x99",
            "\xe2\x80\x9c",
            "\xe2\x80\x9d",
            "\xe2\x80\x93",
            "\xe2\x80\x94",
            "\xe2\x80\xa6",
        ];
        $replace = ["'", "'", '"', '"', '-', '--', '...'];
        $searchTerm = str_replace($chars, $replace, $searchTerm);
        $query = sprintf(
            "
                SELECT
                    %s
                FROM 
                    %s %s %s
                WHERE
                    MATCH (%s) AGAINST ('%s' IN BOOLEAN MODE)
                    %s
                    %s",
            $columns,
            $this->getTable(),
            $this->getJoinedTable(),
            $this->getJoinedColumns(),
            $this->getMatchingColumns(),
            $this->configuration->getDb()->escape($searchTerm),
            $this->getConditions(),
            $orderBy
        );
        $this->resultSet = $this->configuration->getDb()->query($query);
        // Fallback for searches with less than three characters
        if (false !== $this->resultSet && 0 === $this->configuration->getDb()->numRows($this->resultSet)) {
            $query = sprintf(
                '
                    SELECT
                        %s
                    FROM 
                        %s %s %s
                    WHERE
                        %s
                        %s',
                $this->getResultColumns(),
                $this->getTable(),
                $this->getJoinedTable(),
                $this->getJoinedColumns(),
                $this->getMatchClause($searchTerm),
                $this->getConditions()
            );

            $this->resultSet = $this->configuration->getDb()->query($query);
        }
        return $this->resultSet;
    }

    /**
     * Add the matching columns into the columns for the relevance ranking
     */
    public function setRelevanceRanking(string $searchTerm): string
    {
        return sprintf(
            "MATCH (%s) AGAINST ('%s' IN BOOLEAN MODE) as score",
            $this->getMatchingColumns(),
            $this->configuration->getDb()->escape($searchTerm)
        );
    }
}
